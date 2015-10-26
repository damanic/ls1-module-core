<?

	class Core_ModuleSettings extends Core_Configuration_Model
	{
		protected $module_id;
		protected $form_code;
		protected $module_obj = false;
		
		public static function create($module_id, $form_code)
		{
			$record_code = self::get_record_code($module_id, $form_code);
			if (array_key_exists($record_code, self::$loaded_objects))
				return self::$loaded_objects[$record_code];

			$obj = new self();
			return $obj->get($module_id, $form_code);
		}

		public function get($module_id, $form_code)
		{
			$record_code = self::get_record_code($module_id, $form_code);
			Db_ActiveRecord::disable_column_cache();

			$obj = $this->find_by_record_code($record_code);
			if (!$obj)
			{
				$class_name = get_class($this);
				$obj = new $class_name();
			}

			$obj->module_id = $module_id;
			$obj->form_code = $form_code;
			$obj->record_code = $record_code;
			
			$obj->define_form_fields();
			
			self::$loaded_objects[$record_code] = $obj;
			
			return $obj;
		}

		public function add_field($code, $title, $side = 'full', $type = db_text, $options = array())
		{
			$form_field = parent::add_field($code, $title, $side, $type, $options);
			$form_field->optionsMethod('get_added_field_options');
			
			return $form_field;
		}
		
		public function get_added_field_options($db_name, $current_key_value = -1)
		{
			$result = $this->get_module_obj()->getSettingsFieldOptions($this, $this->form_code, $db_name);
			if (!is_array($result))
				return array();
				
			return $result;
		}
		
		protected function get_module_obj()
		{
			if ($this->module_obj !== false)
				return $this->module_obj;
			
			$this->module_obj = self::load_module_obj($this->module_id);
			if (!$this->module_obj)
				throw new Phpr_SystemException(sprintf('Module %s not found', $this->module_id));
				
			return $this->module_obj;
		}
		
		protected static function load_module_obj($module_id)
		{
			$result = Core_ModuleManager::findById($module_id);
			if (!$result)
				throw new Phpr_SystemException(sprintf('Module %s not found', $module_id));
				
			return $result;
		}
		
		protected static function get_record_code($module_id, $form_code)
		{
			$module = self::load_module_obj($module_id);
			$settings_forms = $module->listSettingsForms();
			if (!array_key_exists($form_code, $settings_forms))
				throw new Phpr_ApplicationException('Settings form with the specified code is not found');

			$form_params = $settings_forms[$form_code];
			$personal = isset($form_params['personal']) && $form_params['personal'];
			
			if ($personal && $user = Phpr::$security->getUser())
				$personal = '-u'.$user->id;

			return $module_id.'-'.$form_code.$personal;
		}
		
		protected function build_form()
		{
			$this->get_module_obj()->buildSettingsForm($this, $this->form_code);
		}

		protected function init_config_data()
		{
			$this->get_module_obj()->initSettingsData($this, $this->form_code);
		}
		
		protected function validate_config_on_save()
		{
			$this->get_module_obj()->validateSettingsData($this, $this->form_code);
		}
	}

?>