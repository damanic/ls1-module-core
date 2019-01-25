<?

	/**
	 * Memcached-based caching class
	 */
	class Core_MemCache extends Core_CacheBase
	{
		protected $ttl = 0;
		protected $online_servers = 0;
		protected $memcache = null;
		
		/**
		 * Creates the caching class instance.
		 * @param mixed $Params Specifies the class configuration options
		 */
		public function __construct($params = array())
		{
			if (!class_exists('Memcache'))
				throw new Phpr_SystemException('PHP Memcache extension is not installed.');
			
			$this->ttl = isset($params['TTL']) ? $params['TTL'] : 0;
			
			$servers = isset($params['SERVERS']) ? $params['SERVERS'] : null;
			if (!$servers)
				throw new Phpr_SystemException('Memcached servers are not specified in the config.php file');
				
			$this->memcache = new Memcache();
			foreach ($servers as $server)
			{
				$pos = strpos($server, ':');
				if ($pos === false)
					throw new Phpr_SystemException('Invalid memcached server specifier. Please use the following format: 192.168.0.1:11211');
					
				$ip = substr($server, 0, $pos);
				$port = substr($server, $pos+1);

				$this->memcache->addServer($ip, $port);
			}
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

			try
			{
				$key = Phpr::$request->getRootUrl().'|'.$key;
				$result = @$this->memcache->replace($key, $value, false, $ttl); 
				if( $result == false ) 
				    $result = @$this->memcache->set($key, $value, false, $ttl); 
				
				return $result;
			} catch (exception $ex) 
			{
				return false;
			}
		}

		/**
		 * Returns value from the cache
		 * @param mixed $key The key or array of keys to fetch.
		 */
		protected function get_value($key)
		{
			try
			{
				$key = Phpr::$request->getRootUrl().'|'.$key;
				$result = @$this->memcache->get($key);
			} catch (exception $ex)
			{
				return false;
			}
			
			return $result;
		}

		/**
		 * Deletes value from the cache
		 * @param mixed $key The key or array of keys to delete.
		 */
		protected function delete_value($key){
			try
			{
				$key = Phpr::$request->getRootUrl().'|'.$key;
				$result = @$this->memcache->delete($key);
			} catch (exception $ex)
			{
				return false;
			}

			return $result;
		}
	}

?>