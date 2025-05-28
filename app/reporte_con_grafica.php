<?php
session_start();
require('../fpdf186/fpdf.php');
require_once 'conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: bienvenida.php");
    exit;
}

$nombre = $_SESSION['usuario'];

// Obtener ID usuario
$queryUser = "SELECT id FROM usuarios WHERE usuario = ?";
$stmtUser = $conexion->prepare($queryUser);
$stmtUser->bind_param("s", $nombre);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows === 0) {
    exit("Usuario no encontrado.");
}
$user_data = $resultUser->fetch_assoc();
$user_id = $user_data['id'];

// Obtener totales
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

$fecha = date('d/m/Y');
$hora = date('H:i');

class PDF extends FPDF {
    // Colores para la tabla
    var $headerColor = [60, 141, 188];
    var $rowFillColor1 = [235, 242, 251];
    var $rowFillColor2 = [255, 255, 255];

    function Header() {
        // Logo
        $this->Image('images/Logo.jpeg', 15, 10, 30);
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(60, 141, 188);
        $this->Cell(0, 15, utf8_decode('SmartFinance - Beowulf'), 0, 1, 'C');

        // Línea decorativa azul
        $this->SetDrawColor(60, 141, 188);
        $this->SetLineWidth(1.5);
        $this->Line(15, 32, 195, 32);
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, utf8_decode('Generado automáticamente por SmartFinance - Beowulf'), 0, 1, 'C');
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }

    function fancyTable($header, $data) {
        // Colores y fuente para el header
        $this->SetFillColor($this->headerColor[0], $this->headerColor[1], $this->headerColor[2]);
        $this->SetTextColor(255);
        $this->SetDrawColor(255, 255, 255);
        $this->SetLineWidth(.3);
        $this->SetFont('Arial', 'B', 13);

        // Anchuras columnas
        $w = [60, 60, 60];

        // Header
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 12, utf8_decode($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Restaurar colores y fuente para filas
        $this->SetFillColor($this->rowFillColor1[0], $this->rowFillColor1[1], $this->rowFillColor1[2]);
        $this->SetTextColor(40, 40, 40);
        $this->SetFont('Arial', '', 12);

        // Datos con relleno alterno
        $fill = false;
        foreach ($data as $row) {
            $this->SetFillColor(
                $fill ? $this->rowFillColor1[0] : $this->rowFillColor2[0],
                $fill ? $this->rowFillColor1[1] : $this->rowFillColor2[1],
                $fill ? $this->rowFillColor1[2] : $this->rowFillColor2[2]
            );
            $this->Cell($w[0], 12, '$' . number_format($row[0], 0, ',', '.'), 'LR', 0, 'C', true);
            $this->Cell($w[1], 12, '$' . number_format($row[1], 0, ',', '.'), 'LR', 0, 'C', true);
            $this->Cell($w[2], 12, '$' . number_format($row[2], 0, ',', '.'), 'LR', 0, 'C', true);
            $this->Ln();
            $fill = !$fill;
        }
        // Línea de cierre
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new PDF();
$pdf->AddPage();

// Título
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 20, utf8_decode('Reporte Financiero Personal'), 0, 1, 'C');
$pdf->Ln(5);

// Datos de usuario y fecha
$pdf->SetFont('Arial', '', 13);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 8, utf8_decode("Usuario: $nombre"), 0, 1, 'L');
$pdf->Cell(0, 8, utf8_decode("Fecha: $fecha   Hora: $hora"), 0, 1, 'L');
$pdf->Ln(10);

// Tabla con datos
$header = ['Total Ingresos', 'Total Egresos', 'Saldo Actual'];
$data = [[$total_ingresos, $total_egresos, $saldo]];
$pdf->fancyTable($header, $data);

$pdf->Ln(15);

// Insertar imagen del gráfico con borde y sombra simulada
if (isset($_POST['imgBase64'])) {
    $imgData = $_POST['imgBase64'];

    // Limpiar base64
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);

    $imgDecoded = base64_decode($imgData);

    // Guardar imagen temporal
    $imgPath = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
    file_put_contents($imgPath, $imgDecoded);

    // Dibujar "sombra" simulada detrás (rectángulo gris)
    $x = 40;
    $y = $pdf->GetY();
    $width = 120;
    $height = 80;
    $pdf->SetDrawColor(160, 160, 160);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Rect($x + 3, $y + 3, $width, $height, 'F');

    // Insertar imagen del gráfico
    $pdf->Image($imgPath, $x, $y, $width, $height);

    unlink($imgPath);
}

$pdf->Ln(90);

// Nota final
$pdf->SetFont('Arial', 'I', 11);
$pdf->SetTextColor(120, 120, 120);
$pdf->MultiCell(0, 8, utf8_decode("Este reporte resume tus finanzas personales con datos actualizados y un gráfico visual para ayudarte a tomar mejores decisiones financieras."));

$pdf->Output('D', "reporte_financiero_$nombre.pdf");
exit;
?>
