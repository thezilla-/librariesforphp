<?php

require('httpclient.php');

try
{
    $objHttpClient = new HttpClient('http://dominik-zogg.ch');
    $objRequestAndResponse = $objHttpClient->request('');
    echo $objRequestAndResponse->response->getContent();
    //printData($objRequestAndResponse);
}
catch(Exception $e)
{
    echo $e->getMessage();
}






function printData($array, $boolDie = true)
{
    echo '<pre>'; print_r($array); echo '<pre>';
    if($boolDie)
    {
        die();
    }
}
