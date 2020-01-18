<?php

/**
 * QR-Einzahlschein / QR-Rechnung
 * 
 * License: GPL
 * Author: Andreas Butti, andreasbutti@gmail.com
 */


require_once 'qreinzahlschein.php';


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


