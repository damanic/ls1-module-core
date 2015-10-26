<?

	class Core_Settings extends Backend_Controller
	{
		public $implement = 'Db_FormBehavior';

		public function edit($module_id, $form_id)
		{
			Backend::$events->fireEvent('core:onConfigureModuleSettingsController', $this, $form_id);
			
			try
			{
				$form_params = $this->get_form_parameters($module_id, $form_id);
				$is_personal = isset($form_params['personal']) && $form_params['personal'];

				$this->app_page_title =  isset($form_params['title']) ? $form_params['title'] : 'Parameters';
				$this->app_module_name = $is_personal ? 'My Settings' : 'Settings';

				if ($is_personal)
					$this->override_module_name = $this->app_page_title;
				else {
					$this->app_module = 'system';
					$this->app_tab = 'system';
					$this->app_module_name = 'System';
					$this->app_page = 'settings';
				}

				$this->viewData['form_model'] = Core_ModuleSettings::create($module_id, $form_id);
				$this->viewData['is_personal'] = $is_personal;
				$this->viewData['form_width'] = isset($form_params['width_class']) ? $form_params['width_class'] : 'form-600';
			}
			catch (exception $ex)
			{
				$this->handlePageError($ex);
			}
		}
		
		protected function get_form_parameters($module_id, $form_id)
		{
			$module = Core_ModuleManager::findById($module_id);
			if (!$module)
				throw new Phpr_ApplicationException('Module with the specified code is not found');
				
			$settings_forms = $module->listSettingsForms();
			if (!array_key_exists($form_id, $settings_forms))
				throw new Phpr_ApplicationException('Settings form with the specified code is not found');

			$form_params = $settings_forms[$form_id];
			$is_personal = isset($form_params['personal']) && $form_params['personal'];
			if (!$is_personal && !$this->currentUser->is_administrator())
				throw new Phpr_ApplicationException('You have no rights to access this page');

			return $form_params;
		}
		
		protected function edit_onSave($module_id, $form_id)
		{
			try
			{
				$form_params = $this->get_form_parameters($module_id, $form_id);
				$is_personal = isset($form_params['personal']) && $form_params['personal'];
				
				$obj = Core_ModuleSettings::create($module_id, $form_id);
				$obj->save(post('Core_ModuleSettings', array()), $this->formGetEditSessionKey());
				
				Phpr::$session->flash['success'] = 'Settings have been saved.';
				
				if ($is_personal)
					Phpr::$response->redirect(url('system/mysettings'));
				else
					Phpr::$response->redirect(url('system/settings'));
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
	}

?>