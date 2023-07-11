<?php
namespace RedooCalendar;
/**
 * Updater class to handle module update database migrations
 *
 * @CHANGELOG
 * [1.0] 2020-03-05
 *      - First release
 *
 * @example
 *   // Migrations must be placed within a "migrations" folder within the module.
 *   // Processing will be in alphabetical order
 *
 *   $moduleName = "ModuleName";
 *
 *    // When used in MODCheckDB.php:
 *    $moduleName = basename(dirname(__FILE__));
 *
 *   try {
 *      $updater = new \ModuleNamePlaceholder\Updater($moduleName);
 *      $updater->update();
 *   } catch (\Exception $exp) {
 *      echo 'Error during Setup of ' . $moduleName.': ' . $exp->getMessage();
 *   }
 *
 * @author Redoo Networks GmbH (SW)
 * @version 1.0
 */
class Updater
{
    /**
     * @var string
     */
    private $migrationsPath;

    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var integer|null
     */
    private $tabId;

    public function __construct($moduleName)
    {
        global $root_directory;

        $this->moduleName = $moduleName;
        $this->tabId = getTabid($moduleName);
        $this->migrationsPath = $root_directory . DS . 'modules' . DS . $this->moduleName . DS . 'migrations' . DS;

        if(empty($this->tabId)) {
            throw new \Exception('Module "'.$moduleName.'" you want to update, is not installed');
        }

        $this->checkUpdateTable();
        $this->registerAutoloader();
    }

    public function update()
    {
        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT `update` FROM vtiger_redoomod_update WHERE tabid = ?';
        $result = VtUtils::fetchRows($sql, $this->tabId);

        $done = array();
        foreach($result as $row) {
            $done[] = html_entity_decode($row['update'], ENT_QUOTES);
        }

        $files = glob($this->migrationsPath  . '*.php');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            $filename = str_replace('.php', '', $filename);
            $filename = preg_replace('/[^a-zA-Z0-9-_ ]/', '', $filename);

            if (in_array($filename, $done)) {
                continue;
            }

            $processor = require_once($file);

            try {
                if(!empty($processor) && is_callable($processor)) {
                    $arguments = array();
                    $refFunc = new \ReflectionFunction($processor);
                    $parameters = $refFunc->getParameters();
                    foreach($parameters as $parameter) {
                        switch(strtolower($parameter->getName())) {
                            case 'adb':
                            case 'db':
                            case 'database':
                                $arguments[] = $adb;
                                break;
                            case 'modulename':
                                $arguments[] = $this->moduleName;
                                break;
                        }
                    }
                    call_user_func_array($processor, $arguments);
                }
            } catch (\Exception $exception) {
                $this->log('['.$this->moduleName . ':' . $filename.'] Error: ' . $exception->getMessage());
                continue;
            }

            $sql = 'INSERT INTO vtiger_redoomod_update SET `tabid` = ?, `update` = ?';
            $adb->pquery($sql, array($this->tabId, $filename));

            $this->log('[' . $this->moduleName . ':' . $filename . '] Update completed');
        }

        if(!empty($files)) {
            $this->log('[' . $this->moduleName . '] No update required');
        }

    }

    /** Private functions */
    private function log($log) {
        // Do nothing at the moment
        echo $log.'<br/>';
    }

    private function registerAutoloader() {
        spl_autoload_register(function($class) {
            if (stripos($class, __NAMESPACE__) === 0)
            {
                $file = realpath(
                    __DIR__ . DIRECTORY_SEPARATOR .
                    str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen(__NAMESPACE__))) . '.php');

                if(!empty($file)) {
                    include_once(
                        __DIR__ . DIRECTORY_SEPARATOR .
                        str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen(__NAMESPACE__))) . '.php'
                    );
                }
            }
        });
    }

    private function checkUpdateTable()
    {
        if(VtUtils::existTable('vtiger_redoomod_update') === false) {
            $sql = 'CREATE TABLE `vtiger_redoomod_update` (
 `tabid` int(11) NOT NULL,
 `update` varchar(64) NOT NULL,
 `done` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (`tabid`,`update`)
) ENGINE=InnoDB';
            VtUtils::query($sql);
        }

    }

}
