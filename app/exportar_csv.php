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

$usuario = $_SESSION['usuario'];

// Obtener id_usuario
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

$sql = "
    SELECT t.fecha, t.tipo, t.monto, t.descripcion, c.nombre AS categoria_nombre
    FROM transacciones t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    WHERE t.id_usuario = ?
    ORDER BY t.fecha DESC
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

// Cabeceras para que el navegador descargue como Excel
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=historial_movimientos.xls");
header("Cache-Control: max-age=0");

echo '<html><head><meta charset="UTF-8">';
echo '<style>
    table {
        border-collapse: collapse;
        width: 100%;
        font-family: Arial, sans-serif;
    }
    th {
        background-color: #16a085;
        color: white;
        padding: 10px;
        text-align: left;
    }
    td {
        padding: 10px;
        border-bottom: 1px solid #ccc;
    }
    tr:nth-child(even) {background-color: #f2f2f2;}
</style>';
echo '</head><body>';

echo '<h2 style="text-align:center; color:#2c3e50;">Historial de Movimientos - Smartfinance - Beowulf</h2>';
echo '<table>';
echo '<thead><tr>';
echo '<th>Fecha</th>';
echo '<th>Tipo</th>';
echo '<th>Monto</th>';
echo '<th>Descripción</th>';
echo '<th>Categoría</th>';
echo '</tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    $fecha = date('Y-m-d H:i', strtotime($row['fecha']));
    $tipo = ucfirst($row['tipo']);
    $monto = number_format($row['monto'], 2, '.', ',');
    $descripcion = htmlspecialchars($row['descripcion']);
    $categoria = $row['categoria_nombre'] ?? 'Sin categoría';

    echo "<tr>";
    echo "<td>$fecha</td>";
    echo "<td>$tipo</td>";
    echo "<td>$monto</td>";
    echo "<td>$descripcion</td>";
    echo "<td>$categoria</td>";
    echo "</tr>";
}

echo '</tbody></table>';
echo '</body></html>';
exit();
?>
