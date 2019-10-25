<?php

	/**
	 * @has_documentable_methods
	 */
	class Core_Module extends Core_ModuleBase
	{
		/**
		 * Creates the module information object
		 * @return Core_ModuleInfo
		 */
		protected function createModuleInfo()
		{
			return new Core_ModuleInfo(
				"Core",
				"LemonStand core module",
				"LemonStand eCommerce Inc." );
		}

		public function subscribeEvents()
		{
			Backend::$events->addEvent('onLogin', $this, 'onUserLogin');
			Backend::$events->addEvent('core:onAfterSoftwareUpdate', $this, 'onAfterSoftwareUpdate');
		}
		
		public function onUserLogin()
		{
			$handler_path = PATH_APP.'/handlers/login.php';
			if (file_exists($handler_path))
				include $handler_path;
		}

		public function onAfterSoftwareUpdate(){
			$framework_update = PATH_APP.'/modules/core/updates/framework_update.zip';
			$framework_folder = PATH_APP.'/phproad/';
			if (file_exists($framework_update)){
				Core_ZipHelper::unzip($framework_folder, $framework_update, $update_file_permissions = true);
				unlink($framework_update);
			}
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
			return array(
				'System variables'=>array(
					'recipient_email'=>array('Outputs the email recipient email address', '{recipient_email}'),
					'email_subject'=>array('Outputs the email subject', '{email_subject}')
				)
			);
		}
		
		public function listSettingsItems()
		{
			$eula_info = Core_EulaInfo::get();
			$eula_update_str = null;
			if ($eula_info->accepted_on)
				$eula_update_str = sprintf(' Last updated on %s.', Phpr_Date::display($eula_info->accepted_on));
				
			$user = Phpr::$security->getUser();
			$is_unread = Core_EulaInfo::is_unread($user->id);

			return array(
				array(
					'icon'=>'/modules/core/resources/images/new_page.png', 
					'title'=>'License Agreement', 
					'url'=>'/core/viewlicenseagreement',
					'description'=>'View LemonStand End User License Agreement.'.$eula_update_str,
					'sort_id'=>200,
					'section'=>'System',
					'class'=>($is_unread ? 'unread' : null)
				)
			);
		}
		
		/*
		 * Event descriptions
		 */
		
		/**
		 * Triggered when the system initialization finishes and before any AJAX or POST handler is invoked. 
		 * Usage example:
		 * <pre>
		 * public function subscribeEvents()
		 * {
		 *   Backend::$events->addEvent('core:onInitialize', $this, 'core_initialize');
		 * }
		 * 
		 * public function core_initialize()
		 * {
		 *   // Do something
		 * }
		 * </pre>
		 * @event core:onInitialize
		 * @triggered /modules/backend/init/custom_helpers.php
		 * @see core:onUninitialize
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 */
		private function event_onInitialize() {}
			
		/**
		 * Triggered when the script execution finishes. 
		 * The event is fired even if the <em>exit()</em> or <em>die()</em> function has been called. 
		 * Usage example:
		 * <pre>
		 * public function subscribeEvents()
		 * {
		 *   Backend::$events->addEvent('core:onUninitialize', $this, 'core_uninitialize');
		 * }
		 * 
		 * public function core_uninitialize()
		 * {
		 *   // Do something
		 * }
		 * </pre>
		 * @event core:onUninitialize
		 * @triggered /modules/backend/init/custom_helpers.php
		 * @see core:onInitialize
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 */
		private function event_onUninitialize() {}
	}
?>