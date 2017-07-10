<?php namespace LukeTowers\ActivityLog\Controllers;

use Lang;
use Flash;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;

use LukeTowers\ActivityLog\Models\Activity as ActivityModel;

/**
 * Settings Back-end Controller
 */
class Settings extends Controller
{
    public $requiredPermissions = ['luketowers.activitylog.manage_settings'];
    
    public $pageTitle = 'luketowers.activitylog::lang.plugin.name';
    
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('LukeTowers.ActivityLog', 'settings');
    }
    
    public function index() {}
    
    public function index_onEmptyLog()
    {
        ActivityModel::truncate();
        Flash::success(Lang::get('luketowers.activitylog::lang.settings.empty_log_success'));
    }
}
