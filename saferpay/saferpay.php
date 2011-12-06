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

class saferpay
{
    /**
     * @var string $_strSaferPayUrl the saferpay url
     */
    protected $_strSaferPayUrl = 'https://www.saferpay.com';
    
    /**
     * @var array $_arrSaferPayQueryStrings the saferpay urls
     */
    protected $_arrSaferPayQueryStrings = array
    (
        'init' => '/hosting/CreatePayInit.asp',
        'confirm' => '/hosting/VerifyPayConfirm.asp',
        'complete' => '/hosting/PayCompleteV2.asp',
    );
    
    /**
     * @var array $_arrSaferPayParameters allowed saferpay parameters
     */
    protected $_arrSaferPayParameters = array
    (
        'init' => array
        (
            'ACCOUNTID' => 'ns[..15]',
            'AMOUNT' => 'n[..8]',
            'CURRENCY' => 'a[3]',
            'DESCRIPTION' => 'ans[..50]',
            'ORDERID' => 'ans[..80]',
            'VTCONFIG' => 'an[..20]',
            'SUCCESSLINK' => 'ans[..1024]',
            'FAILLINK' => 'ans[..1024]',
            'BACKLINK' => 'ans[..1024]',
            'NOTIFYURL' => 'ans[..1024]',
            'AUTOCLOSE' => 'n[..2]',
            'CCNAME' => 'a[..3]',
            'NOTIFYADDRESS' => 'ans[..50]',
            'USERNOTIFY' => 'ans[..50]',
            'LANGID' => 'a[2]',
            'SHOWLANGUAGES' => 'a[..3]',
            'PROVIDERSET' => 'ns[..40]',
            'DURATION' => 'n[14]',
            'CARDREFID' => 'ans[..40]',
            'DELIVERY' => 'a[..3]',
            'ADDRESS' => 'a[..8]',
            'COMPANY' => 'ans[..50]',
            'GENDER' => 'a[1]',
            'FIRSTNAME' => 'ans[..50]',
            'LASTNAME' => 'ans[..50]',
            'STREET' => 'ans[..50]',
            'ZIP' => 'an[..10]',
            'CITY' => 'ans[..50]',
            'COUNTRY' => 'a[2]',
            'EMAIL' => 'ans[..50]',
            'PHONE' => 'ans[..50]',
        ),
        'confirm' => array
        (
            'MSGTYPE' => 'a[..30]',
            'VTVERIFY' => 'ans[..40]',
            'KEYID' => 'ans[..40]',
            'ID' => 'an[28]',
            'TOKEN' => 'ans[..40]',
            'ACCOUNTID' => 'ns[..15]',
            'AMOUNT' => 'n[..8]',
            'CURRENCY' => 'a[3]',
            'CARDREFID' => 'ans[..40]',
            'PROVIDERID' => 'n[..4]',
            'PROVIDERNAME' => 'ans[..30]',
            'ORDERID' => 'an[..39]',
            'IP' => 'ns[..15]',
            'IPCOUNTRY' => 'a[2]',
            'CCCOUNTRY' => 'a[2]',
            'MPI_LIABILITYSHIFT' => 'a[..3]',
            'ECI' => 'n[1]',
            'XID' => 'ans[28]',
            'CAVV' => 'ans[28]',
        ),
    );

    /**
     * @var array $_arrSaferPayData saferpay data
     */
    protected $_arrSaferPayData = array
    (
        'init' => array(),
        'confirm' => array(),
    );
    
    /**
     * @var array $_arrSaferPayInvalidData saferpay invalid data
     */
    protected $_arrSaferPayInvalidData = array
    (
        'init' => array(),
        'confirm' => array(),
    );
    
    /**
     * @var array $_arrSignatur the saferpay signatures
     */
    protected $_arrSignaturs = array
    (
        'init' => '',
        'confirm' => '',
    );

    /**
     * @var string $_strSessionKey the session key
     */
    protected $_strSessionKey = '';

    /**
     * @var array $_arrRequestLog request log
     */
    protected $_arrRequestLog = array();
    
    /**
     * getInstance
     * @param string|null $strSerializedObject serialized object
     * @return object self
     */
    static public function getInstance($strSerializedObject = null)
    {
        $objSaferpay = @unserialize($strSerializedObject);
        if(is_object($objSaferpay))
        {
            return($objSaferpay);
        }
        return(new Saferpay());
    }
    
    /**
     * getSerializedInstance
     * @return string serialzed instance
     */
    public function getSerializedInstance()
    {
        return(serialize($this));
    }
    
    /**
     * createPayInit
     * @param array $arrInitData the data array for saferpay
     * @return string saferpay url
     */
    public function createPayInit(array $arrInitData)
    {
        // set data
        $this->_setInputs('init', $arrInitData);
        
        // call request handler
        $strReturn = $this->_requestHandler('init', $this->_arrSaferPayData['init']);
        if(strpos($strReturn, 'ERROR') !== false)
        {
            throw new \Exception($strReturn);
        }

        // set signature
        $this->_arrSignaturs['init'] = self::getFieldFromAnswer($strReturn, 'SIGNATURE');
        return($strReturn);
    }
    
