<?php


namespace RedooCalendar\Model;


use RedooCalendar\Base\Exception\DatabaseException;
use RedooCalendar\Base\Model\BaseModel;

class Connection extends BaseModel
{
    static $_tableName = 'connections';

    /**
     * Fetch connection by code
     *
     * @param string $code
     * @return BaseModel
     * @throws DatabaseException
     */
    public function fetchByCode(string $code): BaseModel
    {
        $data = $this->_table->fetchRows(['code = \'' . $code . '\''])[0];
        if (isset($data)) {
            $this->setData($data);
        } else {
            throw new DatabaseException('Entity Not Found');
        }

        return $this;
    }

    /**
     * Fetch by calendar and user
     *
     * @param string $processor
     * @param \Users_Record_Model $user
     * @return BaseModel
     */
    public function fetchByProcessorForUser(string $processor, \Users_Record_Model $user): BaseModel
    {
        $data = $this->_table->fetchRows([
            'user_id = \'' . $user->getId() . '\'',
            'connector = \'' . $processor . '\''
        ]);
        if (isset($data[0])) {
            $this->setData($data[0]);
        }

        return $this;
    }
}