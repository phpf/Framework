<?php

namespace Phpf\Event;

/**
 * Event container.
 * 
 * Class for binding and triggering events.
 * 
 * Public methods:
 * @method on()				Bind a callback to an event.
 * @method trigger()		Triggers an event with arguments.
 * @method triggerArray()	Triggers an event with an array of arguments.
 * @method event()			Returns a completed event object.
 * @method result()			Returns the array of results returned from a completed event.
 * @method orderBy()		Set the priority ordering; default is low to high.
 */
class Container
{

	const LOW_TO_HIGH = 1;

	const HIGH_TO_LOW = 2;

	const DEFAULT_PRIORITY = 10;

	protected $order;

	protected $events = array();

	protected $listeners = array();

	protected $completed = array();

	/**
	 * Sets the default sort order (low to high).
	 */
	public function __construct() {
		$this->order = static::LOW_TO_HIGH;
	}

	/**
	 * Adds an event listener (real listeners are lazy-loaded).
	 *
	 * @param string $event Event ID
	 * @param mixed $call Callable to execute on event
	 * @param int $priority Priority to give to the listener
	 * @return $this
	 */
	public function on($event, $call, $priority = self::DEFAULT_PRIORITY) {

		if (! isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
		}
		
		$this->listeners[$event][] = array($call, $priority);

		return $this;
	}

	/**
	 * Triggers an event.
	 *
	 * @param Event|string $event Event object or ID
	 * @param ... Args
	 * @return array Items returned from event listeners.
	 */
	public function trigger($event) {

		// prepare the event
		if (false === ($prepared = $this->prepare($event))) {
			return null;
		}

		list($event, $listeners) = $prepared;

		// get args
		$args = func_get_args();

		// remove event from args
		array_shift($args);

		return $this->execute($event, $listeners, $args);
	}

	/**
	 * Triggers an event given an array of arguments.
	 * 
	 * @param Event|string $event Event object or ID.
	 * @param array $args Args to pass to listeners.
	 * @return array Items returned from event Listeners.
	 */
	public function triggerArray($event, array $args = array()) {

		if (false === ($prepared = $this->prepare($event))) {
			return null;
		}

		list($event, $listeners) = $prepared;

		return $this->execute($event, $listeners, $args);
	}

	/**
	 * Returns a completed Event object.
	 *
	 * @param string $eventId The event's ID
	 * @return Event The completed Event object.
	 */
	public function event($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['event'] : null;
	}

	/**
	 * Returns the array that was returned from a completed Event trigger.
	 *
	 * This allows you to access previously returned values (obviously).
	 *
	 * @param string $eventId The event's ID
	 * @return array Values returned from the event's listeners, else null.
	 */
	public function result($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['result'] : null;
	}

	/**
	 * Sets the listener priority sort order.
	 *
	 * @param int $order One of self::LOW_TO_HIGH (1) or self::HIGH_TO_LOW (2)
	 * @return $this
	 */
	public function orderBy($order) {

		if ($order != self::LOW_TO_HIGH && $order != self::HIGH_TO_LOW) {
			throw new \OutOfBoundsException("Invalid sort order.");
		}

		$this->order = (int)$order;

		return $this;
	}

	/**
	 * Prepares the event to execute.
	 * Lazy-loads Listener objects.
	 *
	 * @param string|Event $event The event name/object to trigger.
	 * @return boolean|array False if no listeners, otherwise indexed array of Event
	 * object and array of listeners.
	 * @throws InvalidArgumentException if event is not an Event object or a string.
	 */
	protected function prepare($event) {

		if (! $event instanceof Event) {

			if (! is_string($event)) {
				$msg = "Event must be string or instance of Event - ".gettype($event)." given.";
				throw new \InvalidArgumentException($msg);
			}

			$event = new Event($event);
		}

		if (! isset($this->listeners[$event->id])) {
			return false;
		}

		$listeners = $this->listeners[$event->id];

		// lazy-load the listeners
		foreach($listeners as $key => &$value) {
			$value = new Listener($event->id, $value[0], $value[1]);
		}
		
		return array($event, $listeners);
	}

	/**
	 * Executes the event listeners.
	 * Sorts, calls, and returns result.
	 *
	 * @param Event $event Event object
	 * @param array $listeners Array of Listener objects
	 * @param array $args Callback arguments
	 * @return array Array of event callback results
	 */
	protected function execute(Event $event, array $Listeners, array $args = array()) {

		$return = array();

		// Sort the listeners.
		usort($Listeners, array($this, 'sortListeners'));

		// Call the listeners
		foreach ( $Listeners as $listener ) {

			$return[] = $listener($event, $args);

			// Return if listener has stopped propagation
			if ($event->isPropagationStopped()) {

				$this->complete($event, $return);

				return $return;
			}
		}

		$this->complete($event, $return);

		return $return;
	}

	/**
	 * Stores the Event and its return array once the last listener has been called.
	 *
	 * @param Event $event The completed event object.
	 * @param array $return The returned array
	 * @return void
	 */
	protected function complete(Event $event, array $return) {
		$this->completed[$event->id] = array('event' => $event, 'result' => $return);
	}

	/**
	 * Listener sort function
	 *
	 * @param Listener $a
	 * @param Listener $b
	 * @return int sort result
	 */
	protected function sortListeners(Listener $a, Listener $b) {

		if ($this->order === static::LOW_TO_HIGH) {

			if ($a->priority >= $b->priority) {
				return 1;
			}

			return - 1;

		} else {

			if ($a->priority <= $b->priority) {
				return 1;
			}

			return - 1;
		}
	}

}