    /**
     * verfifyPayConfirm
     * @param string data xml from saferpay
     * @param string signature from saferpay
     * @return boolean true or false with exception
     */
    public function verfifyPayConfirm($strData, $strSignature)
    {
        // convert xml to array
        $objXml = new xml2array();
        $objXml->set($strData);
        $arrXML = $objXml->getArray();

        if(is_array($arrXML[0]['attributes']))
        {
            // set data
            $this->_setInputs('confirm', $arrXML[0]['attributes']);

            // set signature
            $this->_arrSignaturs['confirm'] = $strSignature;

            // verfify payment
            $arrConfirmRequest = array
            (
                'DATA' => $strData,
                'SIGNATURE' => $strSignature,
            );
            
            $strConfirm = $this->_requestHandler('confirm', $arrConfirmRequest);

            // complete payment
            $arrCompleteRequest = array
            (
                'ACCOUNTID' => $this->_arrSaferPayData['init']['ACCOUNTID'],
                'ID' => $this->_arrSaferPayData['confirm']['ID'],
            );
            if(substr($this->_arrSaferPayData['init']['ACCOUNTID'], 0, 6) == "99867-") {
                $arrCompleteRequest['spPassword'] = 'XAjc3Kna';
            }
            $strComplete = $this->_requestHandler('complete', $arrCompleteRequest);

            return(true);
        }
        throw new \Exception('Data xml from saferpay is invalid!');
        return(false);
    }
    
    /**
     * _setInputs
     * @param string $strAction the action to prepare
     * @param array $arrValues the values to set
     */
    protected function _setInputs($strAction, array $arrValues)
    {
        if(isset($this->_arrSaferPayData[$strAction]) && is_array($arrValues))
        {
            foreach($arrValues as $strKey => $strValue)
            {
                $this->_setInput($strAction, $strKey, $strValue);
            }
        }
    }
    
    /**
     * _setInput
     * @param string $strAction the action to prepare
     * @param string $strKey the key of the field
     * @param string $strValue the value of the field
     * @return boolean
     */
    protected function _setInput($strAction, $strKey, $strValue)
    {
        if(isset($this->_arrSaferPayParameters[$strAction][$strKey]))
        {
            // rewrite condition to regular expression
            $strPattern = self::conditionToRegex($this->_arrSaferPayParameters[$strAction][$strKey]);
            
            // check if the value is ok
            if(preg_match($strPattern, $strValue) == 1)
            {
                $this->_arrSaferPayData[$strAction][$strKey] = $strValue;
                return(true);
            }
        }
        $this->_arrSaferPayInvalidData[$strAction][$strKey] = $strValue;
        return(false);
    }
    
    /**
     * conditionToRegex
     * @param string $strCondition the condition to be converted into a regular expression
     * @return string regular expression pattern
     */
    public static function conditionToRegex($strCondition)
    {
        //replace condition with matching regular expression
        $strReplacedConditions = str_replace
        (
            array
            (
                ']',
                '[',
                '..',
                'a',
                'n',
                's'
            ),
            array
            (
                '}',
                ']{',
                '1,',
                'a-z ',
                '0-9',
                '\-\_\:\;\/\\\<\>\.\=\?',
            ),
            $strCondition
        );
        
        // return full regular expression pattern
        return('/^([' . $strReplacedConditions . ')$/i');
    }
    
    /**
     * @param string $strAction the action to prepare
     * @param array $arrPostValues the to post data
     * @return string the response body
     */
    protected function _requestHandler($strAction, $arrPostValues)
    {
        // httpclient connect
        $objHttpClient = new HttpClient($this->_strSaferPayUrl);
        $objRequestAndResponse = $objHttpClient->request($this->_arrSaferPayQueryStrings[$strAction], array(), $arrPostValues);
        
        // add to request log
        $this->_arrRequestLog[] = $objRequestAndResponse;
        
        // check if the response code is 200
        if($objRequestAndResponse->response->getBaseInfo('code') != 200)
        {
            throw new \Exception($objRequestAndResponse->response->getBaseInfo('code') . ': ' . $objRequestAndResponse->response->getContent());
        }

        // return response body
        return $objRequestAndResponse->response->getContent();
    }
    
    /**
     * getFieldFromAnswer
     * @param string $strUrl the url
     * @param string $strKey the key wished from the given url
     * @return string|boolean the value or false with exception
     */
    static public function getFieldFromAnswer($strUrl, $strKey)
    {
        parse_str(parse_url($strUrl, PHP_URL_QUERY), $arrQueryElements);
        if(is_array($arrQueryElements) && isset($arrQueryElements[$strKey]))
        {
            return($arrQueryElements[$strKey]);
        }
        throw new \Exception("key: {$strKey}, url: {$strUrl}");
    }
}