<?php
require "conexion.php";

$mensaje = "";
$tipo = "";
$ajax = (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "fetch");
$v = ["nombre"=>"","apellido"=>"","email"=>"","telefono"=>"","direccion"=>"","ciudad"=>"","codigo_postal"=>""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  try {
    foreach ($v as $campo => $x) {
        $v[$campo] = trim($_POST[$campo] ?? "");
    }

    if ($v["nombre"] === "" || $v["apellido"] === "" || $v["email"] === "") {
        $mensaje = "Completa nombre, apellido y correo.";
        $tipo = "error";
    } elseif (!filter_var($v["email"], FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no es válido.";
        $tipo = "error";
    } else {
        // ¿Ya existe el correo?
        $check = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $check->bind_param("s", $v["email"]);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $mensaje = "Ese correo ya está registrado.";
            $tipo = "error";
        } else {
            $stmt = $conexion->prepare(
                "INSERT INTO usuarios (nombre, apellido, email, telefono, direccion, ciudad, codigo_postal)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssssss",
                $v["nombre"], $v["apellido"], $v["email"],
                $v["telefono"], $v["direccion"], $v["ciudad"], $v["codigo_postal"]
            );
            if ($stmt->execute()) {
                $tipo = "ok";
                if (!$ajax) {
                    header("Location: index.html?registro=ok");
                    exit;
                }
            } else {
                $mensaje = "No se pudo guardar el registro. Intenta de nuevo.";
                $tipo = "error";
            }
        }
    }
  } catch (Throwable $ex) {
    $mensaje = "Error en la base de datos: " . $ex->getMessage();
    $tipo = "error";
  }
  if ($ajax) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["ok" => ($tipo === "ok"), "mensaje" => $mensaje]);
    exit;
  }
}

function e($t) { return htmlspecialchars($t, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta | Shake Club</title>
<style>
  :root { --rojo:#dc0a17; --negro:#111; --gris:#f4f4f4; --linea:#e5e5e5; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Helvetica, Arial, sans-serif; color:var(--negro); background:#fff; font-size:14px; }
  a { color:inherit; }
  :focus-visible { outline:2px solid var(--negro); outline-offset:2px; }

  .aviso { background:var(--rojo); color:#fff; text-align:center; padding:7px; font-size:12px; font-weight:bold; text-transform:uppercase; }
  header { text-align:center; padding:20px; border-bottom:1px solid var(--linea); }
  .logo { font-family:Impact,"Arial Black",sans-serif; font-size:30px; color:var(--rojo); letter-spacing:1px; text-decoration:none; }

  main { max-width:560px; margin:50px auto; padding:0 20px; }
  h1 { font-size:30px; letter-spacing:-1px; margin-bottom:6px; }
  .sub { color:#777; margin-bottom:28px; }

  .fila { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
  label { display:block; margin-bottom:16px; text-transform:uppercase; font-size:12px; letter-spacing:.5px; }
  input { display:block; width:100%; margin-top:6px; padding:12px; border:1px solid var(--negro); background:#fff; font-size:14px; }
  button { width:100%; background:var(--negro); color:#fff; border:0; padding:15px; font-size:14px; text-transform:uppercase; cursor:pointer; margin-top:6px; }
  button:hover { background:var(--rojo); }

  .msg { padding:12px 14px; margin-bottom:22px; border-left:4px solid; }
  .msg.ok { background:#eaf7ee; border-color:#1f8a43; }
  .msg.error { background:#fdeaea; border-color:var(--rojo); }
  .pie { margin-top:22px; text-align:center; }
  .pie a { text-decoration:underline; }

  @media (max-width:520px) { .fila { grid-template-columns:1fr; } }
</style>
</head>
<body>

<div class="aviso">Envíos a todo México</div>
<header><a href="index.html" class="logo">M*** SHAKES</a></header>

<main>
  <h1>Crear cuenta</h1>
  <p class="sub">Regístrate para comprar y seguir tus pedidos.</p>

  <?php if ($mensaje): ?>
    <div class="msg <?php echo $tipo; ?>"><?php echo e($mensaje); ?></div>
  <?php endif; ?>

  <form method="post" action="registro.php">
    <div class="fila">
      <label>Nombre
        <input type="text" name="nombre" value="<?php echo e($v['nombre']); ?>" required>
      </label>
      <label>Apellido
        <input type="text" name="apellido" value="<?php echo e($v['apellido']); ?>" required>
      </label>
    </div>

    <label>Correo electrónico
      <input type="email" name="email" value="<?php echo e($v['email']); ?>" required>
    </label>

    <label>Teléfono
      <input type="tel" name="telefono" value="<?php echo e($v['telefono']); ?>" placeholder="10 dígitos">
    </label>

    <label>Dirección
      <input type="text" name="direccion" value="<?php echo e($v['direccion']); ?>" placeholder="Calle, número y colonia">
    </label>

    <div class="fila">
      <label>Ciudad
        <input type="text" name="ciudad" value="<?php echo e($v['ciudad']); ?>">
      </label>
      <label>Código postal
        <input type="text" name="codigo_postal" value="<?php echo e($v['codigo_postal']); ?>" maxlength="10">
      </label>
    </div>


    <button type="submit">Crear cuenta</button>
  </form>

  <p class="pie">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a> · <a href="index.html">Volver a la tienda</a></p>
</main>

</body>
</html>