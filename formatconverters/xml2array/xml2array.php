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

class xml2array
{
    /**
     * @var string $_strXml xml string
     */
    protected $_strXml = '';
    
    /**
     * @var array $_arrXml xml as array
     */
    protected $_arrXml = array();
    
    /**
     * set
     * @param string|array xml string or array
     */
    public function set($xml)
    {
        if(is_string($xml))
        {
            $this->_strXml = $xml;
            $this->_buildArrayFromXml();
        }
        if(is_array($xml))
        {
            $this->_arrXml = $xml;
            $this->_buildXmlFromArray();
        }
    }

    /**
     * getString
     * @return string xml string
     */
    public function getString()
    {
        return $this->_strXml;
    }

    /**
     * getArray
     * @return array xml as array
     */
    public function getArray()
    {
        return $this->_arrXml;
    }

    /**
     * _buildArrayFromXml
     */
    protected function _buildArrayFromXml()
    {
        $this->_arrXml = self::parseXml(null, $this->_strXml);
    }
    
    /**
     * parseXml
     * @param obj|null $objXML xml object
     * @param string $strXML xml string
     * @return array xml array
     */
    static function parseXml($objXML = null, $strXML = '')
    {
        $arrTree = null;
        if($objXML === null)
        {
            $objXML = new XMLReader();
            $objXML->xml($strXML);
        }
        while(@$objXML->read())
        {
            switch ($objXML->nodeType)
            {
                case XMLReader::END_ELEMENT:
                    return $arrTree;
                case XMLReader::ELEMENT:
                    $node['tag'] = $objXML->name;
                    if($objXML->hasAttributes)
                    {
                        while($objXML->moveToNextAttribute())
                        {
                            $node['attributes'][$objXML->name] = $objXML->value;
                        }
                    }
                    $arrSubTree = self::parseXml($objXML);
                    if(!is_array($arrSubTree))
                    {
                        $node['value'] = $arrSubTree;
                    }
                    else
                    {
                        $node['childs'] = $arrSubTree;
                    }
                    $arrTree[] = $node;
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    $arrTree .= $objXML->value;
            }
        }
        return($arrTree);
    }
    
    /**
     * _buildXmlFromArray
     * @param array xml array or parts of
     */
    protected function _buildXmlFromArray(array $arrXml = null, $objXML = null)
    {
        // default get xml array
        if($arrXml === null)
        {
            $this->_strXml = '';
            $arrXml = $this->_arrXml;
        }
        
        $boolIsNewXmlObject = false;
        
        // create a new xml writer
        if($objXML === null)
        {
            $objXML = new XMLWriter();
            $objXML->openMemory();
            $boolIsNewXmlObject = true;
        }

        // foreach tags or subtag parrent
        foreach($arrXml as $intTagParentKey => $arrTag)
        {
            // there allways an int as key
            if(is_int($intTagParentKey))
            {
                // add tag
                $objXML->startElement($arrTag['tag']);

                // foreach attribute
                if(isset($arrTag['attributes']) && is_array($arrTag['attributes']))
                {
                    foreach($arrTag['attributes'] as $strAttributeKey => $strAttributeValue)
                    {
                        // add attribute
                        $objXML->writeAttribute($strAttributeKey, $strAttributeValue);
                    }
                }

                // add value
                if(isset($arrTag['value']) && !empty($arrTag['value']))
                {
                    // check if cdata is needed
                    if(htmlspecialchars($arrTag['value']) == $arrTag['value'])
                    {
                        $objXML->text($arrTag['value']);
                    }
                    else
                    {
                        $objXML->writeCData($arrTag['value']);
                    }
                }

                // child recursion
                if(isset($arrTag['childs']) && is_array($arrTag['childs']))
                {
                    $this->_buildXmlFromArray($arrTag['childs'], $objXML);
                }

                // close tag
                $objXML->endElement();
            }
        }
        
        // safe xml
        if($boolIsNewXmlObject)
        {
            $this->_strXml = $objXML->outputMemory(true);
        }
    }
}