<?php

namespace RedooCalendar\Source;

use RedooCalendar\Base\Source\BaseSource;

class TaskStatusOptions extends BaseSource
{
    const data = [
        'Not Started' => 'Not Started',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'Pending Input' => 'Pending Input',
        'Deferred' => 'Deferred',
        'Planned' => 'Planned'
    ];
}