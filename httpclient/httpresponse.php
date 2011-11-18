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

class HttpResponse
{
    /**
     * @var array $_arrHeader the response header as array
     */
    protected $_arrHeader = array();

    /**
     * @var string $_strContent the response content
     */
    protected $_strContent = '';

    /**
     * __construct
     * @param string $strResponse
     */
    public function __construct($strResponse)
    {
        // split header and content
        $intPositionofFirstTwoLineBreaks = strpos($strResponse, "\r\n\r\n");
        $strHeader = substr($strResponse, 0, $intPositionofFirstTwoLineBreaks);
        $strContent = substr($strResponse, $intPositionofFirstTwoLineBreaks + 4);

        // call header parser
        $this->_arrHeader = self::parseHeader($strHeader);

        // call content parser
        $this->_strContent = self::parseContent($this->_arrHeader, $strContent);
    }

    /**
     * getCode
     * @return integer response code
     */
    public function getCode()
    {
        return($this->_arrHeader['code']);
    }

    /**
     * getMessage
     * @return string response message
     */
    public function getMessage()
    {
        return($this->_arrHeader['message']);
    }

    /**
     * getInfo
     * @return array response info
     */
    public function getInfo($key)
    {
        return($this->_arrHeader['info'][$key]);
    }

    /**
     * getContent
     * @return string response content
     */
    public function getContent()
    {
        return($this->_strContent);
    }

    /**
     * parseHeader
     * @param string $strHeader header string
     * @return array header array
     */
    public static function parseHeader($strHeader)
    {
        $arrReturn = array();

        // split header lines
        $arrRawHeader = explode("\r\n", $strHeader);

        // get protocoll code and message
        preg_match('/^([^\s]+)\s([\d]+)\s(.*)$/', $arrRawHeader[0], $arrMatches);
        if(!isset($arrMatches[3]) || empty($arrMatches[3]))
        {
            throw new Exception('Invalid protocol line!');
        }
        $arrReturn['proto'] = $arrMatches[1];
        $arrReturn['code'] = $arrMatches[2];
        $arrReturn['message'] = trim($arrMatches[3]);

        // remove first line
        array_shift($arrRawHeader);

        // go through the other raw array elements
        foreach($arrRawHeader as $strHeaderLine)
        {
            preg_match('/^([^\s]+):(.*)$/', $strHeaderLine, $arrMatches);
            if(!isset($arrMatches[2]) || empty($arrMatches[2]))
            {
                throw new Exception("Invalid header line: {$strHeaderLine} !");
            }
            $arrReturn['info'][$arrMatches[1]] = trim($arrMatches[2]);
        }
        return($arrReturn);
    }

    /**
     * parseContent
     * @param string $strContent
     * @return string clean content
     */
    public static function parseContent(array $arrHeader, $strContent)
    {
        if(isset($arrHeader['info']['Transfer-Encoding']) && $arrHeader['info']['Transfer-Encoding'] == 'chunked')
        {
            $strContent = self::unchunkHttp11($strContent);
        }
        return(trim($strContent));
    }

    /**
     * unchunkHttp11
     * @author Buoysel (http://php.net/manual/de/function.fsockopen.php)
     * @param string $strContent
     * @return string unchunked content
     */
    public static function unchunkHttp11($strContent)
    {
        $strReturn = '';

        // set pointer where we are in the content string
        $intPointer = 0;

        // while the pointer is smaller than the string length
        while($intPointer < strlen($strContent))
        {
            // get the hex number of the sign group
            $strHexNumber = substr($strContent, $intPointer, strpos(substr($strContent, $intPointer), "\r\n") + 2);

            // get the decimal number of the sign group
            $intNumber = hexdec(trim($strHexNumber));

            // set new pointer position, go after the string with hexnumber
            $intPointer += strlen($strHexNumber);

            // add that many signs defined in the decimal number
            $strContentPart = substr($strContent, $intPointer, $intNumber);
            $strReturn .= $strContentPart;

            // set new pointer position, go after the content part
            $intPointer += strlen($strContentPart);
        }
        return($strReturn);
    }
}