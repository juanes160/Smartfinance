<?php
session_start();

// Conexión a la base de datos
include 'conexion.php';

// Obtener datos del formulario
$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

// Verificar si existe el usuario
$sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    // Verificar contrasena
    if (password_verify($contrasena, $usuario['contrasena'])) {
        // Autenticación exitosa
        $_SESSION['usuario'] = $usuario['usuario'];
        echo "<script>
                alert('Inicio de sesión exitoso. Bienvenido, {$usuario['usuario']}');
                window.location.href = 'bienvenida.php';
              </script>";
    } else {
        echo "<script>
                alert('contrasena incorrecta');
                window.history.back();
              </script>";
    }
} else {
    echo "<script>
            alert('No existe una cuenta con ese correo');
            window.history.back();
          </script>";
}

$conexion->close();
?>
