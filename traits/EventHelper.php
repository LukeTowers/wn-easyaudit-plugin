<?php namespace LukeTowers\ActivityLog\Traits;

use Event;

/**
 * EventHelper trait.
 * Adds easier triggering of local and global events to any class
 */
trait EventHelper
{
	use \October\Rain\Support\Traits\Emitter;

	/**
	 * Prefix to remove for local events
	 * If empty will remove first section of event key: 'component.action' global would equal 'action' local
	 * @var string
	 * /
	 const EVENT_PREFIX = '';
	*/

    /**
     * Fires a combination of local and global events. The first segment is removed
     * from the event name locally and the local object is passed as the first
     * argument to the event globally. Halting is also enabled by default.
     *
     * For example:
     *
     *   $this->fireCombinedEvent('backend.list.myEvent', ['my value'], true, true);
     *
     * Is equivalent to:
     *
     *   $this->fireEvent('list.myEvent', ['myvalue'], true);
     *
     *   Event::fire('backend.list.myEvent', [$this, 'myvalue'], true);
     *
     * @param string $event Event name
     * @param array $params Event parameters
     * @param boolean $halt Halt after first non-null result
     * @param boolean $prefixed The passed event has already been prefixed, remove it for the local event. Otherwise add it for the global event
     * @return mixed
     */
    public function fireCombinedEvent($event, $params = [], $halt = true, $prefixed = false)
    {
        $result = [];
        $prefix = $this->getEventPrefix();

        $shortEvent = $prefixed ? substr($event, strpos($event, $this->getEventPrefix()) + 1) : $event;
        $event = $prefixed ? $event : $prefix . $event;
        $longArgs = array_merge([$this], $params);

        /*
         * Local event first
         */
        if ($response = $this->fireEvent($shortEvent, $params, $halt)) {
            if ($halt) {
                return $response;
            }
            else {
                $result = array_merge($result, $response);
            }
        }
        /*
         * Global event second
         */
        if ($response = Event::fire($event, $longArgs, $halt)) {
            if ($halt) {
                return $response;
            }
            else {
                $result = array_merge($result, $response);
            }
        }
        return $result;
    }

    /**
	 * Gets the class' event prefix
	 */
    public function getEventPrefix()
    {
	    return defined('static::EVENT_PREFIX') ? static::EVENT_PREFIX . '.' : '.';
    }
}