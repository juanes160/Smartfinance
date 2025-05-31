<?php
$servidor = "db"; // 👈 CAMBIO IMPORTANTE: el nombre del servicio MySQL en docker-compose
$usuario = "usuario"; // lo definirás en docker-compose.yml
$contrasena = "clave"; // igual que en docker-compose.yml
$base_de_datos = "smartfinance";

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
