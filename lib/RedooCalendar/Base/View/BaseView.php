<?php


namespace RedooCalendar\Base\View;


use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Helper\Translator;

class BaseView extends \Vtiger_Index_View
{
    use Translator;

    protected $user;
    protected $connector;

    public function __construct()
    {
        $this->user = \Users_Record_Model::getCurrentUserModel();
        parent::__construct();
    }


    /**
     * Get external calendar connector
     *
     * @return ConnectorPluginInterface
     * @throws \Exception
     */
    protected function getConnector(): ConnectorPluginInterface
    {
        if (!$this->connector) {
            $request = new \Vtiger_Request($_REQUEST);
            if ($request->has('connector')) {
                $this->connector = Connection::GetInstance($request->get('connector'))->getConnector();
            } else {
                throw new \Exception('Connector not provided in request');
            }
        }
        return $this->connector;
    }

    public function getUser(): \Users_Record_Model
    {
        return $this->user;
    }
}