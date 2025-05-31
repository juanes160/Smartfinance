<?php
// Conexión a la base de datos
include 'conexion.php';

// Obtener datos del formulario
$nombre_completo = $_POST['nombre_completo'];
$correo = $_POST['correo'];
$usuario = $_POST['usuario'];
$contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);

// Preparar la consulta con sentencias preparadas y nombres escapados con backticks
$stmt = $conexion->prepare("INSERT INTO usuarios (`nombre_completo`, `correo`, `usuario`, `contraseña`) VALUES (?, ?, ?, ?)");

if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conexion->error);
}

// Vincular parámetros
$stmt->bind_param("ssss", $nombre_completo, $correo, $usuario, $contraseña);

// Ejecutar la consulta
if ($stmt->execute()) {
    echo "<script>
            alert('Registro exitoso');
            window.location.href='index.html';
          </script>";
} else {
    echo "<script>
            alert('Error al registrar: " . $stmt->error . "');
            window.history.back();
          </script>";
}

// Cerrar la sentencia y la conexión
$stmt->close();
$conexion->close();
?>
