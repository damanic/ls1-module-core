<?php

	/**
	 * Incapsulates information about the HTTP request.
	 * An instance of this class is always available through the <em>$Phpr</em> class and you never need to create it manually:
	 * <pre>$ip = Phpr::$request->getUserIp();</pre>
	 * Use this class for reading GET or POST values from the request, loading cookie information, obtaining the visitor's IP address, etc.
	 * @documentable
	 * @see Phpr_Response
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 */
	class Phpr_Request
	{
		private $_ip = null;
		private $_language = null;
		private $_cachedEvendParams = null;
		private $_cachedUri = null;
		private $_subdirectory = null;
		private $_cachedRootUrl = null;

		protected $_remoteEventIndicator = 'HTTP_PHPR_REMOTE_EVENT';
		protected $_postbackIndicator = 'HTTP_PHPR_POSTBACK';

		public $get_fields = null;

		/**
		 * Creates a new Phpr_Request instance.
		 * Do not create the Request objects directly. Use the Phpr::$request object instead.
		 * @see Phpr
		 */
		public function __construct()
		{
			$this->preprocessGlobals();
		}

		/**
		 * Returns a named POST parameter value.
		 * If a parameter with the specified name does not exist in POST, returns <em>NULL</em> or a value 
		 * specified in the $default parameter.
		 * @documentable
		 * @see post() post() function
		 * @param string $name Specifies the parameter name.
		 * @param mixed $default Specifies a default value.
		 * @return mixed Returns the POST parameter value, NULL or default value.
		 */
		public function post( $Name, $Default = null )
		{
			if (array_key_exists($Name.'_x', $_POST) && array_key_exists($Name.'_y', $_POST))
				return true;

			if ( !array_key_exists($Name, $_POST) )
				return $Default;

			return $_POST[$Name];
		}

		/**
		 * Finds an array in the <em>POST</em> data then finds and returns an element inside this array.
		 * If the array or the element do not exist, returns null or a value specified in the $default parameter.
		 * 
		 * This method is useful for extracting form field values if you use array notation for the form input element names.
		 * For example, if you have a form with the following fields
		 * <pre>
		 * <input type="text" name="customer_form[first_name]">
		 * <input type="text" name="customer_form[last_name]">
		 * </pre>
		 * you can extract the first name field value with the following code:
		 * <pre>$first_name = Phpr::$request->post_array_item('customer_form', 'first_name')</pre>
		 * @documentable
		 * @see post_array_item() post_array_item() function
		 * @param string $array_name specifies the array element name in the POST data.
		 * @param string $name specifies the array element key in the first array.
		 * @param mixed $default specifies a default value.
		 * @return mixed returns the found array element value or the default value.
		 */
		public function post_array_item( $ArrayName, $Name, $Default = null )
		{
			if ( !array_key_exists($ArrayName, $_POST) )
				return $Default;

			if ( !array_key_exists($Name, $_POST[$ArrayName]) )
				return $Default;

			return $_POST[$ArrayName][$Name];
		}
		
		/**
		 * Returns a cookie value by the cookie name.
		 * If a cookie with the specified name does not exist, returns NULL.
		 * @documentable
		 * @param string $name Specifies the cookie name.
		 * @return mixed Returns either cookie value or NULL.
		 */
		public function cookie( $Name )
		{
			if ( !isset($_COOKIE[$Name]) )
				return null;

			return $_COOKIE[$Name];
		}

		/**
		 * Returns a name of the User Agent.
		 * If user agent data is not available, returns NULL.
		 * @documentable
		 * @return mixed Returns the user agent name or NULL.
		 */
		public function getUserAgent()
		{
			return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}

		/**
		 * Determines whether the remote event handling requested.
		 * @return boolean.
		 */
		public function isRemoteEvent()
		{
			return isset($_SERVER[$this->_remoteEventIndicator]);
		}
		
		/**
		 * Returns SSL Session Id value.
		 * @return string.
		 */
		public function getSslSessionId()
		{
			if (isset($_SERVER["SSL_SESSION_ID"]))
				return $_SERVER["SSL_SESSION_ID"];
				
			return null;
		}

		/**
		 * Determines whether the page is loaded in response to a client postback.
		 * @return boolean.
		 */
		public function isPostBack()
		{
			return isset($_SERVER[$this->_postbackIndicator]);
		}

		/**
		 * Returns the visitor's IP address.
		 * @documentable
		 * @return string Returns the IP address.
		 */
		public function getUserIp()
		{
			if ( $this->_ip !== null )
				return $this->_ip;

			$ipKeys = Phpr::$config->get('REMOTE_IP_HEADERS', array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'));
			foreach ( $ipKeys as $ipKey )
			{
				if ( isset($_SERVER[$ipKey]) && strlen($_SERVER[$ipKey]) )
				{
					$this->_ip = $_SERVER[$ipKey];
					break;
				}
			}

			if ( strlen( strstr($this->_ip, ',') ) )
			{
				$ips = explode(',', $this->_ip);
				$this->_ip = trim(reset($ips));
			}
				
			if ($this->_ip == '::1')
				$this->_ip = '127.0.0.1';

			return $this->_ip;
		}

		/**
		 * Returns the visitor language preferences.
		 * @return string
		 */
		public function gerUserLanguage()
		{
			if ( $this->_language !== null )
				return $this->_language;

			if ( !array_key_exists('HTTP_ACCEPT_language', $_SERVER) )
				return null;

			$languages = explode( ",", $_SERVER['HTTP_ACCEPT_language'] );
			$language = $languages[0];

			if ( ($pos = strpos($language, ";")) !== false )
				$language = substr( $language, 0, $pos );

			return $this->_language = str_replace( "-", "_", $language );
		}
		
		/**
		 * Returns a subdirectory path, starting from the server 
		 * root directory to LemonStand directory root.
		 * Example. LemonStand installed to the subdirectory /lemonstand of a domain
		 * Then the method will return the '/subdirectory/' string
		 */
		public function getSubdirectory()
		{
			if ($this->_subdirectory !== null)
				return $this->_subdirectory;
				
			$request_param_name = Phpr::$config->get('REQUEST_PARAM_NAME', 'q');
			
			$uri = $this->getRequestUri();
			$path = $this->getField($request_param_name);

			$uri = urldecode($uri);
			$uri = preg_replace('|/\?(.*)$|', '/', $uri);

			$pos = strpos($uri, '?');
			if ($pos !== false)
				$uri = substr($uri, 0, $pos);

			$pos = strpos($uri, '/&');
			if ($pos !== false)
				$uri = substr($uri, 0, $pos+1);
			
			$path = mb_strtolower($path);
			$uri = mb_strtolower($uri);

			$pos = mb_strrpos($uri, $path);
			$subdir = '/';
			if ($pos !== false && $pos == mb_strlen($uri)-mb_strlen($path))
				$subdir = mb_substr($uri, 0, $pos).'/';
				
			if (!strlen($subdir))
				$subdir = '/';
				
			return $this->_subdirectory = $subdir;
		}

		/**
		 * Returns the URL of the current request
		 */
		public function getRequestUri()
		{
			$provider = Phpr::$config->get( "URI_PROVIDER", null );

			if ( $provider !== null )
				return getenv( $provider );
			else
			{
				// Pick the provider from the server variables
				//
				$providers = array( 'REQUEST_URI', 'PATH_INFO', 'ORIG_PATH_INFO' );
				foreach ( $providers as $provider )
				{
					$val = getenv( $provider );
					if ( $val != '' )
						return $val;
				}
			}
			
			return null;
		}

		/**
		 * Returns the URI of the current request relative to the LemonStand root directory.
		 * @param bool $Routing Determines whether the Uri is requested for the routing process
		 * @return string
		 */
		public function getCurrentUri( $Routing = false )
		{
			global $bootstrapPath;

			if ( !$Routing && $this->_cachedUri !== null )
				return $this->_cachedUri;

			$request_param_name = Phpr::$config->get('REQUEST_PARAM_NAME', 'q');
			$bootstrapPathBase = pathinfo($bootstrapPath, PATHINFO_BASENAME);
			$URI = $this->getField($request_param_name);

			// Postprocess the URI
			//
			if ( strlen($URI) )
			{
				if ( ( $pos = strpos($URI, '?') ) !== false )
					$URI = substr( $URI, 0, $pos );

				if ( $URI{0} == '/' ) $URI = substr( $URI, 1 );

				$len = strlen($bootstrapPathBase);
				if ( substr($URI, 0, $len) == $bootstrapPathBase )
				{
					$URI = substr($URI, $len);
					if ( $URI{0} == '/' ) $URI = substr( $URI, 1 );
				}

				$len = strlen($URI);
				if ($len > 0 && $URI{$len-1} == '/' ) $URI = substr( $URI, 0, $len-1 );
			}

			$URI = "/".$URI;

			if ( $Routing )
			{
				// $DocRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null;
				// if ( strlen($DocRoot) )
				// {
				// 	if ( strpos(PATH_APP, $DocRoot) == 0 && strcmp(PATH_APP, $DocRoot) != 0 )
				// 	{
				// 		$dirName = substr( PATH_APP, strlen($DocRoot) );
				// 		if ( strlen($dirName) )
				// 		{
				// 			$URI = str_replace($dirName.'/', '', $URI);
				// 		}
				// 	}
				// }
				// 
				// $URI = str_replace('test/', '', $URI);
			} else
				$this->_cachedUri = $URI;

			return $URI;
		}

		/**
		 * Cleans the _POST and _COOKIE data and unsets the _GET data.
		 * Replaces the new line charaters with \n.
		 */
		private function preprocessGlobals()
		{
			// Unset the global variables
			//
			$this->get_fields = $_GET;
			
			$this->unsetGlobals( $_GET );
			$this->unsetGlobals( $_POST );
			$this->unsetGlobals( $_COOKIE );
			
			// Remove magic quotes
			//
			if (ini_get('magic_quotes_gpc') || Phpr::$config->get('REMOVE_GPC_SLASHES'))
			{
				array_walk_recursive($_GET, array('Phpr_Request', 'array_strip_slashes')); 
			    array_walk_recursive($_POST, array('Phpr_Request', 'array_strip_slashes')); 
			    array_walk_recursive($_COOKIE, array('Phpr_Request', 'array_strip_slashes'));
			}

			// Clear the _GET array
			//
			$_GET = array();

			// Clean the POST and COOKIE data
			//
			$this->cleanupArray( $_POST );
			$this->cleanupArray( $_COOKIE );
		}
		
		public function get_value_array($name, $default = array())
		{
			if (array_key_exists($name, $this->get_fields))
				return $this->get_fields[$name];

			if (!isset($_SERVER['QUERY_STRING']))
				return $default;

			$vars = explode('&', $_SERVER['QUERY_STRING']);

			$result = array();
			foreach ($vars as $var_data)
			{
				$var_data = urldecode($var_data);

				$var_parts = explode('=', $var_data);
				if (count($var_parts) == 2)
				{
					if ($var_parts[0] == $name.'[]' || $var_parts[0] == $name.'%5B%5D')
						$result[] = $var_parts[1];
				}
			}
			
			if (!count($result))
				return $default;
				
			return $result;
		}

		public function get_query_string($include_request_name=false){
			$params = $this->get_fields;
			$rpn = Phpr::$config->get('REQUEST_PARAM_NAME', 'q');
			if(is_array($params)){
				if(!$include_request_name && isset($params[$rpn])){
					unset($params[$rpn]);
				}
			}
			return (is_array($params) && count($params)) ? http_build_query($params, '', '&') : null;
		}
		
		public static function array_strip_slashes(&$value)
		{
			$value = stripslashes($value); 
		}
		
		/**
		 * Returns a named GET parameter value.
		 * @documentable
		 * If a parameter with the specified name does not exist in GET, returns <em>null</em> or a value specified in the $default parameter.
		 * @documentable
		 * @see Phpr_Request::post() post()
		 * @param string $name Specifies the parameter name.
		 * @param mixed $default Specifies a default value.
		 * @return mixed Returns the GET parameter value, NULL or default value.
		 */
		public function getField($name, $default = false)
		{
			return array_key_exists($name, $this->get_fields) ? $this->get_fields[$name] : $default;
		}

		/**
		 * Unsets the global variables created with from the POST, GET or COOKIE data.
		 * @param array &$Array The array containing a list of variables to unset.
		 */
		private function unsetGlobals( &$Array )
		{
			if ( !is_array($Array) )
				unset( $$Array );
			else
				foreach ( $Array as $VarName => $VarValue )
					unset($$VarName);
		}

		/**
		 * Check the input array key for invalid characters and adds slashes.
		 * @param string $Key Specifies the key to process.
		 * @return string
		 */
		private function cleanupArrayKey( $Key )
		{
			if ( !preg_match("/^[0-9a-z:_\/-\{\}|]+$/i", $Key) )
			{
				return null;
//				throw new Phpr_SystemException( "Invalid characters in the input data key: $Key" );
			}

			return get_magic_quotes_gpc() ? $Key : addslashes($Key);
		}

		/**
		 * Fixes the new line characters in the specified value.
		 * @param mixed $Value Specifies a value to process.
		 * return mixed
		 */
		private function cleanupArrayValue( $Value )
		{
			if ( !is_array($Value) )
				return preg_replace("/\015\012|\015|\012/", "\n", $Value);

			$Result = array();
			foreach ( $Value as $VarName => $VarValue )
				$Result[$VarName] = $this->cleanupArrayValue($VarValue);

			return $Result;
		}

		/**
		 * Cleans the unput array keys and values.
		 * @param array &$Array Specifies an array to clean.
		 */
		private function cleanupArray( &$Array )
		{
			if ( !is_array($Array) )
				return;

			foreach( $Array as $VarName => &$VarValue)
			{
				if (is_array($VarValue))
					$this->cleanupArray( $VarValue );
				else
					$Array[$this->cleanupArrayKey($VarName)] = $this->cleanupArrayValue($VarValue);
			}
		}

		/**
		 * @ignore
		 * Returns a list of the event parameters, or a specified parameter value.
		 * This method is used by the PHP Road internally.
		 *
		 * @param string $Name Optional name of parameter to return.
		 * @return mixed
		 */
		public function getEventParams( $Name = null )
		{
			if ( $this->_cachedEvendParams == null )
			{
				$this->_cachedEvendParams = array();

				if ( isset($_POST['phpr_handler_params']) )
				{
					$pairs = explode( '&', $_POST['phpr_handler_params'] );
					foreach ($pairs as $pair)
					{
						$parts = explode( "=", urldecode($pair) );
						$this->_cachedEvendParams[$parts[0]] = $parts[1];
					}
				}
			}

			if ( $Name === null )
				return $this->_cachedEvendParams;

			if ( isset($this->_cachedEvendParams[$Name]) )
				return $this->_cachedEvendParams[$Name];

			return null;
		}

		public function getReferer($Detault = null)
		{
			if ( isset($_SERVER['HTTP_REFERER']) )
				return $_SERVER['HTTP_REFERER'];

			return $Detault;
		}

		/**
		 * Returns the current request method name - <em>POST</em>, <em>GET</em>, <em>HEAD</em> or <em>PUT</em>.
		 * @documentable
		 * @return string Returns the request method name.
		 */ 
		public function getRequestMethod()
		{
			if (isset($_SERVER['REQUEST_METHOD']))
				return strtoupper($_SERVER['REQUEST_METHOD']);
				
			return null;
		}

		public function getCurrentUrl()
		{
			$protocol = $this->protocol();
			$port = ($_SERVER["SERVER_PORT"] == "80") ? ""
				: (":".$_SERVER["SERVER_PORT"]);
				
			return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
		}
		
		/**
		 * Returns HTTP protocol name - <em>http</em> or <em>https</em>.
		 * @documentable
		 * @return string Returns HTTP protocol name.
		 */
		public function protocol()
		{
			if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
				$s = 's';
			else
				$s = (empty($_SERVER["HTTPS"]) || ($_SERVER["HTTPS"] === 'off')) ? '' : 's';

			return $this->strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
		}
		
		/**
		 * Returns HTTP port number.
		 * If <em>STANDARD_HTTP_PORTS</em> parameter is set to TRUE in {@link http://lemonstand.com/docs/lemonstand_configuration_options/ config.php file}, 
		 * the method returns NULL.
		 * @documentable
		 * @return integer Returns HTTP port number.
		 */
		public function port()
		{
		    if (Phpr::$config->get('STANDARD_HTTP_PORTS'))
		        return null;
		    
			if (array_key_exists('HTTP_HOST', $_SERVER))
			{
				$matches = array();
				if (preg_match('/:([0-9]+)/', $_SERVER['HTTP_HOST'], $matches))
					return $matches[1];
			}

			return isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : null;
		}

		public function getRootUrl($protocol = null)
		{
			if (!isset($_SERVER['SERVER_NAME']))
				return null;
				
			$protocol_specified = strlen($protocol);
			if (!$protocol_specified && $this->_cachedRootUrl !== null)
				return $this->_cachedRootUrl;

			if ($protocol === null)
				$protocol = $this->protocol();

			$port = $this->port();

			$current_protocol = $this->protocol();
			if ($protocol_specified && strtolower($protocol) != $current_protocol)
				$port = '';

			$https = strtolower($protocol) == 'https';

			if (!$https && $port == 80)
				$port = '';

			if ($https && $port == 443)
				$port = '';

			$port = !strlen($port) ? "" : ":".$port;

			$result = $protocol."://".$_SERVER['SERVER_NAME'].$port;
			
			if (!$protocol_specified)
 				$this->_cachedRootUrl = $result;
			
			return $result;
		}
		
		private function strleft($s1, $s2) 
		{
			return substr($s1, 0, strpos($s1, $s2));
		}
	}

?>