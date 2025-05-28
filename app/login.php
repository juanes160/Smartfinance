<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "smartfinance");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Obtener datos del formulario
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];

// Verificar si existe el usuario
$sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
$resultado = $conexion->query($sql);

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    // Verificar contraseña
    if (password_verify($contraseña, $usuario['contraseña'])) {
        // Autenticación exitosa
        $_SESSION['usuario'] = $usuario['usuario'];
        echo "<script>
                alert('Inicio de sesión exitoso. Bienvenido, {$usuario['usuario']}');
                window.location.href = 'bienvenida.php';
              </script>";
    } else {
        echo "<script>
                alert('Contraseña incorrecta');
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
