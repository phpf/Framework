<?php

namespace Phpf\Event {
	
	class Functional {
		// dummy class
	}
}

namespace {
	
	/**
	 * Phpf\Event functions
	 */	
	function on_event($eventId, $callback, $priority = \Events::DEFAULT_PRIORITY) {
		\App::instance()->get('events')->on($eventId, $callback, $priority);
	}
	
	function trigger_event($event /* [, $args [, ...]] */ ) {
		$args = func_get_args();
		array_shift($args);
		return \App::instance()->get('events')->triggerArray($event, $args);
	}

}
