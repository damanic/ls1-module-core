<?

	/**
	 * Dummy cache class. This class does not cache anything
	 * and needed for the development and testing. LemonStand
	 * uses this class if no other caching class is specified
	 * in the caching configuration.
	 */
	class Core_DummyCache extends Core_CacheBase
	{
		/**
		 * Adds or updates value to the cache
		 * @param string $key The key that will be associated with the item.
		 * @param mixed $value The variable to store.
		 * @param int $ttl Time To Live; store var in the cache for ttl seconds. After the ttl has passed,
		 * the stored variable will be expunged from the cache (on the next request). If no ttl is supplied,
		 * the value specified in the TTL parameter of the cache configuration will be used. If there is no
		 * TTL value in the cache configuration, the 0 value will will used.
		 * @return bool Returns TRUE on success or FALSE on failure.
		 */
		protected function set_value($key, $value, $ttl = null)
		{
			return true;
		}

		/**
		 * Returns value from the cache
		 * @param mixed $key The key or array of keys to fetch.
		 */
		protected function get_value($key)
		{
			return false;
		}
	}
	
?>