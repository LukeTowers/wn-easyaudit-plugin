<?php namespace LukeTowers\EasyAudit\FormWidgets;

use Model;
use BackendAuth;
use ApplicationException;
use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;

use LukeTowers\EasyAudit\Models\Activity;

/**
 * ActivityLog Form Widget
 *
 * Configuration:
 *     form: config_form.yaml
 *     list: config_list.yaml
 *     toolbar: true | default: false
 *     filter: true | default: false
 *     subject:
 *        - false | no filter applied
 *        - formModel | default
 *        - $modelInstance | A model instance to be passed on
 *        - callable | A callable method to get the model instance to use
 *        - string | A model class to use
 *     source:
 *        - false | default, no filter applied
 *        - currentUser | the current user
 *        - formModel | The model for the containing form
 *        - $modelInstance | A model instance to be passed on
 *        - callable | A callable method to get the model instance to use
 *        - string | A model class to use
 */
class ActivityLog extends FormWidgetBase
{
    //
    // Configurable properties
    //

    /**
     * @var array List configuration
     */
    public $list = '$/luketowers/easyaudit/formwidgets/activitylog/config/list.yaml';

    /**
     * @var array Form field configuration
     */
    public $form = '$/luketowers/easyaudit/models/activity/fields.yaml';

    /**
     * @var mixed The subject to filter the activity log for
     */
    public $subject = 'formModel';

    /**
     * @var mixed The source to filter the activity log for
     */
    public $source = false;

    /**
     * @var boolean Flag to indicate that the toolbar should be used
     */
    public $toolbar = false;

    /**
     * @var boolean Flag to indicate that the filter should be used
     */
    public $filter = false;

    //
    // Internal properties
    //

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'activitylog';

    /**
     * @var \Backend\Widgets\Toolbar Reference to the toolbar widget object.
     */
    protected $toolbarWidget;

    /**
     * @var \Backend\Widgets\Filter Reference to the filter widget object.
    */
    protected $filterWidget;

    /**
     * @var \Backend\Widgets\Lists Reference to the list widget object.
     */
    protected $listWidget;

    /**
     * @var \Backend\Widgets\Form Reference to the form widget object.
     */
    protected $formWidget;

    /**
     * @inheritDoc
     */
    public function init()
    {
        // Populate configuration
        $this->fillFromConfig([
            'list',
            'form',
            'subject',
            'source',
            'toolbar',
            'filter',
        ]);

        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }

