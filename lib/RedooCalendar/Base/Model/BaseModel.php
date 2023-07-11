<?php

namespace RedooCalendar\Base\Model;

use RedooCalendar\Base\Collection\BaseCollection;
use RedooCalendar\Base\Collection\Item\CollectionItemInterface;
use RedooCalendar\Base\Exception\RelationException;
use RedooCalendar\Base\VarienObject;
use RedooCalendar\Base\Database;

/**
 * Class BaseModel
 * @package RedooCalendar\Base\Model
 */
abstract class BaseModel extends VarienObject implements CollectionItemInterface
{
    const ID_FIELD_NAME = 'id';

    static $_tableName;
    protected $_table;
    protected $collection;
    protected $relations = [];

    public function __construct()
    {
        $this->_table = Database::table(static::$_tableName);
        $this->_idFieldName = self::ID_FIELD_NAME;
        parent::__construct();
    }

    /**
     * Init relations
     *
     * @return BaseModel
     * @throws RelationException
     */
    public function initRelations(): BaseModel
    {
        foreach ($this->relations['has_many'] as $key => &$relation) {
            if (!isset($relation['class'])) throw new RelationException();

            /** @var BaseCollection $collection */
            $collection = new $relation['class']();

            $collection->fetchForRelation($relation['remote_column'], $this->getData($relation['local_column']));
            $relation['collection'] = $collection;
        }
        return $this;
    }

    /**
     * Save model to database
     *
     * @return BaseModel
     */
    public function save(): BaseModel
    {
        if (!$this->getId()) {
            $this->_table->insert($this->_data);
            $this->setId($this->_table->lastInsertId());
        } else {
            $this->update();
        }
        return $this;
    }

    /**
     * Update model in database
     *
     * @param null $where
     * @return BaseModel
     */
    protected function update($where = null): BaseModel
    {
        if (!$where) {
            $where = $this->getId();
        }

        $this->_table->update($this->_data, $where);
        return $this;
    }

    /**
     * Fetch from database
     *
     * @param int $id
     * @return BaseModel
     */
    public function fetch(int $id): BaseModel
    {
        if (isset($this->_table->fetchRows($id)[0])) {
            $this->setData($this->_table->fetchRows($id)[0]);
        }

        return $this;
    }

    /**
     * Delete from database
     *
     * @param int $id
     * @return BaseModel
     */
    public function delete(): bool
    {
        try {
            $this->_table->delete($this->getId());
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $type
     * @return array
     */
    public function getRelations(string $type): array
    {
        if (isset($this->relations[$type])) {
            return $this->relations[$type];
        }
        return [];
    }
}
