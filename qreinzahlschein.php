<?php

/**
 * QR-Einzahlschein / QR-Rechnung
 * 
 * License: GPL
 * Author: Andreas Butti, andreasbutti@gmail.com
 */

require_once 'fpdf/fpdf.php';
require_once 'qr/qrcode.class.php';
require_once 'text.php';


/**
 * Size constants
 */
define('A4_W', 210);
define('A4_H', 297);
define('EZ_H', 105);

// mm / PDF Points
define('SCALE_FACTOR', 72/25.4);


/**
 * Class to generate single sided QR Rechnung
 */
class QrEz {

	/**
	 * PDF
	 */
	protected $pdf;
	
	/**
	 * Grid type:
	 * 'line': Draw a simple line, where the perforation should be
	 * 'scissor' Draw the official scissor TODO
	 * everything else: Draw nothing
	 */
	public $grid = 'line';
	
	/**
	 * Print Debug grid from Style Guide Deutsch.pdf, Page 6
	 *
	 * Only for debugging purpose
	 */
	public $debugGrid = false;
	
	/**
	 * Print a border on all cells
	 */
	public $debugCellBorder = false;

	/**
	 * Current language: de, fr, it, en
	 */
	public $lang = 'de';

	/**
	 * Current cell width (depending on the current part)
	 */
	protected $cellWidth;

	/**
	 * Current cell height, depending on the font size
	 */
	protected $cellHeight;

	/**
	 * Current cell X on newline (depending on the current part)
	 */
	protected $cellX;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->pdf = new FPDF('P', 'mm', 'A4');
		
		// Load custom fonts
		$this->pdf->AddFont('LiberationSans', '', 'LiberationSans-Regular.php');
		$this->pdf->AddFont('LiberationSans', 'B', 'LiberationSans-Bold.php');
		
