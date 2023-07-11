<?php

namespace RedooCalendar\Base\ActionController;

use RedooCalendar\Base\Connection\Connection;
use RedooCalendar\Base\Connection\ConnectorPlugin\ConnectorPluginInterface;
use RedooCalendar\Helper\Translator;
use Vtiger_Action_Controller;
use Vtiger_Request;

/**
 * Class BaseActionController
 * @package RedooCalendar\Base\ActionController
 */
abstract class BaseActionController extends Vtiger_Action_Controller
{
    use Translator;

    protected $connector = null;
    protected $user;

    public function __construct()
    {
        $this->user = \Users_Record_Model::getCurrentUserModel();
        parent::__construct();
    }

    /**
     * Get external calendar connector
     *
     * @return ConnectorPluginInterface
     */
    protected function getConnector(): ConnectorPluginInterface
    {
        if (!$this->connector) {
            $request = new Vtiger_Request($_REQUEST);
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