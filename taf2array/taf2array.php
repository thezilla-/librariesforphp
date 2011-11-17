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

class taf2array
{
    /**
     * var string taf
     */
    protected $_strTaf = '';

    /**
     * var string modified taf
     */
    protected $_strModifiedTaf = '';

    /**
     * var array taf as array
     */
    protected $_arrTaf = array();

    /**
     * var array reference array
     */
    protected $_arrReference = array();

    /**
     * var array parsing signs
     */
    protected $_arrParsingSigns = array
    (
        'open' => '{',
        'close' => '}',
        'quotes' => '"',
    );

    /**
     * var array helpers
     */
    protected $_arrHelpers= array
    (
        'open' => '_#__##reference',
        'close' => '##__#_',
    );

    /**
     * set
     * @param string|array taf string or array
     */
    public function set($taf)
    {
        if(is_string($taf))
        {
            $this->_strTaf = $taf;
            $this->_strModifiedTaf = $this->_strTaf;
            $this->_buildArrayFromTaf();
        }
        if(is_array($taf))
        {
            $this->_arrTaf = $taf;
            $this->_buildTafFromArray();
            $this->_strModifiedTaf = $this->_strTaf;
        }
    }

    /**
     * getString
     * @return string taf string
     */
    public function getString()
    {
        return $this->_strTaf;
    }

    /**
     * getArray
     * @return array taf as array
     */
    public function getArray()
    {
        return $this->_arrTaf;
    }

    /**
     * _buildArrayFromTaf
     */
    protected function _buildArrayFromTaf()
    {
        if(!$this->_checkOpenCloseSymetric())
        {
            throw new Exception('Asymetic open and close signs');
            return false;
        }
        $this->_addToReferenceArray();
        $this->_createArrayFromReference();
    }

    /**
     * _checkOpenCloseSymetric
     * @return boolean
     */
    protected function _checkOpenCloseSymetric()
    {
        // open close signs
        preg_match_all('/' . preg_quote($this->_arrParsingSigns['open']) . '/', $this->_strModifiedTaf, $arrFoundOpenSign);
        $intOpenSign = isset($arrFoundOpenSign[0]) ? count($arrFoundOpenSign[0]) : 0;
        preg_match_all('/' . preg_quote($this->_arrParsingSigns['close']) . '/', $this->_strModifiedTaf, $arrFoundCloseSign);
        $intCloseSign = isset($arrFoundCloseSign[0]) ? count($arrFoundCloseSign[0]) : 0;
        if($intOpenSign != $intCloseSign)
        {
            return false;
        }
        return true;
    }

    /**
     * _addToReferenceArray
     */
    protected function _addToReferenceArray()
    {
        // get all pairs
        preg_match_all('/' . $this->_arrParsingSigns['open'] . '[^' . $this->_arrParsingSigns['open'] . ']*?' . $this->_arrParsingSigns['close'] . '/', $this->_strModifiedTaf, $arrPairsMatch);
        if(count($arrPairsMatch[0]) > 0)
        {
            foreach($arrPairsMatch[0] as $strPair)
            {
                // check if quotes count is divisible by two
                if(!$this->_checkQuotesSymetic($strPair))
                {
                    throw new Exception('Quotes count isn\'t divisible by two');
                    return false;
                }

                // add to reference array
                $this->_arrReference[] = $strPair;

                // get last key
                $arrKeys = array_keys($this->_arrReference);
                $intLastKey = end($arrKeys);

                // replace last key in taf string
                $this->_strModifiedTaf = str_replace($strPair, $this->_arrHelpers['open'] . $intLastKey . $this->_arrHelpers['close'], $this->_strModifiedTaf);
            }
            // recursion
            $this->_addToReferenceArray();
        }
    }

    /**
     * _checkQuotesSymetic
     * @return boolean
     */
    protected function _checkQuotesSymetic($strPair)
    {
        // quotes
        preg_match_all('/' . preg_quote($this->_arrParsingSigns['quotes']) . '/', $strPair, $arrFoundQuotes);
        $intQuotes = isset($arrFoundQuotes[0]) ? count($arrFoundQuotes[0]) : 0;
        if($intQuotes/2 != ceil($intQuotes/2))
        {
            return false;
        }
        return true;
    }

