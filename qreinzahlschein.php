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

// Newline char, UTF-8 => more than one bye
define('NEWLINE_CHAR', '¶');

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
	 * Format data, different for Receipt / Payment part
	 * (except header, for both the same)
	 */
	protected $format = array('H' => array('flag' => 'b', 'size' => 11));

	/**
	 * User data
	 */
	protected $data = array();

	/**
	 * Formatter for specific fields, only for printing, not for QR Code
	 */
	protected $printFormat = array();

	/**
	 * Placeholder / Contents of the QR Code:
	 * newline is ignored, use ¶ for Newline
	 * each line is trimmed,
	 * use %placeholder to get data out of the $this->data array
	 */
	protected $qrCodeFields = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->pdf = new FPDF('P', 'mm', 'A4');

		$this->qrCodeFields = file_get_contents('qrfields.txt');

		// Load custom fonts
		$this->pdf->AddFont('LiberationSans', '', 'LiberationSans-Regular.php');
		$this->pdf->AddFont('LiberationSans', 'B', 'LiberationSans-Bold.php');

		$this->pdf->SetCreator('andreasb242/qreinzahlschein');


		$this->printFormat['reference'] = function($val) {
			$t = '';
			$len = strlen($val);

			for ($i = 0; $i < $len; $i++) {
				$c = $val[$len - $i - 1];
				
				if ($i % 5 == 0) {
					$t = ' ' . $t;
				}
				
				$t = $c . $t;
			}
			
			return $t;
		};

		$this->printFormat['amount'] = function($val) {
			return number_format($val, 2, '.', ' ');
		};
	}
	
	/**
	 * Set data String
	 */
	public function setData($key, $value) {
		$this->data[$key] = $value;
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

		$qrdata = $this->createQrData();

		// Error Level M: «ig-qr-bill-de.pdf», Point 5.1, Page 35
		$qrcode = new QRcode($qrdata, 'M'); // error level : L, M, Q, H
		$qrcode->disableBorder();
		
		$qrWidth = 46;
		$qrX = 67;
		$qrY = A4_H - EZ_H + 17;
		$qrcode->displayFPDF($this->pdf, $qrX, $qrY, $qrWidth);

		// Print swiss cross over QR Code
		// White Background
		$crossSize = 7;
		$crossX = $qrX + ($qrWidth - $crossSize) / 2;
		$crossY = $qrY + ($qrWidth - $crossSize) / 2;
		$this->pdf->SetFillColor(0xFF, 0xFF, 0xFF);
		$this->pdf->SetY($crossY);
		$this->pdf->SetX($crossX);
		$this->pdf->Cell($crossSize, $crossSize, '', false, 0, '', true);

		// Black flag
		$this->pdf->SetFillColor(0x00, 0x00, 0x00);
		$crossSize = 6;
		$crossX = $qrX + ($qrWidth - $crossSize) / 2;
		$crossY = $qrY + ($qrWidth - $crossSize) / 2;
		$this->pdf->SetY($crossY);
		$this->pdf->SetX($crossX);
		$this->pdf->Cell($crossSize, $crossSize, '', false, 0, '', true);

		// White Cross
		$this->pdf->SetFillColor(0xFF, 0xFF, 0xFF);
		$crossSizeX = 3.9;
		$crossSizeY = 1.12;
		$crossX = $qrX + ($qrWidth - $crossSizeX) / 2;
		$crossY = $qrY + ($qrWidth - $crossSizeY) / 2 - 0.13;
		$this->pdf->SetY($crossY);
		$this->pdf->SetX($crossX);
		$this->pdf->Cell($crossSizeX, $crossSizeY, '', false, 0, '', true);
		$crossSizeX = 1.12;
		$crossSizeY = 3.9;
		$crossX = $qrX + ($qrWidth - $crossSizeX) / 2;
		$crossY = $qrY + ($qrWidth - $crossSizeY) / 2 - 0.13;
		$this->pdf->SetY($crossY);
		$this->pdf->SetX($crossX);
		$this->pdf->Cell($crossSizeX, $crossSizeY, '', false, 0, '', true);


		// used for debugCellBorder
		$this->pdf->SetDrawColor(0xff, 0x00, 0x00);

		// Print Data, see «Style Guide Deutsch.pdf», Page 18
		$this->printReceipt();
		$this->printPayment();
	}

	/**
	 * Create a QR Code String
	 */
	protected function createQrData() {
		$t = '';
		foreach(explode("\n", $this->qrCodeFields) as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			if (substr($line, 0, 1) == '#') {
				continue;
			}

			$newline = false;			
			if (substr($line, -strlen(NEWLINE_CHAR)) == NEWLINE_CHAR) {
				$newline = true;
				
				$line = substr($line, 0, -strlen(NEWLINE_CHAR));
			}

			if (substr($line, 0, 1) == '%') {
				$d = '';
				$key = trim(substr($line, 1));
				
				if (isset($this->data[$key])) {
					$t .= $this->data[$key];
				}
			} else {
				$t .= $line;
			}

			if ($newline) {
				$t .= "\n";
			}
		}

		return $t;
	}

	/**
	 * Print Address and Reference
	 */
	protected function printAddrRef() {
		$this->printText('%account', '1');
		$this->printText('iban', 'T');
		$this->printText('address1');
		$this->printText('address2');
		$this->printText('address3');
		$this->printText('address4');
		// Padding 9pt see «Style Guide Deutsch.pdf», Page 15
		$this->paddingOffset('+9');

		$this->printText('%reference', '1');
		
		$this->printText('reference', 'T');
		$this->paddingOffset('+9');

		$this->printText('%payedBy', '1');
		$this->printText('address_sender1', 'T');
		$this->printText('address_sender2');
		$this->printText('address_sender3');
		$this->printText('address_sender4');
		$this->paddingOffset('+9');
	}

	/**
	 * Print receipt part
	 */
	protected function printReceipt() {
		// Font definitions for Receipt
		$this->format['1'] = array('flag' => 'b', 'size' => 6);
		$this->format['1r'] = array('flag' => 'b', 'size' => 6, 'align' => 'R');
		$this->format['T'] = array('flag' => '', 'size' => 8);

		// Start at top
		$this->pdf->SetY(A4_H - EZ_H + 5);

		// Set cell size and position
		$this->cellWidth = 52;
		$this->cellX = 5;
		$this->pdf->SetX($this->cellX);

		// Print header
		$this->printText('%receipt', 'H');

		$this->paddingOffset(12);
		
		// Print main data
		$this->printAddrRef();

		// Print currency and amount
		$this->cellWidth = 15;
		$this->paddingOffset(68);

		$this->printText('%currency', '1');
		$this->printText('currency', 'T');

		$this->cellWidth = 30;
		$this->cellX = 20;
		$this->paddingOffset(68);

		$this->printText('%amount', '1');
		$this->printText('amount', 'T');
		
		// Acceptance Point Header
		$this->cellX = 5;
		$this->cellWidth = 52;
		$this->paddingOffset(82);
		$this->printText('%acceptancePoint', '1r');
	}

	/**
	 * Print QR/Payment part
	 */
	protected function printPayment() {
		// Font definitions for Payment part
		$this->format['1'] = array('flag' => 'b', 'size' => 8);
		$this->format['T'] = array('flag' => '', 'size' => 10);

		// Start at top
		$this->pdf->SetY(A4_H - EZ_H + 5);
		
		// Set cell size and position
		$this->cellWidth = 46;
		$this->cellX = 67;
		$this->pdf->SetX($this->cellX);

		// Print header
		$this->printText('%paymentPart', 'H');


		// Print main data
		// Start at top, right of header
		$this->pdf->SetY(A4_H - EZ_H + 5);
		$this->cellWidth = 87;
		$this->cellX = 118;
		$this->pdf->SetX($this->cellX);
		$this->printAddrRef();


		// Print currency and amount
		$this->cellWidth = 19;
		$this->cellX = 67;
		$this->paddingOffset(68);

		$this->printText('%currency', '1');
		$this->printText('currency', 'T');

		$this->cellWidth = 30;
		$this->cellX = 86;
		$this->paddingOffset(68);

		$this->printText('%amount', '1');
		$this->printText('amount', 'T');
	}

	/**
	 * Print a language dependent text or a value
	 *
	 * @param $textid Text ID to print, starting with % then it's a language text
	 * @param $format 'H' Header Text
	 *                '1' Subheader
	 *                'T' Text
	 *                format is defined in $this->format
	 */
	protected function printText($textId, $format = '') {
		global $qrTexts;

		$text = '';
		if (substr($textId, 0, 1) == '%') {
			$lang = $qrTexts[$this->lang];
			$text = $lang[substr($textId, 1)];
		} else {
			if (isset($this->data[$textId])) {
				$text = $this->data[$textId];
				
				if (isset($this->printFormat[$textId])) {
					$text = $this->printFormat[$textId]($text);
				}
				
			} else {
				return;
			}
		}

		$align = 'L';
		if (isset($this->format[$format])) {
			$formatInfo = $this->format[$format];
			$this->pdf->SetFont('LiberationSans', $formatInfo['flag'], $formatInfo['size']);
			$this->cellHeight = $formatInfo['size'] / SCALE_FACTOR;
			
			if (isset($formatInfo['align'])) {
				$align = $formatInfo['align'];
			}
		}

		$border = 0;
		if ($this->debugCellBorder) {
			$border = 1;
		}
		
		$text = utf8_decode($text);
		$this->pdf->Cell($this->cellWidth, $this->cellHeight, $text, $border, 0, $align);

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

$ez->setData('iban', 'CH44 3199 9123 0008 8901 2');
$ez->setData('address1', 'Robert Schneider AG');
//$ez->setData('address2', '');
$ez->setData('address3', 'Rue du Lac 1268');
$ez->setData('address4', '2501 Biel');

$ez->setData('reference', '210000000003139471430009017');
$ez->setData('address_sender1', 'Pia-Maria Rutschmann-Schnyder');
$ez->setData('address_sender2', 'Grosse Marktgasse 28');
//$ez->setData('address_sender3', '');
$ez->setData('address_sender4', '9400 Rorschach');

$ez->setData('currency', 'CHF');
$ez->setData('amount', '1234.50');

$ez->setData('message', 'Auftrag vom 15.06.2020');
$ez->setData('billinfo', '//S1/10/10201409/11/200701/20/140.000-53/30/102673831/31/200615/32/7.7/33/7.7:139.40/40/0:30');

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
}

$ez->output();

