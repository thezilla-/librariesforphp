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
     * @var string $_strRawHeader the raw response
     */
    protected $_strRawHeader = '';

    /**
     * @var array $_arrHeader the response header as array
     */
    protected $_arrHeader = array();

    /**
     * @var string $_strRawContent the raw content
     */
    protected $_strRawContent = '';

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
        $this->_strRawHeader = substr($strResponse, 0, $intPositionofFirstTwoLineBreaks);
        $this->_strRawContent = substr($strResponse, $intPositionofFirstTwoLineBreaks + 4);

        // call header parser
        $this->_arrHeader = self::parseHeader($this->_strRawHeader);

        // call content parser
        $this->_strContent = self::parseContent($this->_arrHeader, $this->_strRawContent);
    }
    
    /**
     * getRawHeader
     * @return string raw response header
     */
    public function getRawHeader()
    {
        return($this->_strRawHeader);
    }

    /**
     * getBaseInfo
     * @return array response baseinfos
     */
    public function getBaseInfos()
    {
        return($this->_arrHeader['baseinfo']);
    }

    /**
     * getBaseInfo
     * @param string $key the wished baseinfo
     * @return string|boolean response single baseinfo
     */
    public function getBaseInfo($key)
    {
        if(isset($this->_arrHeader['baseinfo'][$key]))
        {
            return($this->_arrHeader['baseinfo'][$key]);
        }
        return(false);
    }
    
    /**
     * getInfo
     * @return array response infos
     */
    public function getInfos()
    {
        return($this->_arrHeader['info']);
    }

    /**
     * getInfo
     * @param string $key the wished info
     * @return string|boolean response single info
     */
    public function getInfo($key)
    {
        if(isset($this->_arrHeader['info'][$key]))
        {
            return($this->_arrHeader['info'][$key]);
        }
        return(false);
    }
    
    /**
     * getRawContent
     * @return string raw response content
     */
    public function getRawContent()
    {
        return($this->_strRawContent);
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
        // split header lines
        $arrRawHeader = explode("\r\n", $strHeader);
        
        // get first line (protocol, code, message)
        preg_match('/^([^\s]+)\s([\d]+)\s(.*)$/', array_shift($arrRawHeader), $arrMatches);
        if(!isset($arrMatches[3]) || empty($arrMatches[3]))
        {
            throw new Exception('Invalid protocol line!');
        }
        
        // base information
        $arrReturn = array
        (
            'baseinfo' => array
            (
                'protocol' => $arrMatches[1],
                'code' => $arrMatches[2],
                'message' => trim($arrMatches[3]),
            ),
        );

        // add info to return array
        $arrReturn['info'] = self::parseHeaderLines($arrRawHeader);
        
        // return the header array
        return $arrReturn;
    }
    
    /**
     * parseHeaderLines
     * @param array $arrRawHeader
     * @return array header info array 
     */
    public static function parseHeaderLines($arrRawHeader)
    {
        $arrReturn = array();
        
        // go through the other raw array elements
        foreach($arrRawHeader as $strHeaderLine)
        {
            // get combinations like "key: value", can be "key: value" : to
            preg_match('/^([^\s]+):(.*)$/', $strHeaderLine, $arrMatches);
            if(!isset($arrMatches[2]) || empty($arrMatches[2]))
            {
                throw new Exception("Invalid header line: {$strHeaderLine}!");
            }
            
            // add parsed header line to return array
            $arrReturn[$arrMatches[1]][] = self::parseSingleHeaderValue(trim($arrMatches[2]));
        }
        
        // simplify header array (remove arrays)
        foreach($arrReturn as $strKey => $arrValue)
        {
            if(count($arrReturn[$strKey]) == 1 && key($arrReturn[$strKey]) == 0)
            {
                $arrReturn[$strKey] = $arrReturn[$strKey][0];
            }
        }
        
        // return the parsed header lines
        return($arrReturn);
    }
    
    /**
     * parseSingleHeaderValue
     * @param string $strSingleHeaderLineValue
     * @return array|string the header line parts 
     */
    public static function parseSingleHeaderValue($strSingleHeaderLineValue)
    {
        $arrReturn = array();
        
        // default set found seperator false
        $strFoundSeperator = false;
        
        // seperator can be a semmicolon or a comma
        $arrSeperatorSigns = array(';',',');
        
        foreach($arrSeperatorSigns as $strSeperator)
        {
            // search for the seperator and search for =
            if(strpos($strSingleHeaderLineValue, $strSeperator) !== false && strpos($strSingleHeaderLineValue, '=') !== false)
            {
                // seperator found
                $strFoundSeperator = true;
                
                // explode the parts
                $arrSingleHeaderLineValue = explode($strSeperator, $strSingleHeaderLineValue);
                
                // foreach combination
                foreach($arrSingleHeaderLineValue as $strCombination)
                {
                    // explode the key from value
                    $arrCombinationKeyToValue = explode('=', $strCombination);
                    
                    // if theres a key to value combination
                    if(count($arrCombinationKeyToValue) > 1)
                    {
                        $arrReturn[trim($arrCombinationKeyToValue[0])] = trim($arrCombinationKeyToValue[1]);
                    }
                    else
                    {
                        $arrReturn[] = $strCombination;
                    }
                }
            }
        }
        
        // no seperator found
        if(!$strFoundSeperator)
        {
            $arrReturn[] = $strSingleHeaderLineValue;
        }
        
        // simplify header array (remove arrays)
        if(count($arrReturn) == 1 && key($arrReturn) == 0)
        {
            $arrReturn = $arrReturn[0];
        }

        // return a single header value
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