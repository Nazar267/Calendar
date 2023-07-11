<?php

namespace RedooCalendar\Source;

use RedooCalendar\Base\Source\BaseSource;

class ReadonlyConnections extends BaseSource
{
    const data = [
        'vtiger_event' => 'Vtiger Event',
        'vtiger_task' => 'Vtiger Task',
    ];
}