<?php

/**
 * QR-Einzahlschein / QR-Rechnung
 * 
 * License: GPL
 * Author: Andreas Butti, andreasbutti@gmail.com
 */

require_once 'fpdf/fpdf.php';

/**
 * Size constants
 */
define('A4_W', 210);
define('A4_H', 297);
define('EZ_H', 105);


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
	public $grid = '';
	
	/**
	 * Print Debug grid from Style Guide Deutsch.pdf, Page 6
	 *
	 * Only for debugging purpose
	 */
	public $debugGrid = false;

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
/*
		$this->pdf->SetFont('LiberationSans', '', 14);
		$this->pdf->Cell(40, 10, 'Hello World!');
		$this->pdf->SetFont('LiberationSans', 'B', 14);
		$this->pdf->Cell(40, 50, 'Hello World!');*/
	}

	/**
	 * Print Debug grid from Style Guide Deutsch.pdf, Page 6
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

		$qrY = array(A4_H - EZ_H + 17, A4_H - EZ_H + 17 + 46);
		foreach ($qrY as $y) {
			$this->pdf->SetY($y);
			$this->pdf->SetX(67);
			$this->pdf->Cell(46, 5, '', false, 0, '', true);
		}

		$this->pdf->SetY($qrY[0]);
		$this->pdf->SetX(67 + 46) + 46;
		$this->pdf->Cell(5, 51, '', false, 0, '', true);
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
$ez->createQrEz();

$ez->debugGrid = false;
$ez->createQrEz();

$ez->output();

