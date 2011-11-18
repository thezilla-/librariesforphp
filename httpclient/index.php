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