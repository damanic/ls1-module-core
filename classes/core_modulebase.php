<?php

	/**
	 * Module base class
	 */
	abstract class Core_ModuleBase
	{
		private $_moduleInfo = null;

		/**
		 * Returns information about the module.
		 * @return Core_ModuleInfo
		 */
		public function getModuleInfo()
		{
			if ( $this->_moduleInfo !== null )
				return $this->_moduleInfo;
				
			$this->_moduleInfo = $this->createModuleInfo();
			$this->_moduleInfo->id = basename($this->getModulePath());

			return $this->_moduleInfo;
		}

		/**
		 * Creates the module information object
		 * @return Core_ModuleInfo
		 */
		abstract protected function createModuleInfo();

		/**
		 * Returns a list of the module back-end GUI tabs.
		 * @param Backend_TabCollection $tabCollection A tab collection object to populate.
		 * @return mixed
		 */
		public function listTabs($tabCollection)
		{
			return null;
		}
		
		/**
		 * Returns notifications to be displayed in the main menu.
		 * @return array Returns an array of notifications in the following format:
		 * array(
		 *    array(
		 *      'id'=>'new-tickets',
		 *      'closable'=>false,
		 *      'text'=>'10 new support tickets',
		 *      'icon'=>'resources/images/notification.png',
		 *      'link'=>'/support/tickets'
		 *    )
		 * ).
		 * The 'link', 'id' and 'closable' keys are optional, but id should be specified if closable is true.
		 * Use the url() function to create values for the 'link' value.
		 * The icon should be a PNG image of size 16x16. Icon path should be specified relative to the module
		 * root directory.
		 */
		public function listMenuNotifications()
		{
			return array();
		}
		
		public function getId()
		{
			return $this->getModuleInfo()->id;
		}
		
		public function getModulePath()
		{
			$refObj = new ReflectionObject($this);
			return dirname(dirname($refObj->getFileName()));
		}
		
		/**
		 * Returns a list links to add to the System/Settings page.
		 * Links should be declared as follows:
		 * return array(array(
		 * 		'icon'=>'/modules/module/resources/images/settings.gif', 
		 *		'url'=>'/cms/settings/stats', 
		 *		'title'=>'CMS Settings', 
		 *		'description'=>'Configure CMS parameters here'),
		 *		'sort_id'=>10,
		 *		'section'=>'CMS'
		 * )
		 * Image size must be 48x48 px
		 * @return array
		 */
		public function listSettingsItems()
		{
			return array();
		}
		
		/**
		 * Returns a list links to add to the Personal Settings page.
		 * Links should be declared as follows:
		 * return array(array(
		 * 		'icon'=>'/modules/module/resources/images/settings.gif', 
		 *		'url'=>'/cms/settings/stats', 
		 *		'title'=>'CMS Settings', 
		 *		'description'=>'Configure CMS parameters here'),
		 *		'sort_id'=>10
		 * )
		 * Image size must be 48x48 px
		 * @return array
		 */
		public function listPersonalSettingsItems()
		{
			return array();
		}
		
		/**
		 * Override this method in your modules to subscribe on Backend events
		 * Example: Backend::$events->addEvent('cms:onDeletePage', $this, 'allowDeletePage');
		 */
		public function subscribeEvents()
		{
		}

		//
		// Subscribe to general cron table. Method must return true to indicate success.
		// Interval is in minutes.
		//
		public function subscribe_crontab()
		{
			// Usage:
			// return array('reset_counters' => array('method'=>'local_method', 'interval'=>60));
			return array();
		}

		/**
		 * Builds user permissions interface
		 * For drop-down and radio fields you should also add methods returning 
		 * options. For example, of you want to have "Access Level" drop-down:
		 * public function get_access_level_options();
		 * This method should return array with keys corresponding your option identifiers
		 * and values corresponding its titles.
		 * 
		 * @param $host_obj ActiveRecord object to add fields to
		 */
		public function buildPermissionsUi($host_obj)
		{
		}
		
		/**
		 * Returns a list of email template variables provided by the module.
		 * The method must return an array of section names, variable names, 
		 * descriptions and demo-values:
		 * array('Shop variables'=>array(
		 * 	'order_total'=>array('Outputs order total value', '$99.99')
		 * ))
		 * @return array
		 */
		public function listEmailVariables()
		{
			return array();
		}

		/**
		 * Returns a list of HTML Editor configurations used by the module
		 * The method must return an array of configuration codes and descriptions:
		 * array('blog_post_content'=>'Blog post content')
		 * @return array
		 */
		public function listHtmlEditorConfigs()
		{
			return array();
		}
		
		/**
		 * Replaces email template variables with real values
		 * @param string $template_text Specifies a message template text to replace values in
		 * @param Shop_Order $order Order object
		 * @param Shop_Customer $customer Customer object
		 * @return string Returns processed template string
		 */
		public function applyEmailVariables($template_text, $order, $customer)
		{
			return $template_text;
		}

		/**
		 * Returns a list of dashboard indicators in format
		 * array('indicator_code'=>array('partial'=>'partial_name.htm', 'name'=>'Indicator Name')).
		 * Partials must be placed to the module dashboard directory:
		 * /modules/cms/dashboard
		 */
		public function listDashboardIndicators()
		{
			return array();
		}
		
		/**
		 * Returns a list of dashboard reports in format
		 * array('report_code'=>array('partial'=>'partial_name.htm', 'name'=>'Report Name')).
		 * Partials must be placed to the module dashboard directory:
		 * /modules/cms/dashboard
		 */
		public function listDashboardReports()
		{
			return array();
		}
		
		/**
		 * Return a list of module reports
		 * @return array
		 *
		 * Acceptable formats:
		 *
		 *  array(
		 *        'report_id_1'=>'Report 1'
		 *        'report_id_2'=>'Report 2'
		 * )
		 *
		 * OR
		 *
		 *  array(
		 *		array(
		 *			'name' => 'Report Group 1',
		 *			'reports' => array(
		 *        		'report_id_1'=>'Report 1'
		 *        		'report_id_2'=>'Report 2'
		 *			)
		 *		),
		 *		array(
		 *			'name' => 'Report Group 2',
		 *			'reports' => array(
		 *        		'report_id_3'=>'Report 3'
		 *        		'report_id_4'=>'Report 4'
		 *			)
		 *		)
		 *	);
		 */
		public function listReports()
		{
			return array();
		}
		
		/*
		 * Returns a list of module email variable scopes
		 * array('order'=>'Order')
		 */
		public function listEmailScopes()
		{
			return array();
		}
		
		/**
		 * Returns a list of the module settings forms.
		 * @return array. Returns data in the following format: array('form-id'=>array(... form parameters ...))
		 * The form parameters array can have the following keys:
		 * - icon - path to the settings icon file relative to the system root, for example '/modules/settingsapi/resources/images/green_energy.png'. Icons should be be 48x48 px PNG files.
		 * - title - settings link title
		 * - description - settings link description
		 * - sort_id - sort order, numeric
		 * - personal - determines whether the link should be displayed on System/Settings or on My Settings page
		 * - width_class - CSS class name defining the settings form width. Known class names are form-300, form-400, 450, form-500, form-650, form-700, form-750, form-800, form-850
		 */
		public function listSettingsForms()
		{
			return array();
		}
		
		/**
		 * Builds a module settings form.
		 * @param Core_ModuleSettings $model Model to add form fields to. 
		 * Create new fields by calling the addField method of the model class. Example:
		 * $model->add_field('font_size', 'Font size', 'left', db_number)
		 * @param string $form_code Form code, one of the codes returned by listSettingsForms() method.
		 */
		public function buildSettingsForm($model, $form_code)
		{
		}
		
		/**
		 * Initializes the module settings.
		 * @param Core_ModuleSettings $model Model to set fields values to. Example:
		 * $model->font_size = 10;
		 * @param string $form_code Form code, one of the codes returned by listSettingsForms() method.
		 */
		public function initSettingsData($model, $form_code)
		{
		}
		
		/**
		 * Validates the module settings before they are saved to the database.
		 * @param Core_ModuleSettings $model Model to read fields values from. Use the model validation object
		 * to trigger validation errors. Example:
		 * if ($model->font_size == 10)
		 *  $model->validation->setError('10 is not allowed here', 'font_size', true);
		 * @param string $form_code Form code, one of the codes returned by listSettingsForms() method.
		 */
		public function validateSettingsData($model, $form_code)
		{
		}

		/**
		 * Returns options for a setting form drop-down or radio button fields.
		 * @param Core_ModuleSettings $model Model to read fields values from.
		 * @param string $form_code Form code, one of the codes returned by listSettingsForms() method.
		 * @param string $field_code Field code.
		 * @return array The method should return an array of key-value pairs. Example: 
		 * return array('1'=>'1pt', '2'=>'2pt');
		 */
		public function getSettingsFieldOptions($model, $form_code, $field_code)
		{
		}

		/**
		 * Registers a hidden page with specific URL. 
		 * @return array Returns an array containing page URLs and methods to call for each URL:
		 * return array('some_hidden_page'=>'process_hidden_page'). The processing methods must be declared 
		 * in the module class. Page processing methods must accept one parameter - an array of URL segments 
		 * following the access point. For example, if URL is /some_hidden_page/1234 an array with single
		 * value '1234' will be passed to process_hidden_page method 
		 */
		public function register_access_points()
		{
			return array();
		}
	}

?>