<?php

namespace LukeTowers\EasyAudit\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;

/**
 * ChangeViewer Form Widget
 */
class ChangeViewer extends FormWidgetBase
{
    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'luketowers_easyaudit_change_viewer';

    /**
     * @inheritDoc
     */
    public function init()
    {
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('changeviewer');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->getLoadValue();
        $this->vars['model'] = $this->model;
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addJs('dist/js/changeviewer.js', 'LukeTowers.EasyAudit');
        $this->addCss('dist/css/changeviewer.css', 'LukeTowers.EasyAudit');
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }
}
