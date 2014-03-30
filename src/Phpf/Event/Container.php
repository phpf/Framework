<?php

namespace Phpf\Event;

class Container
{

	const SORT_LOW_HIGH = 1;

	const SORT_HIGH_LOW = 2;

	const DEFAULT_PRIORITY = 10;

	protected $order;

	protected $events = array();

	protected $listeners = array();

	protected $completed = array();

	/**
	 * Constructor
	 * Sets the default sort order (low to high) and
	 * a context if given.
	 */
	public function __construct($context = null) {

		$this->order = static::SORT_LOW_HIGH;

		if (isset($context))
			$this->context = $context;
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

		if (! isset($this->listeners[$event]))
			$this->listeners[$event] = array();

		$this->listeners[$event][] = array($call, $priority);

		return $this;
	}

	/**
	 * Triggers an event.
	 *
	 * @param Event|string $event Event object or ID
	 * @return array Items returned from event listeners.
	 */
	public function trigger($event) {

		// prepare the event
		$prepared = $this->prepare($event);

		if (false === $prepared) {
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
	 */
	public function triggerArray($event, array $args) {

		$prepared = $this->prepare($event);

		if (false === $prepared) {
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
	public function getEvent($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['event'] : null;
	}

	/**
	 * Returns the array that was returned from a completed Event trigger.
	 *
	 * This allows you to access previously returned values (obviously).
	 *
	 * @param string $eventId The event's ID
	 * @return array Values returned from the event's listeners
	 */
	public function getEventResult($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['result'] : null;
	}

	/**
	 * Sets the listener priority sort order.
	 *
	 * @param int $order One of self::SORT_LOW_HIGH (1) or self::SORT_HIGH_LOW (2)
	 * @return $this
	 */
	public function setSortOrder($order) {

		if ($order != self::SORT_LOW_HIGH && $order != self::SORT_HIGH_LOW) {
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
	 */
	protected function prepare($event) {

		if (! $event instanceof Event) {

			if (! is_string($event)) {
				$msg = "Event must be string or instance of Event - ".gettype($event)." given.";
				throw new \InvalidArgumentException($msg);
			}

			$event = new Event($event);
		}

		$return = array();

		if (! isset($this->listeners[$event->id])) {
			return false;
		}

		$listeners = $this->listeners[$event->id];

		// lazy-load the listeners
		array_walk($listeners, function(&$val) use ($event) {
			$val = new Listener($event->id, $val[0], $val[1]);
		});

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
	 * Get array of Listeners for an event.
	 *
	 * @param string $event Event ID
	 * @return array Event listeners
	 */
	protected function getListeners($event) {
		return isset($this->listeners[$event]) ? $this->listeners[$event] : array();
	}

	/**
	 * Listener sort function
	 *
	 * @param Listener $a
	 * @param Listener $b
	 * @return int sort result
	 */
	protected function sortListeners(Listener $a, Listener $b) {

		if ($this->order === static::SORT_LOW_HIGH) {

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
