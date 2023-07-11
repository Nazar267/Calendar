<?php

namespace RedooCalendar\Model;

use RedooCalendar\Base\Model\BaseModel;
use RedooCalendar\Model\Base\EventInterface;

class Event extends BaseModel implements EventInterface
{
    static $_tableName = 'event';

    /**
     * Save model to database
     *
     * @return BaseModel
     */
    public function save(): BaseModel
    {
        if (!$this->getId()) {
            $this->_table->insert($this->getData());
            $this->setId($this->_table->lastInsertId());
        } else {
            $this->update();
        }
        return $this;
    }


}