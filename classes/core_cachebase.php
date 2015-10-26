<?
	/**
	 * Incapsulates LemonStand caching API features.
	 * This is an interface class for the caching API. The caching API supports 3 caching providers - 
	 * <em>file-based</em>, <em>memcached</em> and <em>APC</em>. Please read  
	 * {@link http://lemonstand.com/docs/caching_api Caching API documentation article} to learn more
	 * about LemonStand caching features and configuration. The following example demonstrates a typical
	 * usage of the class:
	 * <pre>
	 * $cache = Core_CacheBase::create();
	 * 
	 * // Try to load the item value from the cache
	 * 
	 * $recache = false;
	 * $key = Core_CacheBase::create_key('some_item', $recache, array('url'), array('cms','catalog'));
	 * $value = $cache->get($key);
	 * 
	 * // If the key has expired, or if there is no item with this key stored (or if it has expired),
	 * // generate new item value and cache it
	 * 
	 * if ($recache || $value === false)
	 * {
	 *   $value = 'value';
	 *   $cache->set($key, $value);
	 * }
	 * </pre>
	 * @documentable
	 * @see http://lemonstand.com/docs/caching_api Caching API
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 */
	abstract class Core_CacheBase
	{
		protected static $instance = null;
		public static $disabled = false;
		
		/**
		 * Returns an instance of the cache class.
		 * Depending on the configured caching provider, the method returns an object of the 
		 * Core_FileCache, Core_MemCache or Core_ApcCache class. All these classes has same 
		 * methods.
		 * @documentable
		 * @return mixed Returns a cache class instance.
		 */
		public static function create()
		{
			if (self::$instance !== null)
				return self::$instance;

			$caching_config = Phpr::$config->get('CACHING');
			$params = array();
			$class_name = 'Core_DummyCache';

			if ($caching_config)
			{
				$class_name = isset($caching_config['CLASS_NAME']) ? $caching_config['CLASS_NAME'] : null;
				if (!$class_name)
					throw new Phpr_SystemException('Caching class name is not specified in the caching configuration.');

				$params = isset($caching_config['PARAMS']) ? $caching_config['PARAMS'] : array();
				self::$disabled = isset($caching_config['DISABLED']) ? $caching_config['DISABLED'] : false;
			}

			return self::$instance = new $class_name($params);
		}
		
		/**
		 * Allows to create item keys, which depend on different current conditions. 
		 * Use this function for creating keys, which expire depending on the catalog content state,
		 * current customer group and some other conditions.
		 * 
		 * The <em>$vary_by</em> parameter allows you to specify which conditions the key value should
		 * depend on. The condition list could include system conditions, or custom conditions. The
		 * known system vary-by parameters are:
		 * <ul>
		 *   <li><em>url</em> - generates different keys for different page URLs.</li>
		 *   <li><em>customer</em> - generates different keys for different customers.</li>
		 *   <li><em>customer_group</em> - generates different keys for different customers groups.</li>
		 *   <li><em>customer_presence</em> - generates different keys depending on whether a customer is logged in or not.</li>
		 * </ul>
		*  The system vary-by parameters should be specified as strings. You can also specify any other parameters 
		 * you want the key to depend on. These parameters should be specified as key-value pairs, see the order example below.
		 * The <em>$versions</em> parameter allows you force the item recaching when the store content updates. 
		 * There are 3 content types which you can specify in the parameter value. If any of the specified content types
		 * updates, the method assigns the TRUE value to the <em>$recache</em> parameter. You can use the following 
		 * version content types:
		 * <ul>
		 *   <li><em>cms</em> - forces recaching if any CMS object (page, partial or template) has been added, updated or deleted.</li>
		 *   <li><em>catalog</em> - forces recaching if any catalog object (product, category, etc.) has been added, updated or deleted.</li>
		 *   <li><em>blog</em> - forces recaching if any blog object (post, category or comment) has been added, updated or deleted.</li>
		 * </ul>
		 * Examples of the create_key() method usage:
		 * <pre>
		 * // Create key, which does not depend on any parameter.
		 * // There is no real sense in using the create_key() function in this case.
		 * 
		 * $recache = false;
		 * $key = Core_CacheBase::create_key('some_item', $recache);
		 * 
		 * // Create key, which depends on the page URL and the CMS content version.
		 * // Notice that if you use only a single value in the $vary_by and $versions parameter,
		 * // you can specify values as string.
		 * 
		 * $recache = false;
		 * $key = Core_CacheBase::create_key('some_item', $recache, 'url', 'cms');
		 * 
		 * // Create key, which depends on the page URL, and customer group. Thus, the key will be
		 * // different for different pages and different customer groups. Also, the key will
		 * // expire on the CMS or catalog updates.
		 * 
		 * $recache = false;
		 * $key = Core_CacheBase::create_key('some_item', $recache, array('url', 'customer_group'), array('cms','catalog'));
		 * 
		 * // Create key, which depends on the sort order variable. We will be getting different
		 * // keys for different sort order values. 
		 * 
		 * $recache = false;
		 * $sort_order = 'name asc';
		 * $key = Core_CacheBase::create_key('some_item', $recache, array('sort_order'=>$sort_order));
		 * 
		 * $sort_order = 'name desc';
		 * $key = Core_CacheBase::create_key('some_item', $recache, array('sort_order'=>$sort_order));
		 * </pre>
		 * @documentable
		 * @param string $prefix A prefix string.
		 * If you omit the the <em>$vary_by</em> and <em>$versions</em> parameters, the method always returns the same 
		 * key value, based on the prefix value. 
		 * @param boolean $recache Indicates whether the item should be recached.
		 * Recaching should be required if you specify any content type in the <em>$versions</em> parameter.
		 * @param array $vary_by Determines which conditions the key value should depend on.
		 * @param array $version Allows to force the recaching when the store content updates.
		 * @return string Returns the new item key.
		 */
		public static function create_key($prefix, &$recache, $vary_by = array(), $version = array())
		{
			$result = $prefix;
			$recache = false;
			
			$vary_by = Phpr_Util::splat($vary_by);
			$version = Phpr_Util::splat($version);
			
			$obj = self::create();

			$controller = Cms_Controller::get_instance();
			foreach ($vary_by as $key=>$param_name)
			{
				if (is_int($key))
				{
					switch ($param_name)
					{
						case 'url' : 
							$url = Phpr::$request->getCurrentUrl();
							$caching_params = Phpr::$config->get('CACHING', array());
							$reset_cache_key = array_key_exists('RESET_PAGE_CACHE_KEY', $caching_params) ? $caching_params['RESET_PAGE_CACHE_KEY'] : null;
							
							if ($reset_cache_key)
							{
								$url = str_replace('?'.$reset_cache_key.'=1', '', $url);
								$url = str_replace('&'.$reset_cache_key.'=1', '', $url);
							}
							
							$result .= $url;
						break;
						case 'customer' :
							if ($controller)
							{
								$customer = $controller->get_customer();
								if ($customer)
									$result .= '-' .$customer->id;
							}
						break;
						case 'customer_group' :
							if ($controller)
								$result .= '-' .$controller->get_customer_group_id();
						break;
						case 'customer_presence' :
							if ($controller)
							{
								$customer = $controller->get_customer();
								if ($customer)
									$result .= '-customer';
							}
						break;
						default:
							throw new Phpr_SystemException('Unknown cache vary-by parameter: '.$param_name);
						break;
					}
				} else {
					$result .= '-'.$key.':';
					if (!is_string($param_name) && !is_int($param_name))
						$param_name = serialize($param_name);

					$result .= $param_name;
				}
			}
			
			$result = $prefix.'_'.sha1($result);
			
			foreach ($version as $param_name)
			{
			    $result_key = sha1($result);
				$key_versions = $obj->get('sys_key_vrs_'.$result_key);
				
				switch ($param_name)
				{
					case 'catalog' :
						$prev_version_value = ($key_versions && array_key_exists('catalog', $key_versions)) ? $key_versions['catalog'] : 0;
						$new_version_value = Shop_Module::get_catalog_version();
						$recache = $recache || ($prev_version_value != $new_version_value);
						if ($recache)
							$key_versions['catalog'] = $new_version_value;
					break;
					case 'cms' :
						$prev_version_value = ($key_versions && array_key_exists('cms', $key_versions)) ? $key_versions['cms'] : 0;
						$new_version_value = Cms_Module::get_cms_content_version();
						$recache = $recache || ($prev_version_value != $new_version_value);
						if ($recache)
							$key_versions['cms'] = $new_version_value;
					break;
					case 'blog' :
						$prev_version_value = ($key_versions && array_key_exists('blog', $key_versions)) ? $key_versions['blog'] : 0;
						$new_version_value = Blog_Module::get_blog_content_version();
						$recache = $recache || ($prev_version_value != $new_version_value);
						if ($recache)
							$key_versions['blog'] = $new_version_value;
					break;
					default:
						throw new Phpr_SystemException('Unknown cache version parameter: '.$param_name);
					break;
				}
				
				if ($recache)
					$obj->set('sys_key_vrs_'.$result_key, $key_versions);
			}
			
			return $result;
		}
		
		/**
		 * Creates the caching class instance.
		 * @param mixed $Params Specifies the class configuration options
		 */
		public function __construct($params = array())
		{
		}
		
		/**
		 * Adds or updates value in the cache.
		 * @documentable
		 * @param string $key Specifies a key that will be associated with the item.
		 * @param mixed $value Specifies the value to store.
		 * @param integer $ttl Specifies the number of seconds the value should stay in the cache.
		 * When an item expires its gets expunged from the cache (on the next request). If no <em>ttl</em> is supplied
		 * (or if the ttl is 0), the value will persist until it is removed from the cache manually.
		 * @return bool Returns TRUE on success or FALSE on failure.
		 */
		public function set($key, $value, $ttl = null)
		{
			if (self::$disabled)
				return false;

			return $this->set_value($key, $value, $ttl);
		}

		/**
		 * Returns value from the cache.
		 * @documentable
		 * @param mixed $key Specifies the item key to fetch.
		 * @return mixed Returns the item value or FALSE if the item was expired or not found.
		 */
		public function get($key)
		{
			if (self::$disabled)
				return false;

			return $this->get_value($key);
		}
		
		/**
		 * Adds or updates value to the cache
		 */
		abstract protected function set_value($key, $value, $ttl = null);

		/**
		 * Returns value from the cache
		 * @param mixed $key The key or array of keys to fetch.
		 */
		abstract protected function get_value($key);
	}
?>