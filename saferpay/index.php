<?php

require('../formatconverters/xml2array/xml2array.php');
require('../httpclient/httpclient.php');
require('saferpay.php');

try
{
    // session
    session_start();
    if(!isset($_SESSION['saferpay']))
    {
        $_SESSION['saferpay'] = '';
    }
    
    // get saferpayobject
    $objSaferpay = saferpay::getInstance($_SESSION['saferpay']);

    // get status
    switch($_GET['status'])
    {
        case 'success':
            if($objSaferpay->verfifyPayConfirm($_GET['DATA'], $_GET['SIGNATURE']))
            {
                echo('finished');
                unset($_SESSION['saferpay']);
            }
            break;
        default:
            $strToCallUrl = $objSaferpay->createPayInit(
                array(
                    'ACCOUNTID' => '99867-94913159',
                    'CURRENCY' => 'CHF',
                    'AMOUNT' => 10250,
                    'DESCRIPTION' => sprintf('Bestellnummer %s', 'Or001'),
                    'ORDERID' => 'Or001',
                    'SUCCESSLINK' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?status=success',
                    'FAILLINK' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?status=fail',
                    'BACKLINK' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'LANGID'=> 'CH',
                )
            );
            $_SESSION['saferpay'] = $objSaferpay->getSerializedInstance();
            header("Location: {$strToCallUrl}");
            break;
    }
    
}
catch(Exception $e)
{
    echo $e->getMessage();
    printData($objSaferpay);
}

function printData($arrData, $boolDie = true)
{
    echo '<pre>'; print_r($arrData); echo '</pre>';
    if($boolDie)
    {
        die();
    }
}