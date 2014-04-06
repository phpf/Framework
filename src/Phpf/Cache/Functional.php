<?php

namespace Phpf\Cache {

	class Functional {
		// dummy class
	}
}

namespace {

	function cache_get($id, $group = \Cache::DEFAULT_GROUP) {
		return \Cache::instance()->get($id, $group);
	}

	function cache_set($id, $value, $group = \Cache::DEFAULT_GROUP, $ttl = \Cache::DEFAULT_TTL) {
		return \Cache::instance()->set($id, $value, $group, $ttl);
	}

	function cache_isset($id, $group = \Cache::DEFAULT_GROUP) {
		return \Cache::instance()->exists($id, $group);
	}

	function cache_unset($id, $group = \Cache::DEFAULT_GROUP) {
		return \Cache::instance()->delete($id, $group);
	}

	function cache_delete($id, $group = \Cache::DEFAULT_GROUP) {
		return \Cache::instance()->delete($id, $group);
	}

	function cache_incr($id, $value = 1, $group = \Cache::DEFAULT_GROUP, $ttl = \Cache::DEFAULT_TTL) {
		return \Cache::instance()->incr($id, $group);
	}

	function cache_decr($id, $value = 1, $group = \Cache::DEFAULT_GROUP, $ttl = \Cache::DEFAULT_TTL) {
		return \Cache::instance()->decr($id, $group);
	}

	function cache_flush_group($group) {
		return \Cache::instance()->flushGroup($group);
	}

	function cache_flush($group = null) {
		if (! empty($group))
			return \Cache::instance()->flushGroup($group);
		return \Cache::instance()->flush();
	}

}
