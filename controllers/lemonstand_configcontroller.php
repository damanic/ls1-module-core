<?

	class LemonStand_ConfigController extends Phpr_Controller
	{
		private $security_obj = null;
		private $public_actions = array('login');
		public $app_page_title = 'Configuration';
		
		public function __construct()
		{
			parent::__construct();

			$this->security_obj = new Core_ConfigSecurity();

			$this->layoutsPath = PATH_APP.'/modules/core/layouts';
			$this->layout = 'config';
			$this->viewPath = PATH_APP.'/modules/core/controllers/'.strtolower(get_class($this));
			$this->addCss('/modules/core/resources/css/config.css');
			$this->addCss('/modules/backend/themes/blue/css/theme.css');

			$isPublicAction = in_array(Phpr::$router->action, $this->public_actions);
			if (!$isPublicAction)
				$this->security_obj->baseAuthorization();
				
			if (!$isPublicAction)
				$this->viewData['log'] = $this->security_obj->get_login_log();
		}
		
		protected function config_url()
		{
			$config_url = Phpr::$config->get('CONFIG_URL', 'config_tool');
			if (substr($config_url, 0, 1) != '/')
				$config_url = '/'.$config_url;
				
			return root_url($config_url);
		}
		
		public function index()
		{
			$this->viewData['body_class'] = 'no_bottom_offset';
		}

		/*
		 * Login and logout
		 */

		public function login()
		{
			$this->layout = 'login';
		}

		public function logout()
		{
			$this->security_obj->logout($this->config_url().'/login');
		}
		
		protected function login_onsubmit()
		{
			try
			{
				$this->security_obj->login($this->validation, $this->config_url());
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		/*
		 * MySQL settings
		 */
		
		public function mysql()
		{
			$this->app_page_title = 'MySQL Settings';
			$this->viewData['mysql_params'] = Db_SecureSettings::get();
		}
		
		protected function mysql_onSave()
		{
			try
			{
				$this->validation->add('host', 'MySQL Host')->fn('trim')->required('Please specify MySQL Host');
				$this->validation->add('database', 'Database Name')->fn('trim')->required('Please specify MySQL Database Name');
				$this->validation->add('user', 'MySQL User')->fn('trim');
				$this->validation->add('password', 'MySQL Password')->fn('trim');
				if (!$this->validation->validate($_POST))
					$this->validation->throwException();

				$connection = Db_DbHelper::driver()->create_connection($this->validation->fieldValues['host'], 
					$this->validation->fieldValues['user'], 
					$this->validation->fieldValues['password']);

				if (!$connection)
					throw new Phpr_SystemException('Error connecting to MySQL server. MySQL error: '.Db_DbHelper::driver()->get_last_error_string());
					
				$select_result = @Db_DbHelper::driver()->select_db($connection, $this->validation->fieldValues['database']);

				if ($connection)
					Db_DbHelper::driver()->close_connection($connection);
					
				if (!$select_result)
					throw new Phpr_SystemException('Unable to select database '.$this->validation->fieldValues['database'].'. Please check whether the database exists and the user specified has corresponding permissions.');
					
				Db_SecureSettings::set($this->validation->fieldValues);
				
				Phpr::$session->flash['success'] = 'MySQL settings have been successfully saved.';
				Phpr::$response->redirect($this->config_url());
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		/*
		 * Configuration tool security
		 */
		
		public function config_security()
		{
			$this->app_page_title = 'Configuration Tool Security';
			$framework = Phpr_SecurityFramework::create();
			$config_content = $framework->get_config_content();

			$this->viewData['user'] = $config_content['config_user'];
		}
		
		protected function config_security_onSave()
		{
			try
			{
				$this->validation->add('current_password', 'Current Password')->fn('trim')->required('Please specify the current Configuration Tool password');
				$this->validation->add('user', 'User Name')->fn('trim')->required('Please specify a user name');
				$this->validation->add('password', 'Password')->fn('trim')->required('Please specify a password');
				$this->validation->add('confirm', 'Password Confirmation')->fn('trim')->required('Please specify Password Confirmation')->matches('password', 'The password and password confirmation do not match.');
				if (!$this->validation->validate($_POST))
					$this->validation->throwException();

				$framework = Phpr_SecurityFramework::create();
				$config_content = $framework->get_config_content();
				
				if ($config_content['config_pwd'] != $framework->salted_hash($this->validation->fieldValues['current_password']))
					$this->validation->setError('Invalid current Configuration Tool password', 'password', true);
				
				$config_content['config_user'] = $this->validation->fieldValues['user'];
				$config_content['config_pwd'] = $framework->salted_hash($this->validation->fieldValues['password']);
				$framework->set_config_content($config_content);

				Phpr::$session->flash['success'] = 'User name and password have been successfully updated.';
				Phpr::$response->redirect($this->config_url());
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		/*
		 * Encryption settings
		 */
		
		public function encryption()
		{
			$this->app_page_title = 'Encryption Settings';
		}
		
		protected function encryption_onSave()
		{
			try
			{
				$this->validation->add('key', 'Encryption Key')->fn('trim')->required('Please specify encryption key')->minLength(6, 'The encryption key must be at least 6 characters in length.');
				$this->validation->add('password', 'Password')->fn('trim')->required('Please specify Password');
				if (!$this->validation->validate($_POST))
					$this->validation->throwException();

				$framework = Phpr_SecurityFramework::create();
				$config_content = $framework->get_config_content();

				if ($config_content['config_pwd'] != $framework->salted_hash($this->validation->fieldValues['password']))
					$this->validation->setError('Invalid Configuration Tool password', 'password', true);
					
				$key = $config_content['config_key'] = $this->validation->fieldValues['key'];
				$config_content['config_pwd'] = $framework->salted_hash($this->validation->fieldValues['password'], $key);
				
				$enc_test_value = null;
				try
				{
					$enc_test_value = Db_ModuleParameters::get('core', 'enc_test');
				} 
				catch (Exception $ex) {}
				
				if ($enc_test_value !== null)
				{
					if (Phpr_SecurityFramework::create()->salted_hash('lemonstand', $key) != $enc_test_value)
						$this->validation->setError('The encryption key used to encrypt data in the database do not match the encryption key you specified.', 'key', true);
				}

				$framework->set_config_content($config_content);
				
				Phpr::$session->flash['success'] = 'Encryption key has been successfully updated.';
				Phpr::$response->redirect($this->config_url());
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		/*
		 * Restore the encryption key
		 */
		
		public function restore_key()
		{
		}
		
		protected function restore_key_onRestore()
		{
			try
			{
				$framework = Phpr_SecurityFramework::create();
				$config_content = $framework->get_config_content();
				
				$this->validation->add('password', 'Password')->fn('trim')->required('Please specify Password');
				if (!$this->validation->validate($_POST))
					$this->validation->throwException();
				
				if ($config_content['config_pwd'] != $framework->salted_hash($this->validation->fieldValues['password']))
					$this->validation->setError('Invalid Configuration Tool password', 'password', true);
					
				if (!array_key_exists('config_key', $config_content))
					throw new Phpr_SystemException('Invalid configuration file.');
					
				Phpr::$session->flash['success'] = 'The encryption key for this LemonStand installation is '.$config_content['config_key'];
				Phpr::$response->redirect($this->config_url());
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		/*
		 * Acces log
		 */
		
		public function access_log()
		{
			$this->app_page_title = 'Access Log';
			$this->viewData['log'] = $this->security_obj->get_login_log();
			$this->viewData['unsuccessful_log'] = $this->security_obj->get_unsuccessful_login_log();
		}
	}


?>