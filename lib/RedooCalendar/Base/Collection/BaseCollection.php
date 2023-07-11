<?php

namespace RedooCalendar\Base\Collection;

use RedooCalendar\Base\Collection\Item\CollectionItemInterface;
use RedooCalendar\Base\Database;
use RedooCalendar\Base\Model\BaseModel;

/**
 * Class BaseCollection
 * @package RedooCalendar\Base\Collection
 */
class BaseCollection implements CollectionInterface
{
    protected $model;
    protected $items = [];
    protected $_table;

    public function __construct()
    {
        if ($this->model) {
            $modelClass = $this->model;
            $this->_table = Database::table($modelClass::$_tableName);
        }
    }

    /**
     * Get items as array of models
     *
     * @return array[Model]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get items as array
     *
     * @return array
     */
    public function getItemsAsArray(): array
    {
        $resultArray = [];
        /** @var CollectionItemInterface $item */
        foreach ($this->getItems() as $item) {
            $_item = $item->getData();

            $_item['relations'] = [];
            foreach ($item->getRelations('has_many') as $key => $relation) {
                $_item['relations'][$key] = $relation['collection'] ? $relation['collection']->getItemsAsArray() : [];
            }

            $resultArray[] = $_item;
        }

        return $resultArray;
    }

    /**
     * Add item to collection
     *
     * @param CollectionItemInterface $item
     * @return BaseCollection
     */
    public function setItem(CollectionItemInterface $item): BaseCollection
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }

    /**
     * Fetch all rows from database
     *
     * @return $this
     * @throws \RedooCalendar\Base\Exception\RelationException
     */
    public function fetchAll(): BaseCollection
    {
        $modelClass = $this->model;
        foreach ($this->_table->fetchAll() as $item) {
            /** @var BaseModel $model */
            $model = new $modelClass();
            $model->setData($item);
            $model->initRelations();
            $this->setItem($model);
        }
        return $this;
    }

    /**
     * Get items for relation
     *
     * @param string $column
     * @param string $value
     * @return BaseCollection
     */
    public function fetchForRelation(string $column, string $value): BaseCollection
    {
        $modelClass = $this->model;
        foreach ($this->_table->fetchRows([$column . ' = ' . $value]) as $item) {
            /** @var BaseModel $model */
            $model = new $modelClass();
            $model->setData($item);
            $this->setItem($model);
        }
        return $this;
    }

    /**
     * Filter collection by relation
     *
     * @param string $relation
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return BaseCollection
     */
    public function filerByRelation(string $relation, string $column, string $operator, string $value): BaseCollection
    {
        /**  @var BaseModel $item */
        foreach ($this->getItems() as $key => $item) {
            $item->initRelations();
            $result = false;

            if ($item->getRelations('has_many')[$relation]['collection']) {
                foreach ($item->getRelations('has_many')[$relation]['collection']->getItems() as $_item) {
                    if ($operator === '=') {
                        if ($_item->getData($column) == $value) {
                            $result = true;
                            break;
                        }
                    }
                }
            }

            if (!$result) {
                $this->removeItem($key);
            }
        }
        return $this;
    }

    /**
     * Remove item from collection
     *
     * @param int $key
     * @return BaseCollection
     */
    public function removeItem(int $key): BaseCollection
    {
        unset($this->items[$key]);
        return $this;
    }

    public function fetch(array $params): BaseCollection
    {
        $modelClass = $this->model;

        $conditions = [];
        foreach ($params as $param) {
            if (!$param['condition']) $param['condition'] = '=';

            if ($param['condition'] == 'in') {
                if (is_array($param['array'])) $conditions[] = $param['column'] . ' in (\'' . implode('\',\'', $param['array']) . '\')';
            } else {
                $conditions[] = $param['column'] . ' ' . $param['condition'] . ' ' . $param['value'];
            }


        }
//        print_r('--------------------------------------------------------------------------' . PHP_EOL);
//        print_r($conditions);
//      print_r('--------------------------------------------------------------------------' . PHP_EOL);

        $rows = $this->_table->fetchRows($conditions);

        foreach ($rows as $row) {
            /** @var BaseModel $model */
            $model = new $modelClass();
            $model->setData($row);
            $this->setItem($model);
        }
        return $this;
    }

    public function getIds(): array
    {
        $result = [];
        /** @var BaseModel $item */
        foreach ($this->getItems() as $item) {
            $result[] = $item->getId();
        }

        return $result;
    }

    /**
     * @param int $id
     * @return CollectionItemInterface|null
     */
    public function getItem(int $id)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }
        return null;
    }

    public function count() {
        return count($this->items);
    }

    public function merge(BaseCollection $collection) {
        $idList = [];
        foreach($this->items as $item) {
            $idList[$item->getId()] = true;
        }
        
        foreach($collection->getItems() as $item) {
            if(!isset($idList[$item->getId()])) {
                $this->items[] = $item;
            }
        }
    }
}