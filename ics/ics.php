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

class ics
{
    /**
     * @var array $_arrCalendar the calendar array
     */
    protected $_arrCalendar = array();
    
    /**
     * @var string $_strIcsHeader the header of the calendar
     */
    protected $_strIcsHeader = "BEGIN:VCALENDAR\r\nPRODID:-//php/ics\r\nVERSION:2.0\r\nMETHOD:PUBLISH\r\n";
    
    /**
     * @var string $_strIcsFooter the footer of the calendar
     */
    protected $_strIcsFooter = 'END:VCALENDAR';

    /**
     * @var string $_strIcs ics string
     */
    protected $_strIcs = '';

    /**
     * __construct
     * @param array $arrCalendar the calendar as an array
     */
    public function __construct(array $arrCalendar)
    {
        $this->_arrCalendar = $arrCalendar;
        $this->_strIcs = $this->_strIcsHeader;
        foreach($this->_arrCalendar as $arrEvent)
        {
            $this->_strIcs .= self::generateEventString($arrEvent);
        }
        $this->_strIcs .= $this->_strIcsFooter;
    }
    
    /**
     * getFile
     * @param string $strFilename the names of the file
     */
    public function getFile($strFilename) {
        ob_start();
        header("Content-type: text/calendar");
        header('Content-Disposition: attachment; filename="' .  $strFilename . '"');
        echo $this->_strIcs;
        ob_flush();
        die();
    }
    
    /**
     * getString
     * @return string ics string
     */
    public function getString()
    {
        return $this->_strIcs;
    }

    /**
     *
     * generateEventString
     * @param array $arrEvent
     * @return string event as ics string 
     */
    public static function generateEventString(array $arrEvent)
    {
        $strReturn = "BEGIN:VEVENT\r\n";
        $arrEventParts = array();
        
        // set uid
        if(isset($arrEvent['id']))
        {
            $arrEventParts['UID'] = md5($arrEvent['id'] . "@" . $_SERVER['SERVER_NAME']);
        }

        // set creation date
        if(isset($arrEvent['creation_date']))
        {
            $arrEventParts['DTSTAMP'] = gmstrftime("%Y%m%dT%H%M00Z", $arrEvent['creation_date']);
        }
        elseif(isset($arrEvent['from_date']))
        {
            $arrEventParts['DTSTAMP'] = gmstrftime("%Y%m%dT%H%M00Z", $arrEvent['from_date']);
        }

        // set start time of the event
        if(isset($arrEvent['from_date']))
        {
            $arrEventParts['DTSTART'] = gmstrftime("%Y%m%dT%H%M00Z", $arrEvent['from_date']);
        }
        
        // set end time of the event
        if(isset($arrEvent['end_date']))
        {
            $arrEventParts['DTEND'] = gmstrftime("%Y%m%dT%H%M00Z", $arrEvent['end_date']);
        }

        // set summary
        if(isset($arrEvent['title']))
        {
            $arrEventParts['SUMMARY'] = self::cleanString($arrEvent['title']);
        }

        // set description
        if(isset($arrEvent['text']))
        {
            $arrEventParts['DESCRIPTION'] = self::cleanString($arrEvent['text']);
        }

        // set location
        if(isset($arrEvent['location']))
        {
            $arrEventParts['LOCATION'] = self::cleanString($arrEvent['location']);
        }

        // check if all needed values are set if not throw exception
        if(!isset($arrEventParts['UID']) ||
           !isset($arrEventParts['DTSTAMP']) ||
           !isset($arrEventParts['DTSTART']) ||
           !isset($arrEventParts['DTEND']) ||
           !isset($arrEventParts['SUMMARY']))
        {
            throw new Exception('at least one missing value');
        }
        
        // add event parts to return string
        foreach($arrEventParts as $strKey => $strValue)
        {
            $strReturn .= $strKey . ":" . $strValue . "\r\n";
        }
        
        // add end to return string
        $strReturn .= "END:VEVENT" . "\r\n";
        
        // return event string
        return($strReturn);
    }

    /**
     * cleanString
     * @param string $strDirtyString the dirty input string
     * @return string cleaned string 
     */
    public static function cleanString($strDirtyString)
    {
        $arrBadSigns = array('<br />', '<br/>', '<br>', "\r\n", "\r", "\n", "\t", '"');
        $arrGoodSigns = array('\n', '\n', '\n', '', '', '', ' ', '\"');
        return(trim(str_replace($arrBadSigns, $arrGoodSigns, strip_tags($strDirtyString, '<br>'))));
    }
}