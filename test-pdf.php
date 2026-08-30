<?php
/**
 * test-pdf-save-only.php - SAMO ČUVANJE, BEZ OUTPUT-A
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// 1. UČITAJ TCPDF
// ============================================
$tcpdfFile = __DIR__ . '/tcpdf/tcpdf.php';

if (!file_exists($tcpdfFile)) {
    die("TCPDF nije pronađen!");
}

require_once $tcpdfFile;

if (!class_exists('TCPDF')) {
    die("Klasa TCPDF ne postoji!");
}

// ============================================
// 2. KREIRAJ I SAČUVAJ PDF
// ============================================
try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('Rasprodaja.rs');
    $pdf->SetAuthor('Rasprodaja.rs');
    $pdf->SetTitle('Test PDF');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->AddPage();
    
    $html = '<h1 style="color: #4f46e5;">RASPRODAJA.RS</h1>
             <p>TCPDF uspešno radi!</p>
             <p>Datum: ' . date('d.m.Y H:i') . '</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // ============================================
    // 3. SAČUVAJ PDF (NE PRIKAZUJ)
    // ============================================
    $filename = 'test_' . date('Ymd_His') . '.pdf';
    $filepath = __DIR__ . '/assets/uploads/' . $filename;
    
    // Kreiraj folder
    $dir = dirname($filepath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Sačuvaj PDF (F = File)
    $pdf->Output($filepath, 'F');
    
    // ============================================
    // 4. TEK SADA PRIKAŽI REZULTAT
    // ============================================
    if (file_exists($filepath)) {
        echo "✅ PDF uspešno sačuvan!<br>";
        echo "📄 <a href='/assets/uploads/$filename' target='_blank'>Pogledaj PDF</a><br>";
        echo "Putanja: " . $filepath;
    } else {
        echo "❌ Greška: PDF nije sačuvan!";
    }
    
} catch (Exception $e) {
    echo "❌ Greška: " . $e->getMessage();
}