        // Initialize the widgets
        $this->getListWidget();
        $this->getFormWidget();
    }

    /**
     * Get the subject to filter the list by
     *
     * @return Model|false
     */
    protected function getSubject()
    {
        $subject = false;

        if (is_callable($this->subject)) {
            $subject = call_user_func($this->subject, $this);
        } elseif (is_object($this->subject)) {
            $subject = $this->subject;
        } else {
            switch ($this->subject) {
                case 'formModel':
                    $subject = $this->model;
                    break;
                case false:
                    $subject = false;
                    break;
                default:
                    if (class_exists($this->subject)) {
                        $subject = new $this->subject;
                    }
                    break;
            }
        }

        if ($subject !== false && !($subject instanceof Model)) {
            $subject = false;
        }

        return $subject;
    }

    /**
     * Get the source to filter the list by
     *
     * @return Model|false
     */
    protected function getSource()
    {
        $source = false;

        if (is_callable($this->source)) {
            $source = call_user_func($this->source, $this);
        } elseif (is_object($this->source)) {
            $source = $this->source;
        } else {
            switch ($this->source) {
                case 'currentUser':
                    $source = BackendAuth::getUser();
                    break;
                case 'formModel':
                    $source = $this->model;
                    break;
                case false:
                    $source = false;
                    break;
                default:
                    if (class_exists($this->source)) {
                        $source = new $this->source;
                    }
                    break;
            }
        }

        if ($source !== false && !($source instanceof Model)) {
            $source = false;
        }

        return $source;
    }

    /**
     * Get the Toolbar widget used by this FormWidget
     *
     * @return Backend\Widgets\Form The intialized Toolbar widget
     */
    protected function getToolbarWidget()
    {
        return $this->toolbarWidget;
    }

    /**
     * Get the Filter widget used by this FormWidget
     *
     * @return Backend\Widgets\Filter The intialized Filter widget
     */
    protected function getFilterWidget()
    {
        return $this->filterWidget;
    }

    /**
     * Get the Lists widget used by this FormWidget
     *
     * @return Backend\Widgets\Lists The intialized Lists widget
     */
    protected function getListWidget()
    {
        if ($this->listWidget) {
            return $this->listWidget;
        }

        // Initialize the list widget
        $listConfig = $this->makeConfig($this->list);

        /*
         * Create the model
         */
        $class = $listConfig->modelClass;
        $model = new $class;

        /*
         * Prepare the list widget
         */
        $columnConfig = $this->makeConfig($listConfig->list);
        $columnConfig->model = $model;
        $columnConfig->alias = $this->alias . 'List';

        /*
         * Prepare the columns configuration
         */
        $configFieldsToTransfer = [
            'recordUrl',
            'recordOnClick',
            'recordsPerPage',
            'showPageNumbers',
            'noRecordsMessage',
            'defaultSort',
            'showSorting',
            'showSetup',
            'showCheckboxes',
            'showTree',
            'treeExpanded',
            'customViewPath',
        ];

        foreach ($configFieldsToTransfer as $field) {
            if (isset($listConfig->{$field})) {
                $columnConfig->{$field} = $listConfig->{$field};
            }
        }

        /*
         * List Widget
         */
        $widget = $this->makeWidget('Backend\Widgets\Lists', $columnConfig);

        // Filter activities based on the subject and source properties
        $widget->bindEvent('list.extendQuery', function ($query) {
            $subject = $this->getSubject();
            if ($subject) {
                if (!empty($subject->getKey())) {
                    $query->forSubject($subject);
                } else {
                    $query->where('subject_type', get_class($subject));
                }
            }

            $source = $this->getSource();
            if ($source) {
                if (!empty($source->getKey())) {
                    $query->fromSource($source);
                } else {
                    $query->where('source_type', get_class($source));
                }
            }
        });
        $widget->bindToController();

        /*
         * Prepare the toolbar widget (optional)
         */
        if (isset($listConfig->toolbar) && $this->toolbar === true) {
            $toolbarConfig = $this->makeConfig($listConfig->toolbar);
            $toolbarConfig->alias = $widget->alias . 'Toolbar';
            $toolbarWidget = $this->makeWidget('Backend\Widgets\Toolbar', $toolbarConfig);
            $toolbarWidget->bindToController();
            $toolbarWidget->controller->addViewPath($this->viewPath);
            $toolbarWidget->cssClasses[] = 'list-header';

            /*
             * Link the Search Widget to the List Widget
             */
            if ($searchWidget = $toolbarWidget->getSearchWidget()) {
                $searchWidget->bindEvent('search.submit', function () use ($widget, $searchWidget) {
                    $widget->setSearchTerm($searchWidget->getActiveTerm());
                    return $widget->onRefresh();
                });

                $widget->setSearchOptions([
                    'mode' => $searchWidget->mode,
                    'scope' => $searchWidget->scope,
                ]);

                // Find predefined search term
                $widget->setSearchTerm($searchWidget->getActiveTerm());
            }

            $this->toolbarWidget = $toolbarWidget;
        }

        /*
         * Prepare the filter widget (optional)
         */
        if (isset($listConfig->filter) && $this->filter === true) {
            $widget->cssClasses[] = 'list-flush';

            $filterConfig = $this->makeConfig($listConfig->filter);
            $filterConfig->alias = $widget->alias . 'Filter';
            $filterWidget = $this->makeWidget('Backend\Widgets\Filter', $filterConfig);
            $filterWidget->bindToController();

            /*
             * Filter the list when the scopes are changed
             */
            $filterWidget->bindEvent('filter.update', function () use ($widget, $filterWidget) {
                return $widget->onFilter();
            });

            // Apply predefined filter values
            $widget->addFilter([$filterWidget, 'applyAllScopesToQuery']);

            $this->filterWidget = $filterWidget;
        }

        return $this->listWidget = $widget;
    }

    /**
     * Get the Form widget used by this FormWidget
     *
     * @return Backend\Widgets\Form The intialized Form widget
     */
    protected function getFormWidget()
    {
        if ($this->formWidget) {
            return $this->formWidget;
        }

        // Configure the Form widget
        $config = $this->makeConfig($this->form);
        $config->model = $this->getCurrentActivity();
        $config->arrayName = $this->alias . 'Form';
        $config->isNested = true;

        // Initialize the Form widget
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->previewMode = true;
        $widget->bindToController();

        return $this->formWidget = $widget;
    }

    /**
     * Get the currently active activity record
     *
     * @return Activity
     */
    protected function getCurrentActivity()
    {
        $activity = Activity::find(post('recordId'));

        if (!$activity) {
            $activity = new Activity;
        }

        return $activity;
    }

    /**
     * AJAX handler to view a specific activity item's details
     *
     * @return string
     */
    public function onViewLogItemDetails()
    {
        $this->prepareVars();
        return $this->makePartial('form', $this->vars, false);
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->addCss(['less/activitylog.less'], 'LukeTowers.EasyAudit');
        $this->addJs('js/activitylog.js', 'LukeTowers.EasyAudit');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }

        $this->vars['toolbar'] = $this->getToolbarWidget();
        $this->vars['filter']  = $this->getFilterWidget();
        $this->vars['list']    = $this->getListWidget();
        $this->vars['form']    = $this->getFormWidget();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('default');
    }

    /**
     * Process the postback value for this widget. If the value is omitted from
     * postback data, it will be NULL, otherwise it will be an empty string.
     *
     * @param mixed $value The existing value for this widget.
     * @return string The new value for this widget.
     */
    public function getSaveValue($value)
    {
        return FormField::NO_SAVE_DATA;
    }
}