<?php namespace LukeTowers\EasyAudit\Controllers;

use Lang;
use Flash;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\SettingsManager;

use LukeTowers\EasyAudit\Models\Activity as ActivityModel;

/**
 * Settings Back-end Controller
 */
class Settings extends Controller
{
    public $requiredPermissions = ['luketowers.easyaudit.manage_settings'];

    public $pageTitle = 'luketowers.easyaudit::lang.plugin.name';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Winter.System', 'system', 'settings');
        SettingsManager::setContext('LukeTowers.EasyAudit', 'settings');
    }

    public function index() {}

    public function index_onEmptyLog()
    {
        ActivityModel::truncate();
        Flash::success(Lang::get('luketowers.easyaudit::lang.settings.empty_log_success'));
    }
}
