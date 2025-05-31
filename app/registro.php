<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "smartfinance");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Obtener datos del formulario
$nombre_completo = $_POST['nombre_completo'];
$correo = $_POST['correo'];
$usuario = $_POST['usuario'];
$contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

// Insertar datos
$sql = "INSERT INTO usuarios (nombre_completo, correo, usuario, contrasena)
        VALUES ('$nombre_completo', '$correo', '$usuario', '$contrasena')";

if ($conexion->query($sql) === TRUE) {
    echo "<script>
            alert('Registro exitoso');
            window.location.href='index.html';
          </script>";
} else {
    echo "<script>
            alert('Error al registrar: " . $conexion->error . "');
            window.history.back();
          </script>";
}

$conexion->close();
?>
