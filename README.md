# About

Easily view and manage audit logs for models within your OctoberCMS projects.

# Installation

To install from the [Marketplace](https://octobercms.com/plugin/luketowers-easyaudit), click on the "Add to Project" button and then select the project you wish to add it to before updating the project to pull in the plugin.

To install from the backend, go to **Settings -> Updates & Plugins -> Install Plugins** and then search for `LukeTowers.EasyAudit`.

To install from [the repository](https://github.com/luketowers/oc-easyaudit-plugin), clone it into **plugins/luketowers/easyaudit** and then run `composer update` from your project root in order to pull in the dependencies.

To install it with Composer, run `composer require luketowers/oc-easyaudit-plugin` from your project root.

# Documentation

To get started using this plugin just add a few properties to the models that you wish to track:

```php
class MyModel extends Model
{
    /**
     * @var array Behaviors implemented by this model class
     */
    public $implement = ['@LukeTowers.EasyAudit.Behaviors.TrackableModel'];

    /**
     * @var array The model events that are to be tracked as activities
     */
    public $trackableEvents = [
        'model.afterCreate' => ['name' => 'created', 'description' => 'The record was created'],
        'model.afterUpdate' => ['name' => 'updated', 'description' => 'The record was updated'],
        'model.afterDelete' => ['name' => 'archived', 'description' => 'The record was archived'],
    ];
}
```

Once you've added the `TrackableModel` behavior to a model, any local events fired on the model that have been set up in `$trackableEvents` will be automatically listened to and an audit record will be generated for each event.

In addition to the properties above, you can also add the following properties to model classes to configure the audit logging behavior:

```php
class MyModel extends Model
{
    // ...

    /**
     * @var bool Flag to allow identical activities being logged on the same request. Defaults to false
     */
    protected $trackableAllowDuplicates = true;

    /**
     * @var bool Disable the IP address logging on this model (default from the luketowers.easyaudit.logIpAddress configuration value negated)
     */
    public $trackableDisableIpLogging = true;

    /**
     * @var bool Disable the User agent logging on this model (default from the luketowers.easyaudit.logUserAgent configuration value negated)
     */
    public $trackableDisableUserAgentLogging = true;
}
```

You can view a model's audit log by adding a field targeting the `activities` relationship (added by the `TrackableModel` behavior) with the type of `activitylog` to the relevant `fields.yaml` files:

```php
activities:
    tab: Audit Log
    context: [update, preview]
    type: activitylog
    span: full
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

Additionally, the ActivityLogger() class is available on models implementing the `LukeTowers.EasyAudit.Behaviors.TrackableModel` behavior through the `activity()` method.
This enables you to do the following:

```php
class Asset extends Model
{
    // ...

    public function updateInventory()
    {
        // ...
        $this->activity('updated_inventory')->description("The asset's inventory was updated")->log();
    }
}
```