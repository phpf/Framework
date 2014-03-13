<?php

namespace Phpf\Event;

class Listener {
	
	public $eventId;
	
	public $callback;
	
	public $priority;
	
	public function __construct($eventId, $callback, $priority){
		$this->eventId = $eventId;
		$this->callback = $callback;
		$this->priority = $priority;
	}
	
	public function __invoke( Event &$event, array $args = array() ){
		
		// Prepend Event to $args array
		array_unshift($args, $event);
		
		return call_user_func_array($this->callback, $args);
	}
	
}
