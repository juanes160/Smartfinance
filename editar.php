<?php
require_once 'conexion.php';

if (!isset($_GET['id'])) {
    die("ID de transacción no especificado.");
}

$id = (int) $_GET['id'];

// Obtener la transacción
$sql = "SELECT * FROM transacciones WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Transacción no encontrada.");
}

$transaccion = $result->fetch_assoc();

// Obtener todas las categorías para el select
$sql_cat = "SELECT id, nombre FROM categorias ORDER BY nombre";
$result_cat = $conexion->query($sql_cat);

$categorias = [];
if ($result_cat && $result_cat->num_rows > 0) {
    while ($fila = $result_cat->fetch_assoc()) {
        $categorias[] = $fila;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'] ?? $transaccion['fecha'];
    $monto = $_POST['monto'] ?? $transaccion['monto'];
    $descripcion = $_POST['descripcion'] ?? $transaccion['descripcion'];
    $categoria_id = $_POST['categoria_id'] ?? null;
    $tipo = $_POST['tipo'] ?? $transaccion['tipo'];

    $sql_update = "UPDATE transacciones SET fecha=?, monto=?, descripcion=?, categoria_id=?, tipo=? WHERE id=?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("sdsssi", $fecha, $monto, $descripcion, $categoria_id, $tipo, $id);

    if ($stmt_update->execute()) {
        header("Location: bienvenida.php");
        exit;
    } else {
        $error = "Error al actualizar: " . $conexion->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Editar Transacción</title>
<style>
  
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin: 0;
            padding: 0 15px 50px;
            color: #fff;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            max-width: 900px;
            margin: 0 auto 30px;
        }
        header h1 {
            font-weight: 700;
            font-size: 1.8rem;
        }
        a.logout-btn {
            background: #ff4b5c;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 700;
            transition: background-color 0.3s ease;
        }
        a.logout-btn:hover {
            background: #ff2c3b;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .balances {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            gap: 15px;
            flex-wrap: wrap;
        }
        .balance-box {
            flex: 1 1 200px;
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            cursor: default;
        }
        .balance-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .balance-box strong {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .balance-box .amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 0 6px rgba(0,0,0,0.4);
        }
        form {
            background: rgba(255,255,255,0.2);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto 40px;
            animation: fadeIn 0.8s ease forwards;
        }
        form label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            margin-top: 15px;
            color: #fff;
            text-shadow: 0 0 3px rgba(0,0,0,0.6);
        }
        form select, form input[type="number"], form input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: none;
            outline: none;
            font-size: 1rem;
            transition: box-shadow 0.3s ease;
        }
        form select:focus, form input[type="number"]:focus, form input[type="text"]:focus {
            box-shadow: 0 0 8px #6c63ff;
        }
        form input[type="submit"] {
            margin-top: 25px;
            background: #6c63ff;
            border: none;
            padding: 12px 0;
            width: 100%;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.7);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        form input[type="submit"]:hover {
            background: #574bdb;
            box-shadow: 0 6px 20px rgba(87, 75, 219, 0.9);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 700;
            text-shadow: 0 0 4px rgba(0,0,0,0.5);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 6px 25px rgba(0,0,0,0.3);
        }
        thead {
            background: #6c63ff;
        }
        thead th {
            color: #fff;
            padding: 15px 10px;
            font-weight: 700;
            text-align: left;
            letter-spacing: 1px;
        }
        tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            transition: background-color 0.3s ease;
        }
        tbody tr:hover {
            background: rgba(108, 99, 255, 0.3);
        }
        tbody td {
            padding: 12px 10px;
            color: #fff;
            vertical-align: middle;
        }
        tbody td:nth-child(2) {
            text-transform: capitalize;
            font-weight: 600;
        }
        tbody td:nth-child(3) {
            font-weight: 700;
            color: #ffe600;
        }
        @media(max-width: 600px) {
            .balances {
                flex-direction: column;
                gap: 20px;
            }
            form {
                width: 100%;
                padding: 20px;
            }
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        /* Mensajes de error */
        .error-message {
            color: #ff4b5c;
            font-weight: 700;
            margin-top: 8px;
            font-size: 0.9rem;
            display: none;
            animation: fadeIn 0.5s ease forwards;
        }

        .eliminar-btn {
            background: #ff4b5c;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            outline: none;
            box-shadow: none;
            text-decoration: none;
            font-family: inherit;
            font-size: inherit;
        }



        .consejo-flotante {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: #f0f8ff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 15px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            animation: slideIn 0.5s ease forwards;
            margin-bottom: 15px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            }

            .consejo-flotante:hover {
            background-color: #dcefff;
            }

            .consejo-flotante h4 {
            margin: 0 0 8px 0;
            font-weight: 700;
            }

            .consejo-flotante p {
            margin: 0;
            font-size: 0.9rem;
            }

            @keyframes slideIn {
            from {opacity: 0; transform: translateX(100%);}
            to {opacity: 1; transform: translateX(0);}
            }

        .boton-accion {
            padding: 4px 10px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 4px;
            margin: 0 3px;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .boton-editar {
            background-color: #ffc107;
            color: #000;
        }

        .boton-editar:hover {
            background-color: #e0a800;
            color: #000;
        }

        .boton-eliminar {
            background-color: #dc3545;
            color: white;
        }

        .boton-eliminar:hover {
            background-color: #c82333;
            color: white;
        }

        .btn {
      font-size: 12px;
      padding: 4px 8px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin: 2px;
      transition: background-color 0.3s ease;
    }
    .btn-edit {
      background-color: #4CAF50;
      color: white;
    }
    .btn-edit:hover {
      background-color: #45a049;
    }
    .btn-delete {
      background-color: #f44336;
      color: white;
    }
    .btn-delete:hover {
      background-color: #da190b;
    }

    button {
    background-color: #007bff;
    color: white;
    border: none;
    font-size: 1.1rem;
    padding: 12px 0;
    width: 100%;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.25s ease;
  }
  button:hover {
    background-color: #0056b3;
  } 
  .btn-volver {
  display: inline-block;
  background-color: #6c757d;
  color: white;
  text-decoration: none;
  padding: 10px 18px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  margin-top: 20px;
  transition: background-color 0.3s ease;
  text-align: center;
}

.btn-volver:hover {
  background-color: #5a6268;
}


</style>
</head>
<body>
  <div class="container">
    <h2>Editar Transacción</h2>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        
    <a href="bienvenida.php" class="btn-volver">↩️ Volver al menú principal</a>
      <label for="fecha">Fecha:</label>
      <input
        type="datetime-local"
        id="fecha"
        name="fecha"
        value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($transaccion['fecha']))) ?>"
        required
      />

      <label for="monto">Monto:</label>
      <input
        type="number"
        id="monto"
        name="monto"
        step="0.01"
        min="0"
        value="<?= htmlspecialchars($transaccion['monto']) ?>"
        required
      />

      <label for="descripcion">Descripción:</label>
      <input
        type="text"
        id="descripcion"
        name="descripcion"
        value="<?= htmlspecialchars($transaccion['descripcion']) ?>"
        required
      />

      <label for="categoria_id">Categoría:</label>
      <select id="categoria_id" name="categoria_id" required>
        <option value="">-- Selecciona una categoría --</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= $categoria['id'] ?>" <?= $categoria['id'] == $transaccion['categoria_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($categoria['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="tipo">Tipo:</label>
      <select id="tipo" name="tipo" required>
        <option value="ingreso" <?= $transaccion['tipo'] === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
        <option value="egreso" <?= $transaccion['tipo'] === 'egreso' ? 'selected' : '' ?>>Egreso</option>
      </select>
    
            <h2 style="text-align: center; margin-top: 20px;"></h2>

      <button class="logout-btn" type="submit">Guardar cambios</button>
      

    </form>
  </div>
</body>
</html>
