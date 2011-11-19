<?php

require('xml2array.php');

$strToParse = '<html><head><title>Testseite</title></head><body><div id="content"><p>Erster Absatz</p><p>Zweiter Absatz</p></div></body></html>';

$xml = new xml2array();

$xml->set($strToParse);
echo htmlspecialchars($xml->getString());
$arrToTest = $xml->getArray();
printData($arrToTest, false);
$xml->set($arrToTest);
echo htmlspecialchars($xml->getString());

function printData($arrData, $boolDie = true)
{
    echo '<pre>'; print_r($arrData); echo '</pre>';
    if($boolDie)
    {
        die();
    }
}