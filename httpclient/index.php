<?php

require('httpclient.php');

try
{
    $objHttpClient = new HttpClient('http://dominik-zogg.ch');
    $objRequestAndResponse = $objHttpClient->request('suche.html?keywords=test');
    echo $objRequestAndResponse->response->getContent();
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