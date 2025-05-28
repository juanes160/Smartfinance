<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['nombre_categoria'])) {
        $nombre = trim($_POST['nombre_categoria']);

        // Verificar si ya existe una categoría con ese nombre
        $verificar = $conexion->prepare("SELECT id FROM categorias WHERE nombre = ?");
        $verificar->bind_param("s", $nombre);
        $verificar->execute();
        $resultado = $verificar->get_result();

        if ($resultado->num_rows === 0) {
            // Insertar la nueva categoría
            $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->bind_param("s", $nombre);

            if ($stmt->execute()) {
                header("Location: bienvenida.php?mensaje=categoria_agregada");
                exit;
            } else {
                header("Location: bienvenida.php?error=error_guardar_categoria");
                exit;
            }
        } else {
            header("Location: bienvenida.php?error=categoria_existente");
            exit;
        }
    } else {
        header("Location: bienvenida.php?error=nombre_vacio");
        exit;
    }
} else {
    header("Location: bienvenida.php");
    exit;
}