		$this->pdf->SetCreator('andreasb242/qreinzahlschein');
	}
	
	/**
	 * Create QR Code Page
	 */
	public function createQrEz() {
		$this->pdf->AddPage();
		$this->pdf->SetMargins(0, 0, 0); 
		$this->pdf->SetAutoPageBreak(false);
		
		if ($this->debugGrid) {
			$this->printDebugGrid();
		}
		
		if ($this->grid == 'line') {
			$this->pdf->SetDrawColor(0x88, 0x88, 0x88);

			// Top line
			$this->pdf->Line(0, A4_H - EZ_H, A4_W, A4_H - EZ_H);

			// Center line
			$this->pdf->Line(62, A4_H - EZ_H, 62, A4_H);
		}

		$qrcode = new QRcode('your message here', 'H'); // error level : L, M, Q, H
		$qrcode->disableBorder();
		$qrcode->displayFPDF($this->pdf, 67, A4_H - EZ_H + 17, 46);

		// used for debugCellBorder
		$this->pdf->SetDrawColor(0xff, 0x00, 0x00);

		// Print Data, see «Style Guide Deutsch.pdf», Page 18
		$this->printReceipt();
		$this->printPayment();
	}
	
	/**
	 * Print receipt part
	 */
	protected function printReceipt() {
		$this->pdf->SetY(A4_H - EZ_H + 5);
		
		$this->cellWidth = 52;
		$this->cellX = 5;
		$this->pdf->SetX($this->cellX);

		$this->printText('receipt', '%H');

		$this->paddingOffset(12);
		$this->printText('account', '%1');
		$this->printText('CH44 3199 9123 0008 8901 2', 'T');
		$this->printText('Robert Schneider AG');
		$this->printText('Rue du Lac 1268');
		$this->printText('2501 Biel');
		// Padding 9pt see «Style Guide Deutsch.pdf», Page 15
		$this->paddingOffset('+9');

		$this->printText('reference', '%1');
		$this->printText('21 00000 00003 13947 14300 09017', 'T');
		$this->paddingOffset('+9');

		$this->printText('payedBy', '%1');
		$this->printText('Pia-Maria Rutschmann-Schnyder', 'T');
		$this->printText('Grosse Marktgasse 28');
		$this->printText('9400 Rorschach');
		$this->paddingOffset('+9');

		$this->cellWidth = 15;
		$this->paddingOffset(68);

		$this->printText('currency', '%1');
		$this->printText('CHF', 'T');

		$this->cellWidth = 30;
		$this->cellX = 20;
		$this->paddingOffset(68);

		$this->cellX = 20;
		$this->printText('amount', '%1');
		$this->printText('2 500.25', 'T');
	}

	/**
	 * Print QR/Payment part
	 */
	protected function printPayment() {
		$this->pdf->SetY(A4_H - EZ_H + 5);
		
		$this->cellWidth = 46;
		$this->cellX = 67;
		$this->pdf->SetX($this->cellX);

		$this->printText('paymentPart', '%H');
	}

	/**
	 * Print a language dependent text or a value
	 *
	 * @param $text Text to print
	 * @param $flags '%' for Placeholder Text
	 *               'H' Header Text
	 *               '1' Subheader 1
	 *               'T' Text
	 */
	protected function printText($text, $flags = '') {
		global $qrTexts;

		if (strpos($flags, '%') !== false) {
			$lang = $qrTexts[$this->lang];
			$text = $lang[$text];
		}

		if (strpos($flags, 'H') !== false) {
			$this->pdf->SetFont('LiberationSans', 'B', 11);
			$this->cellHeight = 11 / SCALE_FACTOR;
		}
		if (strpos($flags, '1') !== false) {
			$this->pdf->SetFont('LiberationSans', 'B', 6);
			$this->cellHeight = 6 / SCALE_FACTOR;
		}
		if (strpos($flags, 'T') !== false) {
			$this->pdf->SetFont('LiberationSans', '', 8);
			$this->cellHeight = 8 / SCALE_FACTOR;
		}

		$border = 0;
		if ($this->debugCellBorder) {
			$border = 1;
		}
		
		$text = utf8_decode($text);
		$this->pdf->Cell($this->cellWidth, $this->cellHeight, $text, $border);

		$this->pdf->SetY($this->pdf->GetY() + $this->cellHeight);
		$this->pdf->SetX($this->cellX);
	}

	/**
	 * Add space, move to specific position
	 *
	 * @param $pos Offset within EZ, int to position from top of Form (mm)
	 *             string starting with '+', to add space (pt)
	 */
	protected function paddingOffset($pos) {
		if (is_string($pos)) {
			if (substr($pos, 0, 1) == '+') {
				$y = (int)substr($pos, 1);
				$this->pdf->SetY($this->pdf->GetY() + $y / SCALE_FACTOR);
			} else {
				die('Invalid position: ' . $pos);
			}
		} else {
			$this->pdf->SetY(A4_H - EZ_H + $pos);
		}
	
		$this->pdf->SetX($this->cellX);
	}

	/**
	 * Print Debug grid from «Style Guide Deutsch.pdf», Page 6
	 */
	public function printDebugGrid() {
		$this->pdf->SetDrawColor(0x75, 0xbe, 0xeb);
		$this->pdf->SetFillColor(0xcc, 0xef, 0xfc);
		
		$this->pdf->SetX(0);
		// Top Padding
		$this->pdf->SetY(A4_H - EZ_H);
		$this->pdf->Cell(A4_W, 5, '', false, 0, '', true);

		// Bottom Padding
		$this->pdf->SetY(A4_H - 5);
		$this->pdf->Cell(A4_W, 5, '', false, 0, '', true);

		// Vertical Padding
		$this->pdf->SetY(A4_H - EZ_H);
		
		// Left padding
		$this->pdf->SetX(0);
		$this->pdf->Cell(5, EZ_H, '', false, 0, '', true);

		// Right padding
		$this->pdf->SetX(A4_W - 5);
		$this->pdf->Cell(5, EZ_H, '', false, 0, '', true);

		// Center padding (Receipt / QR Code)
		$this->pdf->SetX(62 - 5);
		$this->pdf->Cell(10, EZ_H, '', false, 0, '', true);

		// Center line
		$this->pdf->Line(62, A4_H - EZ_H, 62, A4_H);

		$topY = A4_H - EZ_H + 5;
		// Receipt seperator lines
		foreach (array(7, 56, 14) as $y) {
			$topY += $y;
			$this->pdf->Line(5, $topY, 57, $topY);
		}
		
		// Right QR Part
		$this->pdf->Line(118, A4_H - EZ_H + 5, 118, A4_H - 15);
		$this->pdf->Line(67, A4_H - 15, A4_W - 5, A4_H - 15);

		$qrY = array(A4_H - EZ_H + 12, A4_H - EZ_H + 12 + 51);
		foreach ($qrY as $y) {
			$this->pdf->SetY($y);
			$this->pdf->SetX(67);
			$this->pdf->Cell(46, 5, '', false, 0, '', true);
		}

		$this->pdf->SetY($qrY[0]);
		$this->pdf->SetX(67 + 46);
		$this->pdf->Cell(5, 56, '', false, 0, '', true);
	}

	/**
	 * Get PDF to add custom content
	 */
	public function getPdf() {
		return $this->pdf;
	}

	/**
	 * Output the PDF
	 */
	public function output() {
		$this->pdf->Output();
	}
}

$ez = new QrEz();
$ez->getPdf()->SetAuthor('Demo Application');
$ez->debugGrid = true;
$ez->debugCellBorder = true;
$ez->createQrEz();

$ez->debugGrid = false;
$ez->debugCellBorder = false;

foreach (array('de', 'fr', 'it', 'en') as $lang) {
	$ez->lang = $lang;
	$ez->createQrEz();
}

$ez->output();

