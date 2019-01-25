<?

	/**
	 * File-based caching class
	 */
	class Core_FileCache extends Core_CacheBase
	{
		protected $dir_path = null;
		protected $ttl = 0;
		
		/**
		 * Creates the caching class instance.
		 * @param mixed $Params Specifies the class configuration options
		 */
		public function __construct($params = array())
		{
			$dir_path = isset($params['CACHE_DIR']) ? $params['CACHE_DIR'] : null;
			if (!$dir_path)
				throw new Phpr_SystemException('Caching directory for the file-based caching is not specified in the config.php file');
				
			if (!file_exists($dir_path) || !is_dir($dir_path))
				throw new Phpr_SystemException('Caching directory does not exist: '.$dir_path);

			if (!is_writable($dir_path))
				throw new Phpr_SystemException('Caching directory is not writable: '.$dir_path);
			
			$this->dir_path = $dir_path;
				
			$this->ttl = isset($params['TTL']) ? $params['TTL'] : 0;
		}
		
		/**
		 * Adds or updates value to the cache
		 * @param string $key The key that will be associated with the item.
		 * @param mixed $value The variable to store.
		 * @param int $ttl Time To Live; The file-based caching class does not support the time to live parameter in the 
		 * add() method. Please specify the global TTL parameter in the caching class configuration in the config.php file.
		 * @return bool Returns TRUE on success or FALSE on failure.
		 */
		protected function set_value($key, $value, $ttl = null) {
			if($ttl === null)
				$ttl = $this->ttl;
			
			$value = serialize(array('ls-data' => $value, 'ls-cache' => time(), 'ls-ttl' => (int)$ttl));
			
			if (!is_string($value) && !is_int($value))
				$value = serialize($value);
				
			$dest_path = $this->get_file_path($key);
			
			$fp = @fopen($dest_path, 'w');
			if (!$fp)
				throw new Phpr_SystemException('Error writing to the cache file');
			
			try
			{
				if (flock($fp, LOCK_EX | LOCK_NB))
				{
					@fwrite($fp, $value);
				    @flock($fp, LOCK_UN);
				}
				@fclose($fp);
				@chmod($dest_path, Phpr_Files::getFilePermissions());
				return true;
			}
			catch (exception $ex)
			{
				@close($fp);
				return false;
			}
		}

		/**
		 * Returns value from the cache
		 * @param mixed $key The key or array of keys to fetch.
		 */
		protected function get_value($key)
		{
			if (!is_array($key))
				return $this->get_cache_value($key);
				
			$result = array();
			foreach ($key as $key_value)
				$result[$key_value] = $this->get_cache_value($key_value);
				
			return $result;
		}

		/**
		 * Deletes value from the cache
		 * @param mixed $key The key or array of keys to delete.
		 */
		protected function delete_value($key){
			$dest_path = $this->get_file_path($key);
			if (!file_exists($dest_path)){
				return false;
			}
			@unlink($dest_path);
			return true;
		}
		
		protected function fix_key($key)
		{
			return str_replace('\\', '-', str_replace('/', '-', $key));
		}

		protected function get_file_path($key){
			$key = $this->fix_key($key);
			$dest_path = $this->dir_path.'/'.$key;
			return $dest_path;
		}
		
		protected function get_cache_value($key)
		{
			$dest_path = $this->get_file_path($key);
			if (!file_exists($dest_path))
				return false;
				
			$contents = file_get_contents($dest_path);
			
			$unserialized = null;
			if ( !$this->is_serialized($contents, $unserialized) )
				return $contents;

			if( is_array($unserialized) && isset($unserialized['ls-data']) )
			{
				if ($unserialized['ls-ttl'] && ((time() - $unserialized['ls-cache']) > $unserialized['ls-ttl']))
				{
					@unlink($dest_path);
					return false;
				}
				
				return $unserialized['ls-data'];
			}
			else
			{
				if ($this->ttl && ((time() - filemtime($dest_path)) > $this->ttl))
				{
					@unlink($dest_path);
					return false;
				}
				
				return $unserialized;
			}

			return $contents;
    }
		
		/**
		 * Detects whether a given string is a serialized value
		 * @author		Chris Smith <code+php@chris.cs278.org>
		 * @copyright	Copyright (c) 2009 Chris Smith (http://www.cs278.org/)
		 * @license		http://sam.zoy.org/wtfpl/ WTFPL
		 */
		protected function is_serialized(&$value, &$result)
		{
			// Bit of a give away this one
			if (!is_string($value))
			{
				return false;
			}

			// Serialized false, return true. unserialize() returns false on an
			// invalid string or it could return false if the string is serialized
			// false, eliminate that possibility.
			if ($value === 'b:0;')
			{
				$result = false;
				return true;
			}

			$length	= strlen($value);
			$end	= '';

			if($length < 4)
				return false;

			switch ($value[0])
			{
				case 's':
					if ($value[$length - 2] !== '"')
					{
						return false;
					}
				case 'b':
				case 'i':
				case 'd':
					// This looks odd but it is quicker than isset()ing
					$end .= ';';
				case 'a':
				case 'O':
					$end .= '}';

					if ($value[1] !== ':')
					{
						return false;
					}

					switch ($value[2])
					{
						case 0:
						case 1:
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
						case 7:
						case 8:
						case 9:
						break;

						default:
							return false;
					}
				case 'N':
					$end .= ';';

					if ($value[$length - 1] !== $end[0])
					{
						return false;
					}
				break;

				default:
					return false;
			}

			if (($result = @unserialize($value)) === false)
			{
				$result = null;
				return false;
			}
			
			return true;
		}
	}

?>