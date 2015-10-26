<?php

	class Core_ConfigSecurity extends Phpr_Security
	{
		public $userClassName = "Core_ConfigUser";
		public $cookieName = "config";
		protected $cookieLifetimeVar = 'CONFIG_AUTH_COOKIE_LIFETIME';

		protected static $UserCache = array();

		public function login(Phpr_Validation $Validation = null, $Redirect = null, $Login = null, $Password = null)
		{
			if ( $Login === null )
				$Login = Phpr::$request->post('login');

			if ( $Password === null )
				$Password = Phpr::$request->post('password');

			try
			{
				return parent::login($Validation, $Redirect, $Login, $Password);
			} catch (Exception $ex)
			{
				$this->log_login_attempt($Login);
				
				throw $ex;
			}
		}

		public function getUser()
		{
			if ( $this->user !== null )
				return $this->user;

			/*
			 * Determine whether the authentication cookie is available
			 */

			$CookieName = $this->cookieName;
			$Ticket = Phpr::$request->cookie( $CookieName );

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
			$UserId = trim(base64_decode($Ticket['user']));
			if ( !strlen($UserId) )
				return null;
			
			return $this->findUser($UserId);
		}

		public function authorize_user()
		{
			$user = $this->getUser();

			if (!$user)
				return null;

			$this->updateCookie( $user->id );
			return $user;
		}
		
		public function baseAuthorization()
		{
			/*
			 * Check if user is authenticated and redirect to the Login page of not so.
			 */
			if ( $this->getUser() == null )
			{
				$current_uri = Phpr::$request->getRootUrl();
				$config_url = Phpr::$config->get('CONFIG_URL', 'config_tool');
				if (substr($config_url, 0, 1) == '/')
					$config_url = substr($config_url, 1);

				Phpr::$response->redirect( root_url('/'.$config_url.'/login') );
			}
			
			$user = $this->getUser();
			$this->updateCookie( $user->id );
		}

		protected function updateCookie($Id)
		{
			/*
			 * Prepare the authentication ticket
			 */
			$Ticket = $this->getTicket( $Id );

			/*
			 * Set a cookie
			 */

			$CookieName = $this->cookieName;
			$CookieLifetime = Phpr::$config->get($this->cookieLifetimeVar, $this->cookieLifetime);

			$CookiePath = '/';
			$CookieDomain = '';

			Phpr::$response->setCookie( $CookieName, $Ticket, $CookieLifetime, $CookiePath, $CookieDomain );
		}

		public function logout($Redirect = null)
		{
			$CookieName = $this->cookieName;
			$CookiePath = '/';
			$CookieDomain = '';

			Phpr::$response->deleteCookie( $CookieName, $CookiePath, $CookieDomain );

			$this->user = null;

			Phpr::$session->destroy();

			if ( $Redirect !== null )
				Phpr::$response->redirect( $Redirect );
		}

		public function findUser($UserId)
		{
			if (isset(self::$UserCache[$UserId]))
				return self::$UserCache[$UserId];
			
			return self::$UserCache[$UserId] = new Core_ConfigUser();
		}

		protected function afterLogin($User)
		{
			$framework = Phpr_SecurityFramework::create();
			$config_content = $framework->get_config_content();

			$login_log = array_key_exists('login_log', $config_content) ? $config_content['login_log'] : array();

			$log_record = Phpr_DateTime::now()->getInteger().'|'.Phpr::$request->getUserIp();
			$login_log[] = $log_record;
			
			if (count($login_log) > 10)
				array_shift($login_log);
				
			$config_content['login_log'] = $login_log;
			$framework->set_config_content($config_content);
		}
		
		protected function log_login_attempt($user_name)
		{
			$framework = Phpr_SecurityFramework::create();
			$config_content = $framework->get_config_content();

			$login_log = array_key_exists('unsuccessful_login_log', $config_content) ? $config_content['unsuccessful_login_log'] : array();

			$log_record = Phpr_DateTime::now()->getInteger().'|'.Phpr::$request->getUserIp().'|'.$user_name;
			$login_log[] = $log_record;
			
			if (count($login_log) > 10)
				array_shift($login_log);
				
			$config_content['unsuccessful_login_log'] = $login_log;
			$framework->set_config_content($config_content);
		}
		
		public function get_login_log()
		{
			$framework = Phpr_SecurityFramework::create();
			$config_content = $framework->get_config_content();
			$login_log = array_key_exists('login_log', $config_content) ? $config_content['login_log'] : array();

			$result = array();
			foreach ($login_log as $log_record)
			{
				$parts = explode('|', $log_record);
				if (count($parts) != 2)
					continue;
					
				$time = new Phpr_DateTime();
				$time->setInteger($parts[0]);
				$item = array('time'=>$time, 'ip'=>$parts[1]);
				$result[] = (object)$item;
			}

			return array_reverse($result);
		}
		
		public function get_unsuccessful_login_log()
		{
			$framework = Phpr_SecurityFramework::create();
			$config_content = $framework->get_config_content();

			$login_log = array_key_exists('unsuccessful_login_log', $config_content) ? $config_content['unsuccessful_login_log'] : array();

			$result = array();
			foreach ($login_log as $log_record)
			{
				$parts = explode('|', $log_record);
				if (count($parts) != 3)
					continue;
					
				$time = new Phpr_DateTime();
				$time->setInteger($parts[0]);
				$item = array('time'=>$time, 'ip'=>$parts[1], 'user'=>$parts[2]);
				$result[] = (object)$item;
			}

			return array_reverse($result);
		}
	}
	
?>