<?php

/**
 *
 * Distributed under the GNU Lesser General Public License (LGPL v3)
 * (http://www.gnu.org/licenses/lgpl.html)
 * This program is distributed in the hope that it will be useful -
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @author Dominik Zogg <dominik.zogg@gmail.com>
 * @copyright Copyright (c) 2011, Dominik Zogg
 *
 */

require('httprequest.php');
require('httpresponse.php');

class HttpClient
{
    /**
     * @var boolean $_boolSsl ssl or not
     */
    protected $_boolSsl = false;

    /**
     * @var string $_strHost the wished host
     */
    protected $_strHost = '';

    /**
     *  var integer $_intPort the port of the server
     */
    protected $_intPort = 80;

    /**
     * @var integer $_intConnectionTimeout seconds to wait for etablishing a connection
     */
    protected $_intConnectionTimeout = 0;

    /**
     * @var resource $_resConnection connection resource
     */
    protected $_resConnection = null;
    
    /**
     * @var array $_arrCoookies all cookies
     */
    protected $_arrCoookies = array();

    /**
     * @var array $_arrRequestObjects all done requests
     */
    protected $_arrRequestObjects = array();

    /**
     * @var array $_arrResponseObjects all get responses
     */
    protected $_arrResponseObjects = array();

    /**
     * __construct
     * @param string $strUrl the url to the server
     * @param integer $intConnectionTimeout connection timeout
     */
    public function __construct($strUrl, $intConnectionTimeout = 10)
    {
        $this->_setProperties($strUrl, $intConnectionTimeout);
        $this->_connect();
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->_resConnection = null;
    }

    /**
     * _setProperties
     * @param string $strUrl the url to the server
     * @param integer $intConnectionTimeout connection timeout
     */
    protected function _setProperties($strUrl, $intConnectionTimeout)
    {
        // parse the url
        $arrParsedUrl = @parse_url($strUrl);
        if($arrParsedUrl === false)
        {
            throw new Exception('Invalid url given!');
        }

        // check if ssl is required
        $this->_boolSsl = $arrParsedUrl['scheme'] == 'https' ? true : false;

        // set host
        $this->_strHost = $arrParsedUrl['host'];

        // set the port if is given, else use port based on ssl is required or not
        if(isset($arrParsedUrl['port']))
        {
            $this->_intPort = $arrParsedUrl['port'];
        }
        elseif($this->_boolSsl)
        {
            $this->_intPort = 443;
        }

        // set connection timeout
        $this->_intConnectionTimeout = $intConnectionTimeout;
    }

    /**
     * _connect
     */
    protected function _connect()
    {
        $strHost = $this->_boolSsl ? 'ssl://' . $this->_strHost : $this->_strHost;
        $this->_resConnection = @fsockopen($strHost, $this->_intPort, $intError, $strErrorStr, $this->_intConnectionTimeout);
        if($this->_resConnection === false)
        {
            throw new Exception("Can't connect to the server: {$this->_strHost}. ErrorNumber: {$intError}, Error: {$strErrorStr}");
        }
    }

    /**
     * request
     * @param string $strPathAndQuery path and query
     * @param array $arrGet $_GET parameters
     * @param array $arrPost $_POST parameters
     * @param array $arrHeader http headers
     * @return object request and response
     */
    public function request($strPathAndQuery, array $arrGet = array(), array $arrPost = array(), array $arrHeader = array())
    {
        // check if path and query string ist empty
        $strPathAndQuery = substr($strPathAndQuery, 0, 1) == '/' ? $strPathAndQuery: '/' . $strPathAndQuery;

        // creating request object
        $objRequest = new HttpRequest($this->_strHost, $strPathAndQuery, $arrGet, $arrPost, $arrHeader);
        $this->_arrRequestObjects[] = $objRequest;

        // get request string
        $strRequestString = $objRequest->getRequest();

        // do request and get response
        $strResponse = '';
        @fwrite($this->_resConnection, $strRequestString);
        while($strResponsePart = @fgets($this->_resConnection, 1024))
        {
            $strResponse .= $strResponsePart;
        }

        // creating response object
        $objResponse = new HttpResponse($strResponse);
        $this->_arrResponseObjects[] = $objResponse;
        
        // set cookies
        $arrRawCookie = $objResponse->getInfo('Set-Cookie');
        if($arrCookie !== false)
        {
            $arrCookies = self::parseCookies($objResponse->getInfo('Set-Cookie'));
            $this->_arrCoookies = array_merge($this->_arrCoookies, $arrCookies);
        }

        // if response code if 301 or 302 (redirect) do another request
        if($objResponse->getCode() == 301 || $objResponse->getCode() == 302)
        {
            // parse the location
            $this->_subrequest($objResponse->getInfo('Location'), $arrGet, $arrPost, $arrHeader);
        }

        // get last request and response object
        $objReturn = new stdClass();
        $objReturn->request = end($this->_arrRequestObjects);
        $objReturn->response = end($this->_arrResponseObjects);

        return($objReturn);
    }

    /**
     * _subrequest
     * @param string $strSubRequest host, path and query
     * @param array $arrGet $_GET parameters
     * @param array $arrPost $_POST parameters
     * @param array $arrHeader http headers
     */
    protected function _subrequest($strSubRequest, array $arrGet, array $arrPost, array $arrHeader)
    {
        // parse the location
        $arrSubRequest = parse_url($strSubRequest);
        $strSubRequestUrl = $arrSubRequest['scheme'] . '://' . $arrSubRequest['host'];
        $strSubRequestPathAndQuery = isset($arrSubRequest['query']) ? $arrSubRequest['path'] . '?' . $arrSubRequest['query'] : $arrSubRequest['path'];

        // get sub http object
        $objHttp = new self($strSubRequestUrl, $this->_intConnectionTimeout);
        $objHttp->request($strSubRequestPathAndQuery, $arrGet, $arrPost, $arrHeader);

        // add requests and response from subhttpobject to this one
        $this->_arrRequestObjects = array_merge($this->_arrRequestObjects, $objHttp->_arrRequestObjects);
        $this->_arrResponseObjects = array_merge($this->_arrResponseObjects, $objHttp->_arrResponseObjects);
    }
    
    /**
     * parseCookies
     * @param array|string $arrRawCookies the raw cookies array or string
     * @return array the clean parsed cookie array 
     */
    public static function parseCookies($arrRawCookies)
    {
        // empty cookie array
        $arrCookies = array();
        
        // check if cookie is an array
        if(is_array($arrRawCookies))
        {
            foreach($arrRawCookies as $strRawCookie)
            {
                // parse a single raw cookie
                $arrCookies[] = self::parseCookie($strRawCookie);
            }
        }
        else
        {
            // parse a single raw cookie
            $arrCookies[] = self::parseCookie($arrRawCookies);
        }
        
        // return the cookie array
        return($arrCookies);
    }
    
    /**
     * parseCookie
     * @param string $strCookie a raw cookie string
     * @return array single cookie array 
     */
    public static function parseCookie($strCookie)
    {
        // empty cookie pair array
        $arrCookiePairs = array();
        
        // get the raw pairs
        $arrCookieComponents = explode(';', $strCookie);
        
        // foreach raw pair get key and value
        foreach($arrCookieComponents as $strKeyToValue)
        {
            $strKey = trim(substr($strKeyToValue, 0, strpos($strKeyToValue, '=')));
            $strValue = trim(substr($strKeyToValue, strpos($strKeyToValue, '=')+1));
            $arrCookiePairs[$strKey] = $strValue;
        }
        
        // return the pairs
        return($arrCookiePairs);
    }
}