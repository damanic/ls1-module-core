<?php

	/**
	 * PHP Road
	 *
	 * PHP application framework
	 *
	 * @package		PHPRoad
	 * @author		Aleksey Bobkov, Andy Chentsov
	 * @since		Version 1.0
	 * @filesource
	 */

	/**
	 * PHP Road security class.
	 *
	 * This class provides a basic security features based on cookies.
	 *
	 * The instance of this class is available in the Phpr global object: Phpr::$security.
	 *
	 * @see Phpr
	 *
	 * @package		PHPRoad
	 * @category	PHPRoad
	 * @author		Aleksey Bobkov
	 */
	class Phpr_Security
	{
		/**
		 * The name of the user class. 
		 * Change this name if you want to use a user class other than the Phpr_User.
		 * @var string
		 */
		public $userClassName = "Phpr_User";

		/**
		 * The authentication cookie name.
		 * You may specify a value for this parameter in the configuration file:
		 * $CONFIG['AUTH_COOKIE_NAME'] = 'PHPROAD';
		 * @var string
		 */
		public $cookieName = "PHPROAD";

		/**
		 * Specifies a number of days before the authentication coole expires.
		 * Default value is 2 days.
		 * You may specify a value for this parameter in the configuration file:
		 * $CONFIG['AUTH_COOKIE_LIFETIME'] = 5;
		 * @var int
		 */
		public $cookieLifetime = "2";

		/**
		 * The path on the server in which the authentication cookie will be available on.
		 * You may specify a value for this parameter in the configuration file:
		 * $CONFIG['AUTH_COOKIE_PATH'] = '/blog/';
		 * @var string
		 */
		public $cookiePath = "/";

		/**
		 * The domain that the authentication cookie is available. 
		 * You may specify a value for this parameter in the configuration file:
		 * $CONFIG['AUTH_COOKIE_DOMAIN'] = '.your-site.com';
		 * @var string
		 */
		public $cookieDomain = "";

		/**
		 * The login name cookie name (see the RememberLoginName property).
		 * You may specify a value for this parameter in the configuration file:
		 * $CONFIG['LOGIN_COOKIE_NAME'] = 'PHPROAD_LOGIN';
		 * @var string
		 */
		public $loginCookieName = "PHPROAD_LOGIN";

		/**
		 * Determines whether the user name must be saved in a cookie.
		 * Use this option if you want to implement a 
		 * "Remember my name in this computer" feature in a login form.
		 * Use the GetSavedLoginName() method to obtain a saved login name.
		 * See also the IsLoginNameSaved() method.
		 * @var boolean
		 */
		public $rememberLoginName = false;
		
		public $cookiesUpdated = false;
		
		public $noIpCheck = false;
		
		protected $cookieLifetimeVar = 'AUTH_COOKIE_LIFETIME';

		protected $user = null;
		protected $_ticket = null;

		/**
		 * Returns a currently signed in user. If there is no signed user returns null.
		 * @return Phpr_User The class of a returning object depends on configuration settings.
		 * You may extend the Phpr_User and specify it in the application init.php file:
		 * Phpr::$security->userClassName = "NewUserClass";
		 */
		public function getUser()
		{
			if ( $this->user !== null )
				return $this->user;

			/*
			 * Determine whether the authentication cookie is available
			 */
			
			if ($this->_ticket !== null)
				$Ticket = $this->_ticket;
			else
			{
				$CookieName = Phpr::$config->get('AUTH_COOKIE_NAME', $this->cookieName);
				$Ticket = Phpr::$request->cookie( $CookieName );
			}

			if ( $Ticket === null )
				return null;

			/*
			 * Validate the ticket
			 */
			$Ticket = $this->validateTicket( $Ticket );
			if ( $Ticket === null )
				return null;

			/*
			 * Return the ticket user
			 */
			$UserObj = new $this->userClassName();
			$UserId = trim(base64_decode($Ticket['user']));
			if ( !strlen($UserId) )
				return null;

			return $this->user = $UserObj->find( $UserId );
		}

		/**
		 * Validates user login name and password and logs user in.
		 *
		 * @param Phpr_Validation $Validation Optional validation object to report errors.
		 * @param string $Redirect Optional URL to redirect the user browser in case of successful login.
		 *
		 * @param string $Login Specifies the user login name.
		 * If you omit this parameter the 'Login' POST variable will be used.
		 *
		 * @param string $Password Specifies the user password
		 * If you omit this parameter the 'Password' POST variable will be used.
		 *
		 * @return boolean
		 */
		public function login( Phpr_Validation $Validation = null, $Redirect = null, $Login = null, $Password = null )
		{
			/*
			 * Load the login form data
			 */
			if ( $Login === null )
				$Login = Phpr::$request->post('login');

			if ( $Password === null )
				$Password = Phpr::$request->post('password');

			/*
			 * Validate the login name and password
			 */

			$UserObj = new $this->userClassName();
			
			if (method_exists($UserObj, 'init_columns_info'))
				$UserObj->init_columns_info('login');
				
			$User = $UserObj->findUser( $Login, $Password );

			$this->checkUser( $User );
			if ( $User == null )
			{
				if ( $Validation !== null )
				{
					$Validation->add('login');
					$Validation->setError( Phpr::$lang->mod('phpr', "invalidcredentials", 'security'), 'login', true );
				}

				return false;
			}

			/*
			 * Save the login name
			 */
			$this->updateLoginName( $this->rememberLoginName, $Login );

			/*
			 * Update the authentication cookie
			 */
			$this->updateCookie( $User->id );

			$this->user = $User;

			/*
			 * Prepare a clean user session
			 */
			
			$this->beforeLoginSessionDestroy( $User );
			
			$session_id = null;
			if ($this->keepSessionData())
			{
				$session_id = session_id();
				Phpr::$session->store();
			}
			
			Phpr::$session->destroy();
			
			$this->afterLogin($User);

			/*
			 * Redirect browser to a target page
			 */
			if ( $Redirect !== null )
			{
				if ($session_id)
				{
					$session_id_param = Phpr::$config->get('SESSION_PARAM_NAME', 'ls_session_id');
					$Redirect .= '?'.$session_id_param.'='.urlencode($session_id);
				}
				
				Phpr::$response->redirect($Redirect);
			}

			return true;
		}

		/**
		 * Logs user out.
		 * @param string $Redirect Optional URL to redirect the user browser.
		 */
		public function logout( $Redirect = null )
		{
			$CookieName = Phpr::$config->get('AUTH_COOKIE_NAME', $this->cookieName);
			$CookiePath = Phpr::$config->get('AUTH_COOKIE_PATH', $this->cookiePath);
			$CookieDomain = Phpr::$config->get('AUTH_COOKIE_DOMAIN', $this->cookieDomain);

			Phpr::$response->deleteCookie( $CookieName, $CookiePath, $CookieDomain );

			$this->user = null;

			Phpr::$session->destroy();

			if ( $Redirect !== null )
				Phpr::$response->redirect( $Redirect );
		}

		/**
		 * Determines whether a currently signed in user is allowed to have access to a specified resource.
		 * @param string $Module Specifies the name of a module that owns the resource ("blog").
		 *
		 * @param string $Resource Specifies the name of a recource ("post").
		 * You may omit this parameter to determine if user has accssess rights to any module resource.
		 *
		 * @param string $Object Specifies the resource object ("1").
		 * You may omit this parameter to determine if user has accssess rights to any object in context of specified module resource.
		 *
		 * @return mixed
		 */
		public function authorize( $Module, $Resource = null, $Object = null )
		{
			/*
			 * Validate the session host
			 */
			if (!$this->check_session_host())
				return false;
			
			/*
			 * Validate the user
			 */
			
			$User = $this->getUser();

			if ( $User === null )
				return false;

			$res = $User->authorize( $Module, $Resource, $Object );
			if ( $res )
			{
				$this->updateCookie( $User->id );
				return true;
			}

			return false;
		}
		
		/**
		 * Checks whether the session has been started on this host
		 */
		public function check_session_host()
		{
			$session_host = Phpr::$session->get('phpr_session_host');
			if (!strlen($session_host))
			{
				Phpr::$session->set('phpr_session_host', $_SERVER['SERVER_NAME']);
				return true;
			}

			if ($session_host != $_SERVER['SERVER_NAME'])
				return false;
				
			return true;
		}

		/**
		 * Returns a user login name saved during last login.
		 * @param boolean $Html Indicates whether the result value must be prepared for HTML output.
		 * @return string
		 */
		public function getSavedLoginName( $Html = true )
		{
			$CookieName = Phpr::$config->get('LOGIN_COOKIE_NAME', $this->loginCookieName);
			$Result = Phpr::$request->cookie($CookieName);

			return $Html ? Phpr_Html::encode($Result) : $Result;
		}

		/**
		 * Indicates whether a login name was saved during last login.
		 * @return boolean
		 */
		public function isLoginNameSaved()
		{
			$CookieName = Phpr::$config->get('LOGIN_COOKIE_NAME', $this->loginCookieName);
			return Phpr::$request->cookie($CookieName) !== null;
		}

		/**
		 * Updates or deleted the user login name in a cookie
		 * @param string $Login Specifies the user login name
		 */
		protected function updateLoginName( $Save, $Login )
		{
			$CookieName = Phpr::$config->get('LOGIN_COOKIE_NAME', $this->loginCookieName);
			$CookiePath = Phpr::$config->get('AUTH_COOKIE_PATH', $this->cookiePath);
			$CookieDomain = Phpr::$config->get('AUTH_COOKIE_DOMAIN', $this->cookieDomain);

			if ( $Save )
				Phpr::$response->setCookie( $CookieName, $Login, 365, $CookiePath, $CookieDomain );
			else
				Phpr::$response->deleteCookie( $CookieName, $CookiePath, $CookieDomain );
		}

		/**
		 * Creates or updates the user authentication ticket
		 * @param int $Id Specifies the user identifier.
		 */
		protected function updateCookie( $Id )
		{
			/*
			 * Prepare the authentication ticket
			 */
			$Ticket = $this->getTicket( $Id );

			/*
			 * Set a cookie
			 */
			$CookieName = Phpr::$config->get('AUTH_COOKIE_NAME', $this->cookieName);
			$CookieLifetime = Phpr::$config->get($this->cookieLifetimeVar, $this->cookieLifetime);
			$CookiePath = Phpr::$config->get('AUTH_COOKIE_PATH', $this->cookiePath);
			$CookieDomain = Phpr::$config->get('AUTH_COOKIE_DOMAIN', $this->cookieDomain);

			Phpr::$response->setCookie( $CookieName, $Ticket, $CookieLifetime, $CookiePath, $CookieDomain );
			$this->cookiesUpdated = true;
		}

		/*
		 * Returns the authorization ticket for a specified user
		 * @param int $Id Specifies a user identifier
		 * @return string
		 */
		public function getTicket( $Id = null )
		{
			if ( $Id === null )
			{
				$User = $this->getUser();
				if ( !$User )
					return null;

				$Id = $User->id;
			}

			$lifetime = Phpr::$config->get($this->cookieLifetimeVar, $this->cookieLifetime);
			$lifetime = $lifetime > 0 ? $lifetime*24*3600 : 3600;
			
			$expiration = time()+$lifetime;

			$key = hash_hmac('md5', $Id.$expiration, Phpr_SecurityFramework::create()->salted_cookie());
			$hash = hash_hmac('md5', $Id.$expiration, $key);
			$ticket = base64_encode(base64_encode($Id).'|'.$expiration.'|'.$hash);

			return $ticket;
		}

		/**
		 * Validates authorization ticket
		 * @param string $Ticket Specifies an authorization ticket
		 * @return array Returns parsed ticket information if it is valid or null
		 */
		public function validateTicket( $Ticket, $cacheTicket = false )
		{
			if ($cacheTicket)
				$this->_ticket = $Ticket;
				
			$Ticket = base64_decode($Ticket);

			$parts = explode('|', $Ticket);
			if (count($parts) < 3)
				return null;

			list( $id, $expiration, $hmac ) = explode( '|', $Ticket );

			$id_decoded = base64_decode($id);
			
			if ( $expiration < time() )
				return null;

			$key = hash_hmac( 'md5', $id_decoded.$expiration, Phpr_SecurityFramework::create()->salted_cookie() );
			$hash = hash_hmac( 'md5', $id_decoded.$expiration, $key );

			if ( $hmac != $hash )
				return null;

			return array('user'=>$id);
		}

		/**
		 * Checks a user object before logging in
		 * @param mixed $User Specifies a user to check
		 */
		protected function checkUser( $User )
		{
			
		}
		
		protected function afterLogin( $User )
		{
		}

		protected function beforeLoginSessionDestroy( $User )
		{
		}
		
		protected function keepSessionData()
		{
			return false;
		}
		
		public function storeTicket()
		{
			$ticket_exists = true;
			$ticket_id = null;
			while ($ticket_exists)
			{
				$ticket_id = str_replace('.', '', uniqid('', true));
				$ticket_exists = Db_DbHelper::scalar('select count(*) from db_saved_tickets where ticket_id=:ticket_id', array('ticket_id'=>$ticket_id));
			}
			
			Db_DbHelper::query(
				'insert into db_saved_tickets(ticket_id, ticket_data, created_at) values (:ticket_id, :ticket_data, NOW())', 
				array(
					'ticket_id'=>$ticket_id,
					'ticket_data'=>$this->getTicket()
				));
				
			return $ticket_id;
		}
		
		public function restoreTicket($ticket_id)
		{
			$ticket_id = trim($ticket_id);
			
			if (!strlen($ticket_id))
				return null;
			
			$ttl = (int)Phpr::$config->get('STORED_SESSION_TTL', 3);
			Db_DbHelper::query('delete from db_saved_tickets where created_at < DATE_SUB(now(), INTERVAL :seconds SECOND)', array('seconds'=>$ttl));
			$data = Db_DbHelper::scalar('select ticket_data from db_saved_tickets where ticket_id=:ticket_id', array('ticket_id'=>$ticket_id));
			if (!$data)
				return null;
				
			Db_DbHelper::query('delete from db_saved_tickets where ticket_id=:ticket_id', array('ticket_id'=>$ticket_id));
				
			return $data;
		}
		
		public function removeTicket($ticket_id)
		{
			$ticket_id = trim($ticket_id);
			
			if (!strlen($ticket_id))
				return null;

			Db_DbHelper::query('delete from db_saved_tickets where ticket_id=:ticket_id', array('ticket_id'=>$ticket_id));
		}
	}

?>