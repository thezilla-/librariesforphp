<?php 

require('ics.php');

$arrCalendar = array
(
    0 => array
    (
        'id' => 1,
        'creation_date' => 1321785950,
        'from_date' => 1321795950,
        'end_date' => 1321799550,
        'title' => 'Testevent',
        'text' => 'Dies ist ein Super cooler Anlass, bitte nehmt alle Teil!',
        'location' => 'Irgendwo und nirgwndwo',
    ),
);

$ics = new ics($arrCalendar);
$ics->getFile('test.ics');