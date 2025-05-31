<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: bienvenida.php");
    exit;
}

require_once 'conexion.php';

$user_nombre = $_SESSION['usuario'];

// Obtener ID usuario
$queryUser = "SELECT id FROM usuarios WHERE usuario = ?";
$stmtUser = $conexion->prepare($queryUser);
$stmtUser->bind_param("s", $user_nombre);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 0) {
    header("Location: bienvenida.php");
    exit;
}

$user_data = $resultUser->fetch_assoc();
$user_id = $user_data['id'];
$stmtUser->close();

// Sumar ingresos y egresos
$query = "SELECT 
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS total_ingresos,
            SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) AS total_egresos
          FROM transacciones
          WHERE id_usuario = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$total_ingresos = $data['total_ingresos'] ?? 0;
$total_egresos = $data['total_egresos'] ?? 0;
$saldo = $total_ingresos - $total_egresos;

$stmt->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Resumen Financiero</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Aquí tus estilos, igual que en el ejemplo que diste */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0; padding: 0;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; color: #222; }
        .logout-btn {
            background-color: #ff4b5c;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            float: right;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover { background-color: #e04354; }
        .resumen {
            font-size: 1.25rem;
            text-align: center;
            margin-bottom: 35px;
            color: #444;
        }
        canvas#balanceChart {
            display: block;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .botones {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            margin: 0 10px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<div class="container clearfix">
    <a class="logout-btn" href="logout.php">Cerrar sesión</a>
    <h1>Resumen Financiero</h1>

    <div class="resumen">
        <p><strong>Total ingresos:</strong> $<?php echo number_format($total_ingresos, 0, ',', '.'); ?></p>
        <p><strong>Total egresos:</strong> $<?php echo number_format($total_egresos, 0, ',', '.'); ?></p>
        <p><strong>Saldo actual:</strong> $<?php echo number_format($saldo, 0, ',', '.'); ?></p>
    </div>

    <div class="botones">
        <a href="bienvenida.php" class="btn">Volver al menú principal</a>
        <button id="btnGenerarPdf" class="btn">Descargar reporte PDF</button>
    </div>

    <canvas id="balanceChart"></canvas>
</div>

<script>
const ctx = document.getElementById('balanceChart').getContext('2d');

const data = {
    labels: ['Ingresos', 'Egresos'],
    datasets: [{
        label: 'Montos',
        data: [<?php echo $total_ingresos; ?>, <?php echo $total_egresos; ?>],
        backgroundColor: ['#4caf50', '#f44336']
    }]
};

const config = {
    type: 'doughnut',
    data: data,
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Distribución de ingresos y egresos'
            }
        }
    }
};

const myChart = new Chart(ctx, config);

document.getElementById('btnGenerarPdf').addEventListener('click', () => {
    const imageBase64 = myChart.toBase64Image();

    const formData = new FormData();
    formData.append('imgBase64', imageBase64);

    fetch('reporte_con_grafica.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte_financiero_<?php echo $user_nombre; ?>.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
    })
    .catch(err => alert('Error al generar PDF: ' + err));
});
</script>

</body>
</html>
