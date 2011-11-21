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

class FtpClient
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
     * @var string $_strUsername the username
     */
    protected $_strUsername = '';

    /**
     * @var string $_strPassword the password to the given username
     */
    protected $_strPassword = '';

    /**
     * @var integer $_intConnectionTimeout seconds to wait for etablishing a connection
     */
    protected $_intConnectionTimeout = 0;

    /**
     * @var resource $_resConnection connection resource
     */
    protected $_resConnection = null;

    /**
     * @var array $_arrLog all calls and answers
     */
    protected $_arrLog = array();

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
        $this->_boolSsl = $arrParsedUrl['scheme'] == 'ftps' ? true : false;

        // set host
        $this->_strHost = gethostbyname($arrParsedUrl['host']);

        // set the port if is given, else use port based on ssl is required or not
        if(isset($arrParsedUrl['port']))
        {
            $this->_intPort = $arrParsedUrl['port'];
        }
        elseif($this->_boolSsl)
        {
            $this->_intPort = 443;
        }

        // set user
        if(isset($arrParsedUrl['user']))
        {
            $this->_strUsername = $arrParsedUrl['user'];
        }

        // set password
        if(isset($arrParsedUrl['pass']))
        {
            $this->_strPassword = $arrParsedUrl['pass'];
        }

        // set connection timeout
        $this->_intConnectionTimeout = $intConnectionTimeout;
    }

    /**
     * _connect
     */
    protected function _connect()
    {
        // ssl or not
        $strHost = $this->_boolSsl ? 'ssl://' . $this->_strHost : $this->_strHost;

        // connect
        $this->_resConnection = @fsockopen($strHost, $this->_intPort, $intError, $strErrorStr, $this->_intConnectionTimeout);
        if($this->_resConnection === false)
        {
            throw new Exception("Can't connect to the server: {$this->_strHost}. ErrorNumber: {$intError}, Error: {$strErrorStr}");
        }

        // get the connection response
        $this->_arrLog[] = array
        (
            'response' => $this->_response(),
        );

        // login if needed
        if($this->_strUsername)
        {
            $this->_login();
        }

        // set options
        $this->_request('SYST');
        $this->_request('FEAT');
        $this->_request('OPTS', 'UTF8 ON');
        $this->_request('PWD');
        $this->_request('TYPE', 'I');
        $this->_request('PASV');
    }

    /**
     * _login
     */
    protected function _login()
    {
        $this->_request('USER', $this->_strUsername);
        $this->_request('PASS', $this->_strPassword);
    }

    /**
     * _request
     * @param string $strCommand command
     * @param string $strParameter parameters
     * @param string reponse
     */
    protected function _request($strCommand, $strParameter = '')
    {
        // build request string
        $strRequest = $strParameter ? $strCommand . ' ' . $strParameter . "\r\n" : $strCommand . "\r\n";

        // do the request
        fwrite($this->_resConnection, $strRequest);

        // get response
        $arrResponse = $this->_response();

        // add to log
        $this->_arrLog[] = array
        (
            'request' => trim($strRequest),
            'response' => $arrResponse,
        );

        // return response
        return($strResponse);
    }

    /**
     * _response
     * @return string response
     */
    protected function _response()
    {
        $strResponse = '';
        do
        {
            $strResponsePart = fgets($this->_resConnection, 512);
            $strResponse .= $strResponsePart;
        }
        while(substr($strResponsePart, 3, 1) != ' ');

        // check if theres a misspelled answer
        if(!preg_match('/^[0-9]{3}/', $strResponse))
        {
            throw new Exception($strResponse);
        }

        $arrResponse = array
        (
            'code' => trim(substr($strResponse, 0, 3)),
            'text' => trim(substr($strResponse, 3)),
        );

        // check if code 530
        if($arrResponse['code'] == '530')
        {
            throw new Exception($arrResponse['code'] . ' ' . $arrResponse['text']);
        }

        return($arrResponse);
    }
}