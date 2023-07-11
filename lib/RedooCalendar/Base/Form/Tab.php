<?php
/**
 * Created by PhpStorm.
 * User: StefanWarnat
 * Date: 15.11.2018
 * Time: 16:53
 */

namespace RedooCalendar\Base\Form;

use RedooCalendar\Base\Form;
use RedooCalendar\Helper\Translator;

class Tab
{
    use Translator;

    protected $Form = null;
    protected $Groups = array();
    protected $model = '';

    protected $TabLabel = '';
    protected $FullsizeTab = false;

    public function __construct(Form $form)
    {

        $this->Form = $form;

    }

    public function addGroup($headline = '')
    {
        $tmp = new Group($this, $this->Form);
        $tmp->setHeadline($headline);

        $this->Groups[] = $tmp;

        return $tmp;
    }

    public function disablePaddings()
    {
        $this->FullsizeTab = true;
    }

    public function skipPaddings()
    {
        return $this->FullsizeTab === true;
    }

    public function setTabLabel($headline)
    {
        $this->TabLabel = self::t($headline);
    }

    public function getTabLabel()
    {
        return $this->TabLabel;
    }

    public function getGroups()
    {
        return $this->Groups;
    }

    public function setModel(string $model): Tab
    {
        $this->model = $model;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
