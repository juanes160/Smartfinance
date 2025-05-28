<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "smartfinance");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener usuario y su id
$usuario = $_SESSION['usuario'];
$stmt_user = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
$stmt_user->bind_param("s", $usuario);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$row_user = $res_user->fetch_assoc();
$id_usuario = $row_user['id'] ?? null;
$stmt_user->close();

if (!$id_usuario) {
    session_destroy();
    header("Location: index.html");
    exit();
}

// Cargar categorías
$sql = "SELECT * FROM categorias ORDER BY nombre ASC";
$resultado = $conexion->query($sql);

$categorias = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $categorias[] = $fila;
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo'] ?? '';
    $monto = floatval($_POST['monto'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = intval($_POST['categoria_id'] ?? 0);

    // Validar campos
    $ids_validos = array_column($categorias, 'id');
    if (!in_array($tipo, ['ingreso', 'egreso'])) {
        $error = "Tipo inválido.";
    } elseif ($monto <= 0) {
        $error = "Monto debe ser mayor a cero.";
    } elseif (empty($descripcion)) {
        $error = "La descripción no puede estar vacía.";
    } elseif (!in_array($categoria_id, $ids_validos)) {
        $error = "Categoría inválida.";
    } else {
        // Insertar la transacción con categoría
        $stmt = $conexion->prepare("INSERT INTO transacciones (id_usuario, tipo, monto, descripcion, categoria_id) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = "Error en la preparación de la consulta.";
        } else {
            $stmt->bind_param("isdsi", $id_usuario, $tipo, $monto, $descripcion, $categoria_id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Error al guardar la transacción.";
                $stmt->close();
            }
        }
    }
}

// Obtener totales
$sql_ingresos = "SELECT SUM(monto) AS total FROM transacciones WHERE id_usuario = ? AND tipo = 'ingreso'";
$stmt_ingresos = $conexion->prepare($sql_ingresos);
$stmt_ingresos->bind_param("i", $id_usuario);
$stmt_ingresos->execute();
$res_ingresos = $stmt_ingresos->get_result()->fetch_assoc();
$stmt_ingresos->close();

$sql_egresos = "SELECT SUM(monto) AS total FROM transacciones WHERE id_usuario = ? AND tipo = 'egreso'";
$stmt_egresos = $conexion->prepare($sql_egresos);
$stmt_egresos->bind_param("i", $id_usuario);
$stmt_egresos->execute();
$res_egresos = $stmt_egresos->get_result()->fetch_assoc();
$stmt_egresos->close();

$ingresos = $res_ingresos['total'] ?? 0;
$egresos = $res_egresos['total'] ?? 0;
$saldo = $ingresos - $egresos;

// Obtener transacciones con categorías
$sql_transacciones = "
    SELECT t.id, t.fecha, t.tipo, t.monto, t.descripcion, c.nombre AS categoria_nombre
    FROM transacciones t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    WHERE t.id_usuario = ?
    ORDER BY t.fecha DESC
";
$stmt_transacciones = $conexion->prepare($sql_transacciones);
$stmt_transacciones->bind_param("i", $id_usuario);
$stmt_transacciones->execute();
$res_transacciones = $stmt_transacciones->get_result();

$transacciones = [];
if ($res_transacciones && $res_transacciones->num_rows > 0) {
    while ($fila = $res_transacciones->fetch_assoc()) {
        $transacciones[] = $fila; // Aquí agregas el id con toda la fila
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Panel Financiero</title>
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


            </style>

            <div id="consejos-container"></div>

            <script>
            const consejos = [
            "Controla tus gastos diarios para evitar gastos innecesarios.",
            "Presupuesta tus ingresos y respétalos para no excederte.",
            "Aparta un porcentaje para ahorro, aunque sea pequeño.",
            "Evita endeudarte con intereses altos, paga tus deudas.",
            "Compara precios antes de comprar para ahorrar dinero.",
            "Invierte tiempo en educarte sobre finanzas personales.",
            "Crea un fondo de emergencia con al menos 3 meses de gastos."
            ];

            const container = document.getElementById('consejos-container');

            function crearConsejo(texto) {
            const div = document.createElement('div');
            div.classList.add('consejo-flotante');
            div.innerHTML = `<h4>💡 Consejo</h4><p>${texto}</p>`;
            // Cerrar consejo al hacer clic
            div.onclick = () => div.remove();
            return div;
            }

            // Mostrar 3 consejos flotantes al cargar, uno cada 2 segundos
            let index = 0;
            function mostrarConsejo() {
            if (index >= consejos.length) return;
            const consejo = crearConsejo(consejos[index]);
            container.appendChild(consejo);
            index++;
            setTimeout(mostrarConsejo, 20000); // Mostrar cada 20 segundos
            }

            mostrarConsejo();
            </script>

</head>
<body>

<header>
    <h1>Bienvenido, <?php echo htmlspecialchars($usuario); ?> 👋</h1>
    <a class="logout-btn" href="dashboard.php">Resumen Financiero</a>
    <a class="logout-btn" href="exportar_csv.php">Exportar historial (Excel)</a>
    <a class="logout-btn" href="logout.php">Cerrar sesión</a>   
</form>

</header>

<div class="container">

    <div class="balances">
        
        <div class="balance-box" title="Saldo total actual">
            <strong>Saldo actual</strong>
            <div class="amount">$<?php echo number_format($saldo, 2); ?></div>
        </div>
        <div class="balance-box" title="Total de ingresos">
            <strong>Ingresos</strong>
            <div class="amount">$<?php echo number_format($ingresos, 2); ?></div>
        </div>
        <div class="balance-box" title="Total de egresos">
            <strong>Egresos</strong>
            <div class="amount">$<?php echo number_format($egresos, 2); ?></div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message" style="display: block; text-align: center; margin-bottom: 20px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <h2>Registrar Movimiento</h2>
<form id="movimientoForm" method="POST" novalidate>
    <label for="tipo">Tipo:</label>
    <select name="tipo" id="tipo" required>
        <option value="" disabled selected>Seleccione tipo</option>
        <option value="ingreso">Ingreso</option>
        <option value="egreso">Egreso</option>
    </select>
    <div id="tipoError" class="error-message">Por favor seleccione un tipo válido.</div>

    <label for="monto">Monto:</label>
    <input type="number" name="monto" id="monto" step="0.01" required />
    <div id="montoError" class="error-message">El monto debe ser un número positivo.</div>

    <label for="descripcion">Descripción:</label>
    <input type="text" name="descripcion" id="descripcion" maxlength="255" required />
    <div id="descripcionError" class="error-message">Por favor escriba una descripción.</div>

    <label for="categoria">Categoría:</label>
    <select id="categoria" name="categoria_id" required>
        <option value="">-- Seleccione --</option>
        <?php foreach($categorias as $categoria): ?>
            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Guardar Movimiento" />
</form>

<!-- Separador con margen para bajar el formulario de agregar categoría -->
<div style="margin-top: 30px;">
    <h2>Agregar Nueva Categoría de ingreso o egreso (Ropa, Aseo, Etc)</h2>
    <form method="POST" action="agregar_categoria.php">
        <input type="text" name="nombre_categoria" placeholder="Nueva categoría" required>
        <input class="logout-btn" type="submit" value="Agregar categoría">
    </form>
</div>


<h2>Historial de Transacciones</h2>
<table>
  <thead>
    <tr>
      <th>Fecha y hora</th>
      <th>Monto</th>
      <th>Descripción</th>
      <th>Categoría</th>
      <th>tipo</th>    
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($transacciones as $transaccion): ?>
        <tr>
            <td><?= htmlspecialchars($transaccion['fecha']) ?></td>
            <td>$<?= number_format($transaccion['monto'], 2) ?></td>
            <td><?= htmlspecialchars($transaccion['descripcion']) ?></td>
            <td><?= htmlspecialchars($transaccion['categoria_nombre'] ?? 'Sin categoría') ?></td>
            <td><?= htmlspecialchars($transaccion['tipo']) ?></td>
            <td>

            
            <a href="editar.php?id=<?= urlencode($transaccion['id']) ?>" class="btn btn-edit">✏️ Editar</a>
            <a href="eliminar.php?id=<?= urlencode($transaccion['id']) ?>" class="btn btn-delete" onclick="return confirm('¿Seguro que quieres eliminar esta transacción?');">🗑️ Eliminar</a>


            </td>

        </tr>
    <?php endforeach; ?>
</tbody>

</table>

    
</div>


<script>
document.getElementById("movimientoForm").addEventListener("submit", function(e) {
    const tipo = document.getElementById("tipo");
    const monto = document.getElementById("monto");
    const descripcion = document.getElementById("descripcion");

    let error = false;

    if (tipo.value === "") {
        document.getElementById("tipoError").style.display = "block";
        error = true;
    } else {
        document.getElementById("tipoError").style.display = "none";
    }

    if (!monto.value || monto.value <= 0) {
        document.getElementById("montoError").style.display = "block";
        error = true;
    } else {
        document.getElementById("montoError").style.display = "none";
    }

    if (!descripcion.value.trim()) {
        document.getElementById("descripcionError").style.display = "block";
        error = true;
    } else {
        document.getElementById("descripcionError").style.display = "none";
    }

    if (error) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
<?php $conexion->close(); ?>
