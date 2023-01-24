<?php

namespace LukeTowers\EasyAudit\Controllers;

use ApplicationException;
use Backend\Classes\Controller;
use BackendAuth;
use BackendMenu;
use Flash;
use Lang;
use LukeTowers\EasyAudit\Models\Activity as ActivityModel;
use System\Classes\SettingsManager;

/**
 * Activities Backend Controller
 */
class Activities extends Controller
{
    use \LukeTowers\EasyAudit\Traits\CanViewActivityRecord;

    public $implement = [
        \Backend\Behaviors\ListController::class,
    ];

    public $listConfig = 'config_list.yaml';
    public $requiredPermissions = ['luketowers.easyaudit.*'];

    public function __construct()
    {
        // Restrict the filter options if the user doesn't have access to view all records
        if (!static::userHasAccess('luketowers.easyaudit.activities.view_all')) {
            $this->listConfig = $this->makeConfig($this->listConfig);
            $this->listConfig->filter = $this->makeConfig($this->listConfig->filter);

            foreach ($this->listConfig->filter->scopes as &$scope) {
                if (!empty($scope['modelClass']) && is_string($scope['options']) && class_exists($scope['modelClass'])) {
                    $scope['options'] = (new $scope['modelClass'])->{$scope['options']}(null, BackendAuth::getUser());
                }
            }
        }

        parent::__construct();

        $this->addJs('/plugins/luketowers/easyaudit/assets/js/activityController.js', 'LukeTowers.EasyAudit');

        BackendMenu::setContext('Winter.System', 'system', 'settings');
        SettingsManager::setContext('LukeTowers.EasyAudit', 'logs');
    }

    public function listExtendQuery($query)
    {
        if (!static::userHasAccess('luketowers.easyaudit.activities.*')) {
            $query->where('id', '0');
        }

        if (!static::userHasAccess('luketowers.easyaudit.activities.view_all')) {
            $query->fromSource(BackendAuth::getUser());
        }

        return $query;
    }

    public function index_onEmptyLog()
    {
        $logger = new \LukeTowers\EasyAudit\Classes\ActivityLogger();
        $user = BackendAuth::getRealUser();
        if ($user) {
            $logger->by($user);
        }
        $logger->inLog('LukeTowers.EasyAudit');

        if (!$user->hasAccess('luketowers.easyaudit.clear_logs')) {
            $logger->event('attempted_clear_log');
            $logger->description("Attempted to clear the log");
            $logger->log();
            throw new ApplicationException('You do not have permissions to do that');
        }

        ActivityModel::truncate();
        Flash::success(Lang::get('luketowers.easyaudit::lang.settings.empty_log_success'));
        $logger->event('cleared_log');
        $logger->description("Cleared the log");
        $logger->log();
    }

    /**
     * Checks if a user has access to the provided permission
     */
    public static function userHasAccess(string $permission): bool
    {
        $user = BackendAuth::getUser();
        // If the user isn't even logged in then deny access
        if (!$user) {
            return false;
        }
        return $user->hasAccess($permission);
    }
}
