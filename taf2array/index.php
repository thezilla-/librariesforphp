<?php

require('taf2array.php');

$strToParse = '{Blg Date=18.01.03 MType=0 Orig=1 {Bk AccId=11050 CAcc=div Flags=1 OpId=81222 PkKey=2002015002 Text="Muster Buchungstext" Type=0 ValNt=1100 }{Bk AccId=23010 CAcc=11050 MkTxB=1 TaxId=frei Text="1. Rate" Type=1 ValBt=1100 }{Bk AccId=84.500 BType=1 CAcc=23010 Text="RNR: 81222 Art: Rate" Type=1 ValNt=1100 }}';

$taf = new taf2array();

$taf->set($strToParse);
printData($taf->getString(), false);
$arrToTest = $taf->getArray();
printData($arrToTest, false);
$taf->set($arrToTest);
printData($taf->getString(), false);

function printData($arrData, $boolDie = true)
{
    echo '<pre>'; print_r($arrData); echo '</pre>';
    if($boolDie)
    {
        die();
    }
}