<?php
// 1. Include FPDF library
require_once __DIR__ . '/temp/fpdf.php';
include 'connect.php';

// 2. Get user_id from query string
if (empty($_GET['user_id'])) {
    die("User ID is required");
}
$user_id = (int)$_GET['user_id'];

// 3. Fetch user record (no photo_path column)
$sql  = "SELECT username, serial_no, role FROM users WHERE user_id = $user_id";
$res  = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) !== 1) {
    die("User not found");
}
$user = mysqli_fetch_assoc($res);

// 4. Create FPDF at custom size: 85.60×53.98 mm
$pdf = new FPDF('L', 'mm', [85.60, 53.98]);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// 5. Draw border rectangle (with slight inset)
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.5);
$pdf->Rect(1, 1, 83.6, 51.0);

// 6. Insert logo (top-left)
$logo = __DIR__ . '/logo.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 4, 4, 15, 15);
}

// 7. (Removed user-photo block since no photo_path in DB)

// 8. Write text fields
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY(4, 22);
$pdf->Cell(0, 4, 'Name: ' . $user['username'], 0, 1);
$pdf->SetX(4);
$pdf->Cell(0, 4, 'Serial No: ' . $user['serial_no'], 0, 1);
$pdf->SetX(4);
$pdf->Cell(0, 4, 'Role: ' . ucfirst($user['role']), 0, 1);
$pdf->SetX(4);
$pdf->Cell(0, 4, 'Issued: ' . date('Y-m-d'), 0, 1);

// 9. Output inline
$pdf->Output('I', 'IDCard_' . $user['serial_no'] . '.pdf');
