<?

	/**
	 * APC caching class
	 */
	class Core_ApcCache extends Core_CacheBase
	{
		protected $ttl = 0;
		
		/**
		 * Creates the caching class instance.
		 * @param mixed $Params Specifies the class configuration options
		 */
		public function __construct($params = array())
		{
			if (!function_exists('apc_store'))
				throw new Phpr_SystemException('PHP APC extension is not installed.');

			$this->ttl = isset($params['TTL']) ? $params['TTL'] : 0;
		}
		
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
			if ($ttl === null)
				$ttl = $this->ttl;
			
			return apc_store($key, $value, $ttl);
		}

		/**
		 * Returns value from the cache
		 * @param mixed $key The key or array of keys to fetch.
		 */
		protected function get_value($key)
		{
			return apc_fetch($key);
		}
	}

?>