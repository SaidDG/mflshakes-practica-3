<?php
// Registra la compra del carrito en las tablas pedidos y detalle_pedido
require "conexion.php";
header("Content-Type: application/json; charset=utf-8");

function salir($ok, $mensaje) {
  echo json_encode(["ok" => $ok, "mensaje" => $mensaje]);
  exit;
}

$d = json_decode(file_get_contents("php://input"), true);
$email  = trim($d["email"] ?? "");
$metodo = $d["metodo_pago"] ?? "tarjeta";
$items  = $d["items"] ?? [];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) salir(false, "El correo no es válido.");
if (!in_array($metodo, ["tarjeta", "paypal", "efectivo"], true)) salir(false, "Método de pago no válido.");
if (!is_array($items) || count($items) === 0) salir(false, "Tu carrito está vacío.");

try {
  $conexion->begin_transaction();

  // 1) El comprador debe tener cuenta (tabla usuarios)
  $q = $conexion->prepare("SELECT id_usuario, direccion, ciudad FROM usuarios WHERE email = ?");
  $q->bind_param("s", $email);
  $q->execute();
  $u = $q->get_result()->fetch_assoc();
  if (!$u) {
    $conexion->rollback();
    salir(false, "Ese correo no está registrado. Primero crea tu cuenta en el botón \"Cuenta\".");
  }
  $direccion = trim(($u["direccion"] ?? "") . " " . ($u["ciudad"] ?? ""));
  if ($direccion === "") $direccion = "Sin dirección registrada";

  // 2) Se toman precio y stock de la base de datos (no del navegador)
  $busca = $conexion->prepare(
    "SELECT p.nombre, p.precio, t.stock
       FROM productos p
       JOIN producto_tallas t ON t.id_producto = p.id_producto
      WHERE p.id_producto = ? AND t.talla = ? AND p.activo = 1
      FOR UPDATE"
  );
  $total = 0;
  $lineas = [];
  foreach ($items as $it) {
    $id    = (int)($it["id"] ?? 0);
    $talla = (string)($it["talla"] ?? "");
    $cant  = (int)($it["cantidad"] ?? 0);
    if ($cant < 1 || $cant > 20) throw new RuntimeException("Cantidad no válida.");

    $busca->bind_param("is", $id, $talla);
    $busca->execute();
    $p = $busca->get_result()->fetch_assoc();
    if (!$p) throw new RuntimeException("Un producto de tu carrito ya no está disponible.");
    if ($p["stock"] < $cant) {
      throw new RuntimeException("No hay suficiente stock de " . $p["nombre"] . " talla " . $talla . " (quedan " . $p["stock"] . ").");
    }
    $total += $p["precio"] * $cant;
    $lineas[] = ["id" => $id, "talla" => $talla, "cant" => $cant, "precio" => $p["precio"]];
  }

  // 3) Se guarda el pedido y cada producto comprado
  $ped = $conexion->prepare("INSERT INTO pedidos (id_usuario, total, direccion_envio, metodo_pago) VALUES (?, ?, ?, ?)");
  $ped->bind_param("idss", $u["id_usuario"], $total, $direccion, $metodo);
  $ped->execute();
  $idPedido = $conexion->insert_id;

  $det = $conexion->prepare("INSERT INTO detalle_pedido (id_pedido, id_producto, talla, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)");
  $res = $conexion->prepare("UPDATE producto_tallas SET stock = stock - ? WHERE id_producto = ? AND talla = ?");
  $piezas = 0;
  foreach ($lineas as $l) {
    $det->bind_param("iisid", $idPedido, $l["id"], $l["talla"], $l["cant"], $l["precio"]);
    $det->execute();
    $res->bind_param("iis", $l["cant"], $l["id"], $l["talla"]);
    $res->execute();
    $piezas += $l["cant"];
  }

  $conexion->commit();
  salir(true, "¡Compra registrada! Pedido #" . $idPedido . " · " . $piezas . " producto(s) · Total $ " . number_format($total, 2));
} catch (RuntimeException $ex) {
  $conexion->rollback();
  salir(false, $ex->getMessage());
} catch (Throwable $ex) {
  $conexion->rollback();
  salir(false, "Error en la base de datos: " . $ex->getMessage());
}