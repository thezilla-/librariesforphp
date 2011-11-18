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

class HttpRequest
{
    /**
     * var string $_strHost
     */
    protected $_strHost = '';

    /**
     * var string $_strPath
     */
    protected $_strPath = '';

    /**
     * var array $_arrGet
     */
    protected $_arrGet = array();

    /**
     * var array $_arrPost
     */
    protected $_arrPost = array();

    /**
     * var array $_arrHeader
     */
    protected $_arrHeader = array();

    /**
     * var string $_strRequest
     */
    protected $_strRequest = '';

    /**
     * __construct
     * @param string $strHost the host
     * @param string $strPathAndQuery path and query
     * @param array $arrGet $_GET parameters
     * @param array $arrPost $_POST parameters
     * @param array $arrHeader http headers
     */
    public function __construct($strHost, $strPathAndQuery, array $arrGet, array $arrPost, array $arrHeader)
    {
        // set properties
        $this->_setProperties($strHost, $strPathAndQuery, $arrGet, $arrPost, $arrHeader);
        $this->_buildRequestString();
    }

    /**
     * getRequest
     * @return string request
     */
    public function getRequest()
    {
        return($this->_strRequest);
    }

    /**
     * _setProperties
     * @param string $strHost the host
     * @param string $strPathAndQuery path and query
     * @param array $arrGet $_GET parameters
     * @param array $arrPost $_POST parameters
     * @param array $arrHeader http headers
     */
    protected function _setProperties($strHost, $strPathAndQuery, array $arrGet, array $arrPost, array $arrHeader)
    {
        // set host
        $this->_strHost = $strHost;

        // explode path and query
        $arrPathAndQuery = explode('?', $strPathAndQuery);

        // set path
        $this->_strPath = $arrPathAndQuery[0];

        // if theres a query string ($_GET)
        if(isset($arrPathAndQuery[1]))
        {
            parse_str($arrPathAndQuery[1], $this->_arrGet);
        }

        // set all $_GET parameter
        $this->_arrGet = array_replace_recursive($this->_arrGet, $arrGet);

        // set all $_POST parameter
        $this->_arrPost = $arrPost;

        // set all http headers
        $this->_arrHeader = $arrHeader;
    }

    /**
     * _buildRequestString
     */
    protected function _buildRequestString()
    {
        // get or post
        $this->_strRequest = count($this->_arrPost) === 0 ? "GET {$this->_strPath}" : "POST {$this->_strPath}";

        // add query string ($_GET)
        if(count($this->_arrGet) > 0)
        {
            $this->_strRequest .= '?' . http_build_query($this->_arrGet);
        }

        // add protocoll
        $this->_strRequest .= " HTTP/1.1\r\n";

        // add host
        $this->_strRequest .= "Host: {$this->_strHost}\r\n";

        // add headers
        $arrHeaders = array();
        if(count($this->_arrPost) > 0)
        {
            $strPost = http_build_query($this->_arrPost);
            $arrHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
            $arrHeaders['Content-Length'] = strlen($strPost);
        }
        $arrHeaders['Connection'] = 'close';
        $arrHeaders = array_replace_recursive($arrHeaders, $this->_arrHeader);
        foreach($arrHeaders as $strKey => $strValue)
        {
            $this->_strRequest .= "{$strKey}: {$strValue}\r\n";
        }

        // add post string
        if(isset($strPost))
        {
            $this->_strRequest .= "\r\n{$strPost}\r\n";
        }

        // add another linebreaK
        $this->_strRequest .= "\r\n";
    }
}