<?php

namespace RedooCalendar\Base\Collection;

use RedooCalendar\Base\Collection\Item\CollectionItemInterface;

/**
 * Class BaseExternalCollection
 * @package RedooCalendar\Base\Collection
 */
class BaseExternalCollection implements CollectionInterface
{
    protected $items = [];

    /**
     * Get collection items as array models
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get collection items as array
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

            /** @var CollectionItemInterface $relation */

            foreach ($item->getRelations() as $key => $relation) {
                $_item['relations'][$key] = $relation->getItemsAsArray();
            }

            $resultArray[] = $_item;
        }
        return $resultArray;
    }

    /**
     * Add item to collection
     *
     * @param CollectionItemInterface $item
     * @return BaseExternalCollection
     */
    public function setItem(CollectionItemInterface $item): BaseExternalCollection
    {
        $this->items[$item->getId()] = $item;
        return $this;
    }

    /**
     * Remove item from collection
     *
     * @param $key
     * @return BaseExternalCollection
     */
    public function removeItem($key): BaseExternalCollection
    {
        unset($this->items[$key]);
        return $this;
    }

    public function getItem(string $id)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }
        return null;
    }
}
