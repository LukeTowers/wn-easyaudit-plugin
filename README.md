# About

Easily view and manage audit logs for models within your OctoberCMS projects.

# Installation

To install from the [Marketplace](https://octobercms.com/plugin/luketowers-easyaudit), click on the "Add to Project" button and then select the project you wish to add it to before updating the project to pull in the plugin.

To install from the backend, go to **Settings -> Updates & Plugins -> Install Plugins** and then search for `LukeTowers.EasyAudit`.

To install from [the repository](https://github.com/luketowers/oc-easyaudit-plugin), clone it into **plugins/luketowers/easyaudit** and then run `composer update` from your project root in order to pull in the dependencies.

To install it with Composer, run `composer require luketowers/oc-easyaudit-plugin` from your project root.

# Documentation

To get started using this plugin just add a few properties to your models that you wish to track:

```php
class MyModel extends Model
{
    /**
     * @var array Behaviors implemented by this model class
     */
    public $implement = ['@LukeTowers.EasyAudit.Behaviors.TrackableModel'];

    /**
     * @var bool Flag to allow identical activities being logged on the same request. Default is to prevent duplicates
     * /
    protected $trackableAllowDuplicates = false;

    /**
     * @var array The model events that are to be tracked as activities
     * /
    public $trackableEvents = ['model.afterSave', 'model.afterCreate', 'model.afterFetch'];

    /**
     * @var array The custom event names to override the default event names within the activity entry
     * /
    public $trackableEventNames = ['model.afterSave' => 'updated', 'model.afterCreate' => 'created', 'model.afterFetch' => 'viewed'];

    /**
     * @var array The custom event descriptions to override the default event descriptions within the activity entry
     * /
    public $trackableEventDescriptions = ['model.afterSave' => 'The model was updated', 'model.afterCreate' => 'The model was created', 'model.afterFetch' => 'The model was viewed'];
}
```

You can view a model's acitivities by including the default relation controller configuration file directly in your controller (`public $relationConfig = '$/luketowers/easyaudit/yaml/relation.activities.yaml';`) or by merging it with your existing relation config:

```php
class MyController extends Controller
{
    public function __construct()
    {
        $this->relationConfig = $this->mergeConfig($this->relationConfig, '$/luketowers/easyaudit/yaml/relation.activities.yaml');
        parent::__construct();
    }
}
```

In order to display the relation controller's views, just add the following field config to your model's `fields.yaml` file:

```yaml
_activities:
    tab: luketowers.easyaudit::lang.models.activity.label_plural
    type: partial
    path: $/luketowers/easyaudit/partials/field.activities.htm
```

# Advanced Usage

It is also possible to log events directly using the `LukeTowers\EasyAudit\Classes\ActivityLogger` class:

To use, create a new instance of this class and then either chain the methods for the data as required or call the `log()` method directly.
Example (all in one):

```php
$activity = new ActivityLogger();
$activity->log('updated', 'MyModel updated', $myModel, BackendAuth::getUser(), ['maintenanceMode' => true], 'MyVendor.MyPlugin');
```

Or (chained):

```php
$activity = new ActivityLogger();
$activity->inLog('MyVendor.MyPlugin')
        ->for($myModel)
        ->by(BackendAuth::getUser())
        ->description('MyModel updated')
        ->properties(['maintenanceMode' => true])
        ->log('updated');
```