<?php

namespace RedooCalendar\Source;

use RedooCalendar\Base\Source\BaseSource;

class DefaultConnections extends BaseSource
{
    const data = [
        'redoo' => 'Redoo',
        'vtiger_event' => 'Vtiger Event',
        'vtiger_task' => 'Vtiger Task',
        'custom' => 'Custom',
    ];
}