<?php
// Conexión a MySQL (XAMPP por defecto: usuario root, sin contraseña)
$host = "localhost";
$usuario = "root";
$clave = "";
$bd = "mshakes";
 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 
try {
    $conexion = new mysqli($host, $usuario, $clave, $bd);
    $conexion->set_charset("utf8mb4");
} catch (mysqli_sql_exception $ex) {
    die("<h3 style='font-family:Arial;color:#dc0a17'>No se pudo conectar a la base de datos</h3>
         <p style='font-family:Arial'>Detalle: " . htmlspecialchars($ex->getMessage()) . "</p>
         <p style='font-family:Arial'>Revisa que MySQL esté encendido en XAMPP y que la base <b>$bd</b> exista.</p>");
}
?>
 