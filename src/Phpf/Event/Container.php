<?php

namespace Phpf\Event;

class Container {
	
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
	public function __construct( $context = null ){

		$this->order = static::SORT_LOW_HIGH;
		
		if ( isset($context) )
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
	public function on( $event, $call, $priority = self::DEFAULT_PRIORITY ){
		
		if ( ! isset($this->listeners[$event]) )
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
	public function trigger( $event ){
		
		if (!$event instanceof Event){
			
			if (!is_string($event)){
				$msg = "Event must be string or instance of Event - " . gettype($event) . " given.";
				throw new \InvalidArgumentException($msg);
			}
			
			$event = new Event($event);
		}
		
		$return = array();
		
		$listeners = $this->getListeners($event->id);
		
		if ( empty($listeners) ){
			return $return;
		}
		
		// lazy-load the listeners
		array_walk($listeners, function (&$val) use ($event) {
			$val = new Listener($event->id, $val[0], $val[1]);
		});
		
		// Get arguments
		$args = func_get_args();
		
		// Remove event from arguments
		array_shift($args);
		
		// Sort the listeners.
		usort($listeners, array($this, 'sortListeners'));
		
		// Call the listeners
		foreach($listeners as $listener){
		
			$return[] = $listener($event, $args);
			
			// Return if listener has stopped propagation
			if ( $event->isPropagationStopped() ){
				
				$this->complete($event, $return);
				
				return $return;
			}
		}
		
		$this->complete($event, $return);
		
		return $return;
	}
	
	/**
	 * Returns a completed Event object.
	 * 
	 * @param string $eventId The event's ID
	 * @return Event The completed Event object.
	 */
	public function getEvent($eventId){
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
	public function getEventResult($eventId){
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['result'] : null;
	}
	
	/**
	 * Sets the listener priority sort order.
	 * 
	 * @param int $order One of self::SORT_LOW_HIGH (1) or self::SORT_HIGH_LOW (2)
	 * @return $this
	 */
	public function setSortOrder( $order ){
		
		if ($order != self::SORT_LOW_HIGH && $order != self::SORT_HIGH_LOW){
			throw new \InvalidArgumentException("Invalid sort order.");
		}
		
		$this->order = (int)$order;
		
		return $this;
	}
	
	/**
	 * Stores the Event and its return array once the last listener has been called.
	 * 
	 * @param Event $event The completed event object.
	 * @param array $return The returned array
	 * @return void
	 */
	protected function complete(Event $event, array $return){
		$this->completed[$event->id] = array(
			'event' => $event,
			'result' => $return
		);
	}
	
	/**
	* Get array of Listeners for an event.
	*
	* @param string $event Event ID
	* @return array Event listeners
	*/
	protected function getListeners($event){
		return isset($this->listeners[$event]) ? $this->listeners[$event] : array();
	}
	
	/**
	* Listener sort function
	*
	* @param Listener $a
	* @param Listener $b
	* @return int sort result
	*/
	protected function sortListeners(Listener $a, Listener $b){
		
		if ( $this->order === static::SORT_LOW_HIGH ){

			if ($a->priority >= $b->priority){
				return 1;
			}
			
			return -1;

		} else {
					
			if ($a->priority <= $b->priority){
				return 1;
			}
			
			return -1;
		}
	}
	
}
