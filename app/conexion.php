<?php
$servidor = "localhost";
$usuario = "root";
$contrasena = ""; // si tienes contraseña, escríbela aquí
$base_de_datos = "smartfinance"; // reemplaza por el nombre real de tu base de datos

// Crear conexión
$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
