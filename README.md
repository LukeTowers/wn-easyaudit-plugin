# About

Easily view and manage audit logs for models within your Winter CMS projects.

# Installation

This plugin requires a [paid license ($30 USD)](https://paypal.me/theluketowers/30USD).

To install it with Composer, run `composer require luketowers/wn-easyaudit-plugin` from your project root.

# Documentation

To get started using this plugin just add the `TrackableModel` Behavior to any models that you wish to track:

```php
class MyModel extends Model
{
    /**
     * @var array Behaviors implemented by this model class
     */
    public $implement = ['@LukeTowers.EasyAudit.Behaviors.TrackableModel'];
}
```

Once you've added the `TrackableModel` behavior to a model, any local events fired on the model that have been set up in `$trackableEvents` will be automatically listened to and an audit record will be generated for each event.

By default, the `TrackableModel` behavior will listen to the following events:

- `model.afterCreate`
- `model.afterUpdate`
- `model.afterDelete`

In addition to the properties above, you can also add the following properties to model classes to configure the audit logging behavior:

```php
class MyModel extends Model
{
    // ...

    /**
     * @var array The model events that are to be tracked as activities
     */
    public $trackableEvents = [
        'model.afterCreate' => ['name' => 'created', 'description' => 'The record was created'],
        'model.afterUpdate' => ['name' => 'updated', 'description' => 'The record was updated'],
        'model.afterDelete' => ['name' => 'archived', 'description' => 'The record was archived'],
    ];

    /**
     * @var bool Manually control the IP address logging on this model (default from the luketowers.easyaudit.logIpAddress config setting)
     */
    public $trackableLogIpAddress = true;

    /**
     * @var bool Manually control the User agent logging on this model (default from the luketowers.easyaudit.logUserAgent config setting)
     */
    public $trackableLogUserAgent = true;

    /**
     * @var bool Manually control the change tracking on this model (default from the luketowers.easyaudit.trackChanges config setting)
     */
    public $trackableTrackChanges = true;

    /**
     * @var bool Manually control if the activities field gets automatically injected into backend forms
     * for this model (default from the luketowers.easyaudit.autoInjectActvitiesFormWidget config setting)
     */
    public $trackableInjectActvitiesFormWidget = true;
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
