<?php
require_once 'conexion.php';

if (!isset($_GET['id'])) {
    die("ID de transacción no especificado.");
}

$id = (int) $_GET['id'];

$sql = "DELETE FROM transacciones WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Puedes redirigir después de eliminar
    header("Location: bienvenida.php");
    exit;
} else {
    echo "Error al eliminar la transacción: " . $conexion->error;
}
