<?php

/**
 * QR-Einzahlschein / QR-Rechnung
 * 
 * License: GPL / Proprietary (contact me)
 * Author: Andreas Butti, andreasbutti@gmail.com
 */


require_once 'qreinzahlschein.php';


$ez = new QrEz();
$ez->getPdf()->SetAuthor('Demo Application');

$ez->setData('iban', 'CH44 3199 9123 0008 8901 2');
$ez->setData('address1', 'Robert Schneider AG');
$ez->setData('address2', 'Rue du Lac 1268');
//$ez->setData('address3', '');
$ez->setData('addressZip', '2501');
$ez->setData('addressCity', 'Biel');
$ez->setData('addressCountry', 'CH');

$ez->setData('reference', '210000000003139471430009017');
$ez->setData('address_sender1', 'Pia-Maria Rutschmann-Schnyder');
$ez->setData('address_sender2', 'Grosse Marktgasse 28');
//$ez->setData('address_sender3', '');
$ez->setData('address_senderZip', '9400');
$ez->setData('address_senderCity', 'Rorschach');
$ez->setData('address_senderCountry', 'CH');

$ez->setData('currency', 'CHF');
$ez->setData('amount', '1234.50');

$ez->setData('message', 'Auftrag vom 15.06.2020');

// Use for what?
//$ez->setData('billinfo', '//S1/10/10201409/11/200701/20/140.000-53/30/102673831/31/200615/32/7.7/33/7.7:139.40/40/0:30');

$ez->setData('av1', 'Name AV1: UV;UltraPay005;12345');
$ez->setData('av2', 'Name AV2: XY;XYService;54321');


$ez->debugGrid = true;
$ez->debugCellBorder = true;
$ez->createQrEz();

$ez->debugGrid = false;
$ez->debugCellBorder = false;

foreach (array('de', 'fr', 'it', 'en') as $lang) {
	$ez->lang = $lang;
	$ez->createQrEz();

	$pdf = $ez->getPdf();
	$pdf->SetFont('LiberationSans', 'B', 20);
	$pdf->SetFont('LiberationSans', 'B', 20);

	$pdf->SetY(120);
	$pdf->Cell(210, 20, 'QR Rechnung', 0, 0, 'C');
	$pdf->Ln(10);

	$pdf->SetFont('LiberationSans', '', 20);
	$pdf->Cell(210, 20, 'Beispiel, 18.01.2020', 0, 0, 'C');
	$pdf->Ln(10);

	$pdf->SetFont('LiberationSans', '', 14);
	$pdf->Cell(210, 20, 'Andreas Butti', 0, 0, 'C');
	$pdf->Ln(7);
	$pdf->Cell(210, 20, 'https://github.com/andreasb242/qreinzahlschein', 0, 0, 'C');
}

$ez->output();


