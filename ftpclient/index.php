<?php

require('ftpclient.php');


try
{
    $objFTPClient = new FtpClient('ftp://www.dominik-zogg.ch:<password>@dominik-zogg.ch:21');

    printData($objFTPClient);
}
catch(Exception $e)
{
    echo $e->getMessage();
}

function printData($arrData, $boolDie = true)
{
    echo '<pre>'; print_r($arrData); echo '</pre>';
    if($boolDie)
    {
        die();
    }
}