    /**
     * _createArrayFromReference
     */
    protected function _createArrayFromReference()
    {
        $arrTaf = array();
        foreach($this->_arrReference as $intReferenceKey => $strReferenceValue)
        {
            $arrChilds = array();
            $arrAttributes = array();

            // search for child references
            preg_match_all('/' . $this->_arrHelpers['open'] . '(\d+)' . $this->_arrHelpers['close'] . '/', $strReferenceValue, $arrMatches);
            if(isset($arrMatches[1]) && count($arrMatches[1]) > 0)
            {
                foreach($arrMatches[1] as $intPatternKey => $intChildReferenceKey)
                {
                    // add child references to child reference array
                    $arrChilds[] = $intChildReferenceKey;

                    // remove child reference
                    $strReferenceValue = str_replace($arrMatches[0][$intPatternKey], '', $strReferenceValue);
                }
            }

            $arrAttributePatterns = array
            (
                '/([^\s]+)=' . $this->_arrParsingSigns['quotes'] . '([^' . $this->_arrParsingSigns['quotes'] . ']*)' . $this->_arrParsingSigns['quotes'] .'/',
                '/([^\s]+)=([^\s]+)/'
            );

            // search for attributes
            foreach($arrAttributePatterns as $strAttributePattern)
            {
                preg_match_all($strAttributePattern, $strReferenceValue, $arrMatches);
                if(isset($arrMatches[0]) && count($arrMatches[0]) > 0)
                {
                    foreach($arrMatches[0] as $intPatternKey => $strAttributeName)
                    {
                        // add attribute key => value
                        $arrAttributes[$arrMatches[1][$intPatternKey]] = $arrMatches[2][$intPatternKey];

                        // remove attribute reference
                        $strReferenceValue = str_replace($arrMatches[0][$intPatternKey], '', $strReferenceValue);
                    }
                }
            }

            // get tag
            preg_match('/' .$this->_arrParsingSigns['open'] . '([^\s]+)/', $strReferenceValue, $arrMatches);
            if(isset($arrMatches) && count($arrMatches) > 0)
            {
                // add tag
                $arrTaf[$intReferenceKey]['tag'] = $arrMatches[1];

                // add attributes if exists
                if(count($arrAttributes) > 0)
                {
                    $arrTaf[$intReferenceKey]['attributes'] = $arrAttributes;
                }

                // add value if exists
                if(count($arrChilds) > 0)
                {
                    foreach($arrChilds as $intChildReferenceKey)
                    {
                        $arrTaf[$intReferenceKey]['childs'][] = $arrTaf[$intChildReferenceKey];
                    }
                }
            }
        }
        $this->_arrTaf = array(end($arrTaf));
    }

    /**
     * _buildTafFromArray
     * @param array taf array or parts of
     */
    protected function _buildTafFromArray(array $arrTaf = null)
    {
        // default get taf array
        if($arrTaf === null)
        {
            $this->_strTaf = '';
            $arrTaf = $this->_arrTaf;
        }

        // foreach tags or subtag parrent
        foreach($arrTaf as $intTagParentKey => $arrTag)
        {
            // there allways an int as key
            if(is_int($intTagParentKey))
            {
                // add the open deliminiter and the string tag to the output string
                $this->_strTaf .= $this->_arrParsingSigns['open'] . $arrTag['tag']. ' ';

                // foreach attribute
                foreach($arrTag['attributes'] as $strAttributeKey => $strAttributeValue)
                {
                    // check if stringdeliminiter is needed for key
                    if(preg_match('/[\s]/', $strAttributeKey))
                    {
                        $strAttributeKey = $this->_arrParsingSigns['quotes'] . $strAttributeKey . $this->_arrParsingSigns['quotes'];
                    }

                    // check if stringdeliminiter is needed for value
                    if(preg_match('/[\s]/', $strAttributeValue))
                    {
                        $strAttributeValue = $this->_arrParsingSigns['quotes'] . $strAttributeValue . $this->_arrParsingSigns['quotes'];
                    }

                    // add combo to the output string
                    $this->_strTaf .= $strAttributeKey . '=' . $strAttributeValue . ' ';
                }

                // value recursion
                if(isset($arrTag['childs']) && is_array($arrTag['childs']))
                {
                    $this->_strTaf .= $this->_buildTafFromArray($arrTag['childs']);
                }

                // add the close deliminiter and the string tag to the output string
                $this->_strTaf .= $this->_arrParsingSigns['close'];
            }
        }
        $this->_strTaf = $this->_strTaf;
    }
}