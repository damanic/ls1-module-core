<?php

	/**
	 * Adds form handling features to back-end controllers.
	 * This class allows to extend any back-end controller with a form handling functionality.
	 * It allows to render Create, Edit and Preview forms along with processing corresponding
	 * AJAX events for saving and deleting records.
	 * 
	 * The extension can be added to a controller by listing the class name in the <em>$implement</em> property of the controller class:
	 * <pre>
	 * class AbcBlog_Posts extends Backend_Controller
	 * {
	 *   public $implement = 'Db_FormBehavior, Db_ListBehavior';
	 *   ...
	 * </pre>
	 * The class works only with {@link Db_ActiveRecord} models. {@link Db_ActiveRecord::define_columns() Model columns} 
	 * and {@link Db_ActiveRecord::define_form_fields() form fields} should be defined in the model in order to be displayed in a form.
	 * 
	 * To configure the extension, its properties should be defined in the extended controller class. Only the {@link Db_FormBehavior::$form_model_class $form_model_class}
	 * property is required. Please read the {@link http://lemonstand.com/docs/administration_area_forms/ Administration Area Forms} article for the usage examples and details.
	 *
	 * @documentable
	 * @see http://lemonstand.com/docs/administration_area_forms/ Administration Area Forms
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 * @see Db_ListBehavior
	 * @see Db_FilterBehavior
	 */
	class Db_FormBehavior extends Phpr_ControllerBehavior
	{
		/**
		 * @var string Specifies a title of the Create Record page.
		 * The default value is <em>Create</em>.
		 * @documentable
		 */
		public $form_create_title = 'Create';
		
		/**
		 * @var string Specifies a title of the Edit Record page.
		 * The default value is <em>Edit</em>.
		 * @documentable
		 */
		public $form_edit_title = 'Edit';

		/**
		 * @var string Specifies a title of the Preview Record page.
		 * The default value is <em>Preview</em>.
		 * @documentable
		 */
		public $form_preview_title = 'Preview';

		/**
		 * @var string Message to display on the Edit and Preview pages in case if the record is not found.
		 * The default value is <em>"Record not found"</em>.
		 * @documentable
		 */
		public $form_not_found_message = 'Record not found';

		/**
		 * @var string A name of the model class.
		 * @documentable
		 */
		public $form_model_class = null;

		/**
		 * @var string An URL to redirect the browser after a form is saved or the record is deleted.
		 * Use the {@link url()} for creating back-end URLs. Example:
		 * <pre>$this->form_redirect = url('/shop/product_types');</pre>
		 * @documentable
		 */
		public $form_redirect = null;
		
		/**
		 * @var string An URL to redirect the browser after the Create form is successfully processed.
		 * This value overrides the {@link Db_FormBehavior::$form_redirect $form_redirect} property.
		 * @documentable
		 */
		public $form_create_save_redirect = null;
		
		/**
		 * @var string An URL to redirect the browser after the Save form is successfully processed.
		 * This value overrides the {@link Db_FormBehavior::$form_redirect $form_redirect} property.
		 * @documentable
		 */
		public $form_edit_save_redirect = null;
		
		/**
		 * @var string An URL to redirect the browser after a record has been deleted.
		 * This value overrides the {@link Db_FormBehavior::$form_redirect $form_redirect} 
		 * and {@link Db_FormBehavior::$form_edit_save_redirect $form_edit_save_redirect} properties.
		 * @documentable
		 */
		public $form_delete_redirect = null;
		
		/**
		 * @var string A message to display after an existing record has been successfully saved.
		 * @documentable
		 */
		public $form_edit_save_flash = null;
		
		/**
		 * @var string A message to display after a record has been successfully deleted.
		 * @documentable
		 */
		public $form_edit_delete_flash = null;
		
		/**
		 * @var string A message to display after a new record has been successfully saved.
		 * @documentable
		 */
		public $form_create_save_flash = null;
		public $form_edit_save_auto_timestamp = false;
		public $form_create_context_name = null;
		public $form_flash_id = null;
		public $form_no_flash = false;
		public $form_grid_csv_export_url = null;
		public $form_grid_max_rows = 300;
		public $form_disable_ace = false;

		public $form_preview_mode = false;
		public $form_report_layout_mode = false;
		public $form_unique_prefix = null;
		
		public $enable_concurrency_locking = false;

		/**
		 * Specifies a tab type. Supported values are: tabs, sliding.
		 * @var string
		 */
		public $form_tabs_type = 'tabs';
		
		protected $_model = null;
		protected $_edit_session_key = null;
		protected $_context = null;
		protected $_widgets = array();

		public function __construct($controller)
		{
			parent::__construct($controller);
			
			if (!$controller)
				return;

			$this->formLoadResources();

			$this->hideAction('create_onSave');
			$this->hideAction('edit_onSave');
			$this->hideAction('create_onCancel');
			$this->hideAction('edit_onCancel');
			
			$this->hideAction('edit_formBeforeRender');
			$this->hideAction('create_formBeforeRender');
			$this->hideAction('preview_formBeforeRender');
			$this->hideAction('formBeforeCreateSave');
			$this->hideAction('formBeforeEditSave');
			$this->hideAction('formBeforeSave');
			$this->hideAction('formAfterEditSave');
			$this->hideAction('formAfterDelete');

			$this->addEventHandler('onUpdateFileList');
			$this->addEventHandler('onDeleteFile');
			$this->addEventHandler('onPreviewPopup');
			$this->addEventHandler('onFindFormRecord');
			$this->addEventHandler('onSetRecordFinderRecord');
			$this->addEventHandler('onStealLock');
			$this->addEventHandler('onSetFormFilesOrder');
			$this->addEventHandler('onShowFileDescriptionForm');
			$this->addEventHandler('onSaveFormFileDescription');
			$this->addEventHandler('onSaveFormGridCsv');
			$this->addEventHandler('onLoadFormGridCsvPopup');
			$this->addEventHandler('onFormGridCsvUploaded');
			$this->addEventHandler('onFormToggleCollapsableArea');
			$this->addEventHandler('onFormWidgetEvent');
			
			$this->_controller->addPublicAction('form_file_upload');
			
			if (post('record_finder_flag'))
				$this->formPrepareRecordFinderList();
		}

		/**
		 * Actions
		 */
		
		public function create($context = null)
		{
			try
			{
				$this->_context = !strlen($context) ? $this->_controller->form_create_context_name : $context;

				$this->_controller->app_page_title = $this->_controller->form_create_title;
				$this->_controller->viewData['form_model'] = $this->viewData['form_model'] = $this->_controller->formCreateModelObject();

				if ($this->controllerMethodExists('create_formBeforeRender'))
					$this->_controller->create_formBeforeRender($this->viewData['form_model']);
			}
			catch (exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}
		}

		public function edit($recordId, $context = null)
		{
			try
			{
				$this->_context = $context;
				
				$this->_controller->viewData['form_record_id'] = $recordId;
				$this->_controller->app_page_title = $this->_controller->form_edit_title;
				$this->_controller->viewData['form_model'] = $this->viewData['form_model'] = $this->_controller->formFindModelObject($recordId);
				
				if ($this->controllerMethodExists('edit_formBeforeRender'))
					$this->_controller->edit_formBeforeRender($this->viewData['form_model']);
			}
			catch (exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}
		}

		public function preview($recordId, $context = null)
		{
			try
			{
				$this->_context = $context ? $context : 'preview';

				$this->_controller->viewData['form_record_id'] = $recordId;
				$this->_controller->app_page_title = $this->_controller->form_preview_title;
				$this->_controller->viewData['form_model'] = $this->viewData['form_model'] = $this->_controller->formFindModelObject($recordId);
				
				if ($this->controllerMethodExists('preview_formBeforeRender'))
					$this->_controller->preview_formBeforeRender($this->viewData['form_model']);
			}
			catch (exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}
		}
		
		public function form_widget_request($method, $db_name, $param_1 = null, $param_2 = null)
		{
			if (substr($method, 0, 2) != 'on')
				die ('Invalid widget method name: '.$method);
			
			try
			{
				$data_model = $this->_controller->formCreateModelObject();

				$widget = $this->formInitWidget($db_name, $data_model);
				$widget->$method($param_1, $param_2);
			}
			catch (exception $ex) 
			{
				die ($ex->getMessage());
			}
		}

		/**
		 *
		 * Public methods - you may call it from your views
		 *
		 */

		public function formGetFieldDbName($dbName, $model)
		{
			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");
			
			$columnDefinition = $field_definition->getColDefinition();
			if (!$columnDefinition->isReference)
				return $dbName;

			return $columnDefinition->referenceForeignKey;
		}

		/**
		 * Renders a specified form field
		 */
		public function formRenderField($dbName)
		{
			$model = $this->viewData['form_model'];
			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");
			
			$this->viewData['form_field'] = $field_definition;

			$fieldPartial = $this->form_preview_mode ? 'form_field_preview_'.$dbName : 'form_field_'.$dbName;

			if ($this->controllerPartialExists($fieldPartial))
				$this->renderPartial($fieldPartial);
			else
				$this->renderPartial('form_field');
		}
		
		public function formRenderFieldPartial($form_model, $form_field)
		{
			if ($form_field->formElementPartial)
				$this->_controller->renderPartial($form_field->formElementPartial);
			else
			{
				$controllerFieldPartial = $this->_controller->form_preview_mode ? 'form_field_element_preview_'.$form_field->dbName : 'form_field_element_'.$form_field->dbName;

				if ($this->controllerPartialExists($controllerFieldPartial))
					$this->renderPartial($controllerFieldPartial);
				else
					$this->formRenderFieldElementPartial($form_model, $form_field);
			}
		}
		
		public function formRenderFieldElementPartial($form_model, $form_field)
		{
		 	$renderMode = $this->formGetFieldRenderMode($form_field->dbName);
			$partialName = !$this->_controller->form_preview_mode ? 'form_field_'.$renderMode : 'form_field_preview_'.$renderMode;
			$this->formRenderPartial($partialName, array(
				'form_field'=>$form_field, 
				'form_model_class'=>get_class($form_model))); 
		}
		
		public function formRenderFieldContainer($model, $dbName)
		{
			$this->viewData['form_model'] = $model;
			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");
				
			$renderMode = $this->formGetFieldRenderMode($field_definition->dbName);
			$partialName = 'form_field_'.$renderMode;
			
			$this->formRenderPartial($partialName, array(
				'form_field'=>$field_definition,
				'form_model_class'=>get_class($model)));
		}
		
		/**
		 * Renders a set of fields
		 * @param mixed $fields Could be an array containing field names
		 * or 2 parameters - fromDbName and toDbName
		 */
		public function formRenderFields($param1, $param2 = null)
		{
		}
		
		/**
		 * Allows to register alternative view paths. Please use application root relative path.
		 */
		public function formRegisterViewPath($path)
		{
			$this->registerViewPath($path);
		}
		
		/**
		 * Renders the form.
		 * This method should be called on the Create or Edit pages. The FORM element and buttons should be defined 
		 * separately. The buttons should trigger events with names corresponding the action name - <em>create_</em>onSave, <em>edit_</em>onSave, etc.
		 * The event handlers are defined in the extension class.
		 * Example of the Create form:
		 * <pre>
		 * <?= Phpr_Form::openTag() ?>
		 *   <? $this->formRender() ?>
		 * 
		 *   <?= backend_ajax_button('Create', 'create_onSave', array('class'=>'default')) ?>
		 *   <?= backend_ajax_button('Cancel', 'create_onCancel') ?>
		 * </form>
		 * </pre>
		 * Example of the Edit form:
		 * <pre>
		 * <?= Phpr_Form::openTag(array('id'=>'form_element')) ?>
		 *   <? $this->formRender() ?>
		 * 
		 *   <?= backend_ajax_button('Save', 'edit_onSave', array('class'=>'default')) ?>
		 *   <?= backend_ajax_button('Cancel', 'edit_onCancel') ?>
		 *   <?= backend_ajax_button('Delete', 'edit_onDelete', array('class'=>"right"), "confirm: 'Do you really want to delete this record?'") ?>
		 * 
		 *   <div class="clear"></div>
		 * </form>
		 * </pre>
		 * 
		 * @documentable 
		 * @param Db_ActiveRecord $model Optional model object to override the model class specified in the {@link Db_FormBehavior::$form_model_class $form_model_class} property.
		 */
		public function formRender($model = null)
		{
			if ($model !== null)
			{
				$this->_controller->viewData['form_model'] = $this->viewData['form_model'] = $model;
				$this->_controller->viewData['form_record_id'] = $model->get_primary_key_value();
			}

			$lock = null;
			if (($model = $this->viewData['form_model']) && !$this->form_preview_mode)
			{
				if ($this->_controller->enable_concurrency_locking && !$model->is_new_record())
					$lock = Db_RecordLock::lock($this->viewData['form_model']);
			}
			
			$this->viewData['form_has_tabs'] = $this->hasTabs();
			$this->viewData['form_elements'] = $this->formSplitToTabs();
			$this->viewData['form_tabs_type'] = $this->_controller->form_tabs_type;
			$this->viewData['form_session_key'] = $this->formGetEditSessionKey();
			$this->viewData['form_lock_object'] = $lock;

			Backend::$events->fireEvent('core:onBeforeFormRender', $this->_controller, $model);

			$this->renderPartial('form_form');
		}
		
		public function formAddLockCode()
		{
			$this->renderPartial('form_lock');
		}

		/**
		 * Renders the form in Preview (read-only) mode.
		 * This method should be called on the Preview page. Example:
		 * <pre><? $this->formRenderPreview() ?></pre>
		 * @documentable 
		 * @param Db_ActiveRecord $model Optional model object to override the model class specified in the {@link Db_FormBehavior::$form_model_class $form_model_class} property.
		 */
		public function formRenderPreview($model = null)
		{
			if ($model)
				$this->_controller->form_model_class = get_class($model);
			
			$this->form_preview_mode = true;
			
			Backend::$events->fireEvent('core:onBeforeFormRenderPreview', $this->_controller, $model);

			$this->formRender($model);
		}
		
		public function formRenderReportPreview($model = null)
		{
			$this->form_report_layout_mode = true;
			$this->formRenderPreview($model);
		}

		public function formRenderPartial($view, $params = array())
		{
			$this->renderPartial($view, $params);
		}

		public function formGetFieldRenderMode($dbName, $model = null)
		{
			$model = $model ? $model : $this->viewData['form_model'];
			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");
			
			$columnDefinition = $field_definition->getColDefinition();

			if ($field_definition->renderMode === null)
			{
				if ($columnDefinition->isReference)
				{
					switch ($columnDefinition->referenceType)
					{
						case 'belongs_to' : return frm_dropdown;
					 	case 'has_and_belongs_to_many' : return frm_checkboxlist;
					}
				}
				
				switch ($columnDefinition->type)
				{
					case db_float:
					case db_number:
					case db_varchar: return frm_text;
					case db_bool: return frm_checkbox;
					case db_text: return frm_textarea;
					case db_datetime: return frm_datetime;
					case db_date: return frm_date;
					case db_time: return frm_time;
					default:
						throw new Phpr_SystemException("Render mode is unknown for $dbName field.");
				}
			}
			
			return $field_definition->renderMode;
		}

		public function formIsFieldRequired($dbName)
		{
			$realDbName = $this->formGetFieldDbName($dbName, $this->viewData['form_model']);
			
			$model = $this->viewData['form_model'];
			$rules = $model->validation->getRule($realDbName);
			if (!$rules)
				return false;

			return $rules->required;
		}

		public function formRenderFileAttachments($dbName, $model = null)
		{
			$model = $model ? $model : $this->viewData['form_model'];

			$field_definition = $model->find_form_field($dbName);
			$fileList = $model->list_related_records_deferred($dbName, $this->formGetEditSessionKey());

			$this->renderPartial('form_attached_file_list', array(
				'form_file_list'=>$fileList, 
				'dbName'=>$dbName,
				'render_mode'=>$field_definition->renderFilesAs,
				'form_field'=>$field_definition,
				'form_model'=>$model));
		}

		public function formGetElementId($prefix, $form_class = null)
		{
			return $this->formGetUniquePrefix().$prefix.$form_class;
		}
		
		public function formGetUniquePrefix()
		{
			$prefix = post('form_unique_prefix');
			if (!strlen($prefix))
				return $this->_controller->form_unique_prefix;
			else
				return $prefix;
		}
		
		public function formGetContext()
		{
			return post('form_context', $this->_context);
		}

		public function formGetUploadUrl($dbName, $sessionKey = null)
		{
			$model = $this->viewData['form_model'];
			
			$sessionKey = $sessionKey ? $sessionKey : $this->formGetEditSessionKey();
			
			$url = Backend_Html::controllerUrl();
			$url = substr($url, 0, -1);
			
			$parts = array(
				$url,
				'form_file_upload',
				Phpr::$security->getTicket(),
				$dbName,
				$sessionKey
			);
			
			if (!$model->is_new_record())
				$parts[] = $model->get_primary_key_value();
			
			return implode('/', $parts);
		}

		public function formGetEditSessionKey()
		{
			if ($this->_edit_session_key)
				return $this->_edit_session_key;
			
			if (post('edit_session_key'))
				return $this->_edit_session_key = post('edit_session_key');

			return $this->_edit_session_key = uniqid($this->_controller->form_model_class, true);
		}
		
		public function resetFormEditSessionKey()
		{
			return $this->_edit_session_key = uniqid($this->_controller->form_model_class, true);
		}

		public function formUpdateGridTable($model, $field)
		{
			$form_model_class = get_class($model);
			
			$field_definition = $model->find_form_field($field);
			if (!$field_definition)
				throw new Phpr_ApplicationException('Field not found');

			$form_field = $field_definition;
			$fieldId = $this->formGetElementId($form_model_class.'_'.$form_field->dbName);
			$table_container_id = $fieldId.'table_container';

			$this->_controller->preparePartialRender($table_container_id);
			$this->formRenderPartial('form_grid_table', array(
				'form_field'=>$form_field,
				'form_model_class'=>$form_model_class,
				'form_model'=>$model,
				'dbName'=>$field
			));
		}

		public function formSetViewDataElement($key, $value)
		{
			$this->viewData[$key] = $value;
		}

		/**
		 *
		 * Common methods - you may want to override or call it in the controller
		 *
		 */

		/**
		 * Returns a list of available options for fields rendered as dropdown, autocomplete and radio controls.
		 * You may override dynamic version of this method in the model like this:
		 * public function get_customerId_options()
		 * @param string $dbName Specifies a field database name
		 * @param mixed $model Specifies a model object
		 * @param mixed $keyValue Optional value of a key to find a specific option. If this parameter is not null, 
		 * the method should return exactly one value as array: [caption=>description] or as scalar: caption
		 * @return mixed For dropdowns: [id=>caption], for others: [id=>[caption=>description]]
		 */
		public function formFieldGetOptions($dbName, $model, $keyValue = -1)
		{
			/*
			 * Try to load data from a custom model method
			 */
			$methodName = 'get_'.$dbName.'_options';
			if (method_exists($model, $methodName))
				return $model->$methodName($keyValue);

			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");
			
			$optionsMethod = $field_definition->optionsMethod;
			if (strlen($optionsMethod))
			{
				if (!method_exists($model, $optionsMethod))
					throw new Phpr_SystemException("Method $optionsMethod is not found in the model class ".get_class($model));
					
				$result = $model->$optionsMethod($dbName, $keyValue);
				if ($result !== false)
					return $result;
			}

			/*
			 * Load options from reference table
			 */
			$columnDefinition = $field_definition->getColDefinition();
			if (!$columnDefinition->isReference)
				throw new Phpr_SystemException("Error loading options for $dbName field. Please define method $methodName in the model.");

			$has_primary_key = $has_foreign_key = false;
			$relationType = $model->has_models[$columnDefinition->relationName];
			$options = $model->get_relation_options($relationType, $columnDefinition->relationName, $has_primary_key, $has_foreign_key);

			if ($relationType == 'belongs_to' || $relationType == 'has_and_belongs_to_many')
			{
				$object = new $options['class_name']();

				$nameExpr = str_replace('@', $object->table_name.'.', $columnDefinition->referenceValueExpr);
				
				$object->calculated_columns['_name_calc_column'] = array('sql'=>$nameExpr);

				if ($field_definition->referenceDescriptionField !== null)
					$object->calculated_columns['_description_calc_column'] = array('sql'=>str_replace('@', $object->table_name.'.', $field_definition->referenceDescriptionField));
				
				if ($field_definition->referenceFilter !== null)
					$object->where($field_definition->referenceFilter);
					
				$sortingField = $field_definition->referenceSort !== null ? $field_definition->referenceSort : '1 asc';
				$object->order($sortingField);

				$object->where($options['conditions']);

				$result = array();
				
				if ($keyValue !== -1)
				{
					$assignedValues = array();
					if ($keyValue instanceof Db_DataCollection)
					{
						if (!$keyValue->count())
							return array();
						
						foreach ($keyValue as $assignedRecord)
							$assignedValues[] = $assignedRecord->get_primary_key_value();
							
						$assignedValues = array($assignedValues);
					} else
						$assignedValues[] = $keyValue;

					$records = $object->find_all($assignedValues);
				}
				else
				{
					if (!$object->isExtendedWith('Db_Act_As_Tree'))
						$records = $object->find_all();
					else
					{
						$records = array();
						$this->fetchTreeItems($object, $records, $sortingField, 0);
					}
				}

				$isTree = $object->isExtendedWith('Db_Act_As_Tree');

				foreach ($records as $record)
				{
					if ($field_definition->referenceDescriptionField === null)
					{
						if (!$isTree)
							$result[$record->get_primary_key_value()] = $record->_name_calc_column;
						else
							$result[$record->get_primary_key_value()] = array($record->_name_calc_column, null, $record->act_as_tree_level, 'level'=>$record->act_as_tree_level);
					}
					else
					{
						$option = array();
						$option[$record->_name_calc_column] = $record->_description_calc_column;
						if ($isTree)
							$option['level'] = $record->act_as_tree_level;

						$result[$record->get_primary_key_value()] = $option;
					}
				}

				if ($keyValue !== -1 && count($result) && $relationType == 'belongs_to')
				{
					$keys = array_keys($result);
					return $result[$keys[0]];
				}

				return $result;
			}

			return array();
		}

		/**
		 * Returns true for options what are exist in many-to-many relation. 
		 * For checkbox list many-to-many relations this method returns true if 
		 * a checkbox with a specified value should be checked.
		 * You may override dynamic version of this method in the model like this:
		 * public function get_user_rights_option_state($value)
		 * @param string $dbName Specifies a field database name
		 * @param mixed $value Specifies a current checkbox value to check against
		 * @param mixed $model Specifies a model object
		 * @return bool
		 */
		public function formOptionState($dbName, $value, $model)
		{
			$field_definition = $model->find_form_field($dbName);
			if (!$field_definition)
				throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");

			/*
			 * Try to load data from a dynamic model method
			 */
			$methodName = 'get_'.$dbName.'_optionState';
			if (method_exists($model, $methodName))
				return $model->$methodName($value);

			$methodName = 'get_'.$dbName.'_option_state';
			if (method_exists($model, $methodName))
				return $model->$methodName($value);
				
			$optionStateMethod = $field_definition->optionStateMethod;
			if (strlen($optionStateMethod))
			{
				if (!method_exists($model, $optionStateMethod))
					throw new Phpr_SystemException("Method $optionStateMethod is not found in the model class ".get_class($model));
					
				return $model->$optionStateMethod($dbName, $value);
			}

			$columnDefinition = $field_definition->getColDefinition();
			if (!$columnDefinition->isReference || $columnDefinition->referenceType != 'has_and_belongs_to_many')
				throw new Phpr_SystemException("Error evaluating option state for $dbName field. Please define method $methodName in the model.");

			foreach ($model->$dbName as $record)
			{
				if ($record instanceof Db_ActiveRecord)
				{
					if ($record->get_primary_key_value() == $value)
						return true;
				} 
				elseif ($record == $value)
					return true;
			}

			return false;
		}

		/**
		 * Creates the form model object for the Create page.
		 * This method can be overridden in the controller class if a special
		 * actions should be performed in order to create the model. By default
		 * the method creates an object of class specified in the {@link Db_FormBehavior::$form_model_class $form_model_class} property.
		 * @documentable
		 * @return Db_ActiveRecord Returns the model object.
		 */
		public function formCreateModelObject()
		{
			$modelClass = $this->_controller->form_model_class;
			if (!strlen($modelClass))
				throw new Phpr_SystemException('Form behavior: model class is not specified. Please specify it in the controller class with form_model_class public field.');

			$obj = new $modelClass();
			$obj->init_columns_info();
			$obj->define_form_fields($this->formGetContext());

			return $obj;
		}
		
		/**
		 * Called before a new record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object to be saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formBeforeCreateSave($model, $session_key)
		{
			
		}

		/**
		 * Called after a new record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formAfterCreateSave($model, $session_key)
		{
			
		}
		
		/**
		 * Called after a new or existing record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formAfterSave($model, $session_key)
		{
			
		}

		/**
		 * Called before an existing record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object to be saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formBeforeEditSave($model, $session_key)
		{
			
		}
		
		/**
		 * Called before a new or existing record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object to be saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formBeforeSave($model, $session_key)
		{
			
		}
		
		/**
		 * Called after an existing record is saved to the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object saved to the database.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formAfterEditSave($model, $session_key)
		{
			
		}
		
		/**
		 * Called after a record is deleted from the database.
		 * This method can be overridden in the controller and used as an event handler.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a deleted model object.
		 * @param string $session_key Specifies the form session key.
		 */
		public function formAfterDelete($model, $session_key)
		{
			
		}
		
		/**
		 * Finds a model object for the Edit page.
		 * This method can be overridden in the controller class if a special
		 * actions should be performed in order to load a model from the database. By default
		 * the method creates an object of class specified in the {@link Db_FormBehavior::$form_model_class $form_model_class} property
		 * and then finds a record by the primary key value specified in the page URL.
		 * @documentable
		 * @param integer $record_id Specifies the primary key value.
		 * @return Db_ActiveRecord Returns the model object.
		 */
		public function formFindModelObject($recordId)
		{
			$modelClass = $this->_controller->form_model_class;
			if (!strlen($modelClass))
				throw new Phpr_SystemException('Form behavior: model class is not specified. Please specify it in the controller class with form_model_class public field.');

			if (!strlen($recordId))
				throw new Phpr_ApplicationException($this->_controller->form_not_found_message);

			$model = new $modelClass();

			$obj = $model->find($recordId, array(), $this->formGetContext());
			
			if (!$obj || !$obj->count())
				throw new Phpr_ApplicationException($this->_controller->form_not_found_message);

			$obj->define_form_fields($this->formGetContext());

			return $obj;
		}
		
		public function grid_get_csv($key)
		{
			try
			{
				$this->_controller->app_page_title = 'Download CSV File';
				
				if (!preg_match('/^[0-9]+$/', $key))
					throw new Phpr_ApplicationException('File not found');

				$tmp_obj_name = 'csvexp_'.mb_strtolower($this->_controller->form_model_class).'_'.$key.'.exp';
				$path = PATH_APP.'/temp/'.$tmp_obj_name;

				if (!file_exists($path))
					throw new Phpr_ApplicationException('File not found');
					
				$file = @file_get_contents($path);
				$contents = @unserialize($file);
				if (!$contents)
					throw new Phpr_ApplicationException('Invalid file format');

				header("Content-type: application/octet-stream");
				header('Content-Disposition: inline; filename="data.csv"');
				header('Cache-Control: no-store, no-cache, must-revalidate');
				header('Cache-Control: pre-check=0, post-check=0, max-age=0');
				header('Accept-Ranges: bytes');
				header('Content-Length: '.filesize($path));
				header("Connection: close");

				$data = $contents->data;
				$columns = $contents->columns;
				$iwork = $contents->iwork;
				$separator = $iwork ? ',' : ';';

				$titles = array();
				foreach ($columns as $column)
					$titles[] = isset($column['title']) ? $column['title'] : null;

				Phpr_Files::outputCsvRow($titles, $separator);

				foreach ($data as $row_index=>$values)
				{
					$row = array();
					foreach ($values as $column_id=>$value)
						$row[] = $value;

					Phpr_Files::outputCsvRow($row, $separator);
				}

				@unlink($path);
			
				$this->_controller->suppressView();
			}
			catch (Exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}
		}
		/**
		 * Adds unchecked checkbox values to the $_POST array
		 */
		public function formRecoverCheckboxes($model)
		{
			$modelClass = get_class($model);
			$postData = post($modelClass, array());

			foreach ($model->form_elements as $form_element)
			{
				if (!($form_element instanceof Db_FormFieldDefinition))
					continue;

				$dbName = $form_element->dbName;

				$renderMode = $this->formGetFieldRenderMode($dbName, $model);
				if ($renderMode == frm_checkbox)
					$_POST[$modelClass][$dbName] = array_key_exists($dbName, $postData) ? $postData[$dbName] : 0;
				elseif ($renderMode == frm_checkboxlist)
					$_POST[$modelClass][$dbName] = array_key_exists($dbName, $postData) ? $postData[$dbName] : array();
			}
		}

		/**
		 *
		 * Event handlers
		 *
		 */
		
		public function create_onSave()
		{
			try
			{
				$obj = $this->_controller->formCreateModelObject();
				$this->formRecoverCheckboxes($obj);

				$this->_controller->formBeforeSave($obj, $this->formGetEditSessionKey());
				$this->_controller->formBeforeCreateSave($obj, $this->formGetEditSessionKey());

				Backend::$events->fireEvent('core:onBeforeFormRecordCreate', $this->_controller, $obj);

				$obj->save(post($this->_controller->form_model_class, array()), $this->formGetEditSessionKey());

				Backend::$events->fireEvent('core:onAfterFormRecordCreate', $this->_controller, $obj);

				$this->_controller->formAfterCreateSave($obj, $this->formGetEditSessionKey());
				$this->_controller->formAfterSave($obj, $this->formGetEditSessionKey());

				if ($this->_controller->form_create_save_flash)
					Phpr::$session->flash['success'] = $this->_controller->form_create_save_flash;

				$redirectUrl = Phpr_Util::any($this->_controller->form_create_save_redirect, $this->_controller->form_redirect);

				if ($redirectUrl)
				{
					if (strpos($redirectUrl, '%s') !== false)
						$redirectUrl = sprintf($redirectUrl, $obj->get_primary_key_value());
					
					Phpr::$response->redirect($redirectUrl);
				}
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		public function edit_onSave($recordId = null)
		{
			try
			{
				$obj = $this->_controller->formFindModelObject($recordId);

				if ($this->_controller->enable_concurrency_locking && ($lock = Db_RecordLock::lock_exists($obj)))
					throw new Phpr_ApplicationException(sprintf('User %s is editing this record. The edit session started %s. You cannot save changes.', $lock->created_user_name, $lock->get_age_str()));
				
				$this->formRecoverCheckboxes($obj);

				$this->_controller->formBeforeSave($obj, $this->formGetEditSessionKey());
				$this->_controller->formBeforeEditSave($obj, $this->formGetEditSessionKey());

				Backend::$events->fireEvent('core:onBeforeFormRecordUpdate', $this->_controller, $obj);

				$flash_set = false;
				$obj->save(post($this->_controller->form_model_class, array()), $this->formGetEditSessionKey());

				Backend::$events->fireEvent('core:onAfterFormRecordUpdate', $this->_controller, $obj);
				
				$this->_controller->formAfterSave($obj, $this->formGetEditSessionKey());

				if ($this->_controller->form_edit_save_flash)
				{
					Phpr::$session->flash['success'] = $this->_controller->form_edit_save_flash;
					$flash_set = true;;
				}
					
				if (post('redirect', 1))
				{
					$redirectUrl = Phpr_Util::any($this->_controller->form_edit_save_redirect, $this->_controller->form_redirect);

					if (strpos($redirectUrl, '%s') !== false)
						$redirectUrl = sprintf($redirectUrl, $recordId);
						
					if ($this->_controller->enable_concurrency_locking && !Db_RecordLock::lock_exists($obj))
						Db_RecordLock::unlock_record($obj);

					if ($redirectUrl)
						Phpr::$response->redirect($redirectUrl);
				} else
				{
					if ($flash_set && $this->_controller->form_edit_save_auto_timestamp)
						Phpr::$session->flash['success'] .= ' at '.Phpr_Date::display(Phpr_DateTime::now(), '%X');

					if ($this->_controller->formAfterEditSave($obj, $this->formGetEditSessionKey()))
						return;

					$this->renderPartial('form_flash');
				}
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		public function edit_onStealLock()
		{
			$recordId = post('record_id');
			
			if (strlen($recordId))
			{
				$obj = $this->_controller->formFindModelObject($recordId);
				if ($obj)
					Db_RecordLock::lock($obj);

				Phpr::$response->redirect(Phpr::$request->getReferer(post('url')));
			}
		}
		
		public function create_onCancel()
		{
			$obj = $this->_controller->formCreateModelObject();
			$obj->cancelDeferredBindings($this->formGetEditSessionKey());

			if (strpos($this->_controller->form_create_save_redirect, '%s') === false)
				$redirectUrl = Phpr_Util::any($this->_controller->form_create_save_redirect, $this->_controller->form_redirect);
			else
				$redirectUrl = $this->_controller->form_redirect;

			if ($redirectUrl)
				Phpr::$response->redirect($redirectUrl);
		}

		public function edit_onCancel($recordId = null)
		{
			$obj = $this->_controller->formFindModelObject($recordId);
			$obj->cancelDeferredBindings($this->formGetEditSessionKey());

			$redirectUrl = Phpr_Util::any($this->_controller->form_edit_save_redirect, $this->_controller->form_redirect);
			if (strpos($redirectUrl, '%s') !== false)
				$redirectUrl = sprintf($redirectUrl, $recordId);
				
			if ($redirectUrl)
			{
				if ($this->_controller->enable_concurrency_locking && !Db_RecordLock::lock_exists($obj))
					Db_RecordLock::unlock_record($obj);

				Phpr::$response->redirect($redirectUrl);
			}
		}
		
		public function edit_onDelete($recordId = null)
		{
			try
			{
				$obj = $this->_controller->formFindModelObject($recordId);
				if ($this->_controller->enable_concurrency_locking && ($lock = Db_RecordLock::lock_exists($obj)))
					throw new Phpr_ApplicationException(sprintf('User %s is editing this record. The edit session started %s. The record cannot be deleted.', $lock->created_user_name, $lock->get_age_str()));

				Backend::$events->fireEvent('core:onBeforeFormRecordDelete', $this->_controller, $obj);

				$obj->delete();

				if ($this->_controller->formAfterDelete($obj, $this->formGetEditSessionKey()))
					return;

				$obj->cancelDeferredBindings($this->formGetEditSessionKey());
				
				Backend::$events->fireEvent('core:onAfterFormRecordDelete', $this->_controller, $obj);

				if ($this->_controller->enable_concurrency_locking && !Db_RecordLock::lock_exists($obj))
					Db_RecordLock::unlock_record($obj);

				if ($this->_controller->form_edit_delete_flash)
					Phpr::$session->flash['success'] = $this->_controller->form_edit_delete_flash;

				$redirectUrl = Phpr_Util::any($this->_controller->form_delete_redirect, $this->_controller->form_edit_save_redirect);
				$redirectUrl = Phpr_Util::any($redirectUrl, $this->_controller->form_redirect);
				if (strpos($redirectUrl, '%s') !== false)
					$redirectUrl = sprintf($redirectUrl, $recordId);
				
				if ($redirectUrl)
					Phpr::$response->redirect($redirectUrl);
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		public function onUpdateFileList($recordId = null)
		{
			$model_class = post('phpr_uploader_model_class');

			if (!$model_class)
				$model = strlen($recordId) ? $this->_controller->formFindModelObject($recordId) : $this->_controller->formCreateModelObject();
			else
				$model = $this->create_custom_model($model_class, post('phpr_uploader_model_id'));

			$this->formRenderFileAttachments(post('dbName'), $model);
		}
		
		public function onSetFormFilesOrder($recordId = null)
		{
			Db_File::set_orders(post('item_ids'), post('sort_orders'));
		}
		
		public function onSaveFormFileDescription($record_id)
		{
			try
			{
				$file = Db_File::create()->find(post('file_id'));
				if ($file)
				{
					$file->description = trim(post('description'));
					$file->title = trim(post('title'));
					$file->save();
				}
				
				$this->renderPartial('form_file_description', array('file'=>$file));
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		public function onShowFileDescriptionForm($record_id)
		{
			try
			{
				$this->viewData['file'] = Db_File::create()->find(post('file_id'));
			}
			catch (Exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}

			$this->renderPartial('form_file_description_popup');
		}
		
		public function onDeleteFile($recordId = null)
		{
			$model_class = post('phpr_uploader_model_class');

			if (!$model_class)
				$model = strlen($recordId) ? $this->_controller->formFindModelObject($recordId) : $this->_controller->formCreateModelObject();
			else
				$model = $this->create_custom_model($model_class, post('phpr_uploader_model_id'));

			$dbName = post('dbName');
			
			if ($file = Db_File::create()->find(post('fileId')))
				$model->{$dbName}->delete($file, $this->formGetEditSessionKey());
				
			$this->formRenderFileAttachments($dbName, $model);
		}

		public function onPreviewPopup()
		{
			$modelClass = post('modelClass');
			if (!strlen($modelClass) || !class_exists($modelClass))
				throw new Phpr_SystemException("Model class not found: $modelClass");

			$modelObj = new $modelClass();
			$modelObj = $modelObj->find(post('modelId'));

			if ($modelObj)
				$modelObj->define_form_fields('preview');
			$this->viewData['trackTab'] = 0;
			$this->viewData['popupLevel'] = post('popupLevel');

			$this->renderPartial('form_preview_popup', array('modelObj'=>$modelObj, 'title'=>post('previewTitle')));
		}
		
		public function onFindFormRecord($recordId)
		{
			$title = 'Find Record';
			
			$custom_model_class = post('phpr_recordfinder_model_class');
			if (!$custom_model_class)
				$model = $this->_controller->formCreateModelObject();
			else 
				$model = $this->create_custom_model($custom_model_class, post('phpr_recordfinder_model_id'));
			
			$field_definition = null;
			$columnName = null;
			
			try
			{
				$db_name = post('db_name');
				$field_definition = $model->find_form_field($db_name);
				if (!$field_definition)
					throw new Phpr_ApplicationException('Field not found');

				$title = isset($field_definition->renderOptions['form_title']) ? $field_definition->renderOptions['form_title'] : 'Find Record';

				$columnName = $this->formGetFieldDbName($db_name, $model);
			}
			catch (exception $ex)
			{
				$this->_controller->handlePageError($ex);
			}

			$this->renderPartial('find_record_form', array('db_name'=>$db_name, 'model'=>$model, 'title'=>$title, 'formField'=>$field_definition, 'columnName'=>$columnName));
		}
		
		public function onSetRecordFinderRecord($recordId)
		{
			$custom_model_class = post('phpr_recordfinder_model_class');
			if (!$custom_model_class)
				$model = $this->_controller->formCreateModelObject();
			else 
				$model = $this->create_custom_model($custom_model_class, post('phpr_recordfinder_model_id'));
	
			$db_name = post('db_name');
			$field_definition = $model->find_form_field($db_name);
			if (!$field_definition)
				throw new Phpr_ApplicationException('Field not found');
				
			$field_name = $this->formGetFieldDbName($db_name, $model);

			$model->$field_name = post('recordId');

			$this->viewData['form_model'] = $model;
			
			$this->renderPartial('record_finder_record', array('db_name'=>$db_name, 'form_model'=>$model, 'form_field'=>$field_definition, 'form_model_class'=>get_class($model)));
		}
		
		public function onSaveFormGridCsv($record_id)
		{
			try
			{
				$this->cleanupGridExport();

				$custom_model_class = post('phpr_grid_model_class');

				if (!$custom_model_class)
					$model_class = $this->_controller->form_model_class;
				else
					$model_class = $custom_model_class;

				$model_data = post($model_class, array());
				if (!$model_data)
					throw new Phpr_ApplicationException('No data found');

				$db_name = post('dbName');
				$data = isset($model_data[$db_name]) ? $model_data[$db_name] : array();

				$is_manual_disabled = isset($data['disabled']);
				if ($is_manual_disabled)
					$data = unserialize($data['serialized']);

				if (!$custom_model_class)
					$model = Phpr::$router->action == 'create' ? $this->_controller->formCreateModelObject() : $this->_controller->formFindModelObject($record_id);
				else
					$model = $this->create_custom_model($model_class, null);
				
				$field_definition = $model->find_form_field($db_name);
				if (!$field_definition)
					throw new Phpr_ApplicationException('Field not found');

				$columns = $field_definition->gridColumns;
				$file_content = array('data'=>$data, 'columns'=>$columns, 'iwork'=>post('iwork'));
				$file_content = (object)$file_content;
				
				$key = time();
				$tmp_obj_name = 'csvexp_'.mb_strtolower($model_class).'_'.$key.'.exp';
				if (!@file_put_contents(PATH_APP.'/temp/'.$tmp_obj_name, serialize($file_content)))
					throw new Phpr_ApplicationException('Error creating data file');

				$export_url = post('phpr_grid_model_export_url', $this->_controller->form_grid_csv_export_url);

				Phpr::$response->redirect($export_url.'grid_get_csv/'.$key);
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		public function onLoadFormGridCsvPopup($record_id)
		{
			$this->_controller->form_unique_prefix = 'csv_grid_import';
			$this->_controller->form_model_class = 'Db_CsvFileImport';
			$model = $this->viewData['form_model'] = new Db_CsvFileImport();
			$model->init_columns_info();
			$model->define_form_fields();
			$session_key = $this->viewData['form_session_key'] = $this->_controller->formGetEditSessionKey();

			$files = $model->list_related_records_deferred('csv_file', $session_key);
			try
			{
				foreach ($files as $existing_file)
					$model->csv_file->delete($existing_file, $session_key);
			} catch (Exception $ex) {}

			$this->renderPartial('load_grid_csv_file');
		}
		
		public function onFormGridCsvUploaded($record_id)
		{
			try
			{
				$model = new Db_CsvFileImport();
				$files = $model->list_related_records_deferred('csv_file', $this->_controller->formGetEditSessionKey());
				if (!$files->count)
					throw new Phpr_ApplicationException('File is not uploaded');

				$file = PATH_APP.$files[0]->getPath();
				if (!file_exists($file))
					throw new Phpr_ApplicationException('Unable to open the uploaded file');

				$handle = null;

				try
				{
					/*
					 * Validate and parse the file
					 */

					$delimeter = Phpr_Files::determineCsvDelimeter($file);
					if (!$delimeter)
						throw new Phpr_ApplicationException('Unable to detect a delimiter');

					$handle = @fopen($file, "r");
					if (!$handle)
						throw new Phpr_ApplicationException('Unable to open the uploaded file');

					$file_data = array();
					$columns = array();
					$counter = 0;

					while (($data = fgetcsv($handle, 10000, $delimeter)) !== FALSE) 
					{
						if (Phpr_Files::csvRowIsEmpty($data))
							continue;

						$counter++;

						if ($counter == 1)
							$columns = $data;
						else
							$file_data[] = $data;
					}
					
					$custom_model_class = post('phpr_grid_model_class');

					if (!$custom_model_class)
						$model_class = $this->_controller->form_model_class;
					else
						$model_class = $custom_model_class;
						
					if (!$custom_model_class)
						$data_model = Phpr::$router->action == 'create' ? $this->_controller->formCreateModelObject() : $this->_controller->formFindModelObject($record_id);
					else
						$data_model = $this->create_custom_model($model_class, null);

					$grid_field = post('grid_field');
					$field_definition = $data_model->find_form_field($grid_field);
					if (!$field_definition)
						throw new Phpr_ApplicationException('Field not found');

					/*
					 * Generate column map and import data
					 */

					$grid_columns = $field_definition->gridColumns;

					$column_map = array();
					foreach ($grid_columns as $column_id=>$column_info)
					{
						$column_title = mb_strtoupper(trim($column_info['title']));
						foreach ($columns as $file_column_index=>$file_column)
						{
							$file_column = mb_strtoupper(trim($file_column));
							if ($file_column == $column_title)
								$column_map[$column_id] = $file_column_index;
						}
					}

					$fetched_column_data = array();
					foreach ($file_data as $data_row)
					{
						$fetched_data_row = array();
						
						foreach ($column_map as $column_id=>$column_index)
						{
							if (array_key_exists($column_index, $data_row))
								$fetched_data_row[$column_id] = $data_row[$column_index];
						}

						if (!Phpr_Files::csvRowIsEmpty($fetched_data_row))
							$fetched_column_data[] = $fetched_data_row;
					}

					$data_model->$grid_field = $fetched_column_data;

					/*
					 * Render the field
					 */

					$form_model_class = get_class($data_model);

					$_POST['form_unique_prefix'] = '';

					$form_field = $field_definition;
					$fieldId = $this->formGetElementId($form_model_class.'_'.$form_field->dbName);
					$table_container_id = $fieldId.'table_container';
					$this->_controller->preparePartialRender($table_container_id);
					$this->formRenderPartial('form_grid_table', array(
						'form_field'=>$form_field,
						'form_model_class'=>$form_model_class,
						'form_model'=>$data_model,
						'dbName'=>$grid_field,
					));
				} catch (Exception $ex)
				{
					if ($handle)
						@fclose($handle);

					throw $ex;
				}
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		public function onFormToggleCollapsableArea()
		{
			try 
			{
				$tab_index = post('collapsable_tab_index');
				$var_name = $this->getCollapsableVisibleStatusVarName($tab_index);
				Db_UserParameters::set($var_name, !post('current_expand_status'), true);
			} catch (exception $ex) {}
		}
		
		public function onFormWidgetEvent($record_id = null)
		{
			try
			{
				$custom_model_class = post('widget_model_class');

				if (!$custom_model_class)
					$model_class = $this->_controller->form_model_class;
				else
					$model_class = $custom_model_class;
				
				if (!$custom_model_class)
					$data_model = Phpr::$router->action == 'create' ? $this->_controller->formCreateModelObject() : $this->_controller->formFindModelObject($record_id);
				else
					$data_model = $this->create_custom_model($model_class, null);
					
				Backend::$events->fireEvent('core:onInitFormWidgetModel', $this->_controller, $data_model);

				$field = post('phpr_event_field');
				$widget = $this->formInitWidget($field, $data_model);
				$widget->handle_event(post('phpr_custom_event_name'), $data_model, $field);
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		/**
		 *
		 * Protected methods - used by the behavior
		 *
		 */

		protected function create_custom_model($class_name, $recordId)
		{
			$model = null;

			if (strlen($recordId))
			{
				$model = new $class_name();
				$model = $model->find($recordId);

				if (!$model || !$model->count())
					throw new Phpr_ApplicationException($this->_controller->form_not_found_message);

				$model->define_form_fields($this->formGetContext());
			} else
			{
				$model = new $class_name();
				$model->init_columns_info();
				$model->define_form_fields($this->formGetContext());
			}
			
			return $model;
		}

		public function form_file_upload($ticket, $dbName, $sessionKey, $recordId = null)
		{
			$this->_controller->suppressView();

			$result = array();
			try
			{
				if (!Phpr::$security->validateTicket($ticket, true))
					throw new Phpr_ApplicationException('Authorization error.');

				if (!array_key_exists('file', $_FILES))
					throw new Phpr_ApplicationException('File was not uploaded.');

				$model_class = post('phpr_uploader_model_class');

				if (!$model_class)
					$model = strlen($recordId) ? $this->_controller->formFindModelObject($recordId) : $this->_controller->formCreateModelObject();
				else
					$model = $this->create_custom_model($model_class, post('phpr_uploader_model_id'));

				$field_definition = $model->find_form_field($dbName);
				
				if (!$field_definition)
					throw new Phpr_SystemException("Field $dbName is not found in the model form field definition list.");

				$file = Db_File::create();
				$file->is_public = $field_definition->renderFilesAs == 'single_image' || $field_definition->renderFilesAs == 'image_list';
				
				$file->fromPost($_FILES['file']);
				$file->master_object_class = get_class($model);
				$file->field = $dbName;
				$file->save();

				if ($field_definition->renderFilesAs == 'single_image' || $field_definition->renderFilesAs == 'single_file')
				{
					$files = $model->list_related_records_deferred($dbName, $this->formGetEditSessionKey());
					foreach ($files as $existing_file)
						$model->{$dbName}->delete($existing_file, $sessionKey);
				}
					
				$model->{$dbName}->add($file, $sessionKey);

				$result['result'] = 'success';
			}
			catch (Exception $ex)
			{
				$result['result'] = 'failed';
				$result['error'] = $ex->getMessage();
				header("HTTP/1.1 500 " . $result['error']);
			}
			
			header('Content-type: application/json');
			echo json_encode($result);
		}

		protected function hasTabs()
		{
			$tabsFound = 0;
			$fieldsFound = 0;

			foreach ($this->viewData['form_model']->form_elements as $element)
			{
				if (strlen($element->tab))
					$tabsFound++;

				$fieldsFound++;
			}
			
			if ($tabsFound > 0 && ($tabsFound != $fieldsFound))
				throw new Phpr_SystemException('Form behavior: error in the model form elements definition. Tabs should be specified either for each form element or for none.');
				
			return $tabsFound;
		}
		
		public function formSplitToTabs($model = null)
		{
			$tabs = array();
			
			$model = $model ? $model : $this->viewData['form_model'];

			foreach ($model->form_elements as $index=>$element)
			{
				if (!$element->sortOrder)
					$element->sortOrder = ($index+1)*10;
			}

			usort($model->form_elements, array('Db_FormBehavior', 'formSortFormFields'));

			foreach ($model->form_elements as $element)
			{
				if (!$this->form_preview_mode && $element->noForm)
					continue;

				$tabCaption = $element->tab ? $element->tab : -1;
				if (!array_key_exists($tabCaption, $tabs))
					$tabs[$tabCaption] = array();
					
				$tabs[$tabCaption][] = $element;
			}
			
			return $tabs;
		}
		
		public static function formSortFormFields($element1, $element2)
		{
			if ($element1->sortOrder == $element2->sortOrder)
				return 0;
				
			return $element1->sortOrder > $element2->sortOrder ? 1 : -1;
		}
		
		public function formRenderFormTab($form_model, $tab_index)
		{
			$form_elements = $this->formSplitToTabs($form_model);
			$keys = array_keys($form_elements);
			if (!array_key_exists($tab_index, $keys))
				return;
				
			$this->viewData['form_model'] = $form_model;

			$this->formRenderPartial('form_tab', array('form_tab_elements'=>$form_elements[$keys[$tab_index]], 'tab_index'=>$tab_index));
		}
		
		protected function fetchTreeItems($object, &$records, $sortingField, $level)
		{
			if ($level == 0)
				$children = $object->list_root_children($sortingField);
			else
				$children = $object->list_children($sortingField);
			
			foreach ($children as $child)
			{
				$child->act_as_tree_level = $level;
				$records[] = $child;
				
				$this->fetchTreeItems($child, $records, $sortingField, $level+1);
			}
		}
		
		protected function cleanupGridExport()
		{
			$files = @glob(PATH_APP.'/temp/csvexp_*.exp');
			if (is_array($files) && $files)
			{
				foreach ($files as $filename) 
				{
					$matches = array();
					if (preg_match('/([0-9]+)\.exp$/', $filename, $matches))
					{
						if ((time()-$matches[1]) > 60)
							@unlink($filename);
					}
				}
			}
		}
		
		public function formGetRecordFinderListName($model)
		{
			return get_class($this->_controller).'_rflist_'.get_class($model);
		}
		
		public function formGetRecordFinderModel($masterModel, $fieldDefinition)
		{
			$columnDefinition = $fieldDefinition->getColDefinition();

			$has_primary_key = $has_foreign_key = false;
			$relationType = $masterModel->has_models[$columnDefinition->relationName];
			$options = $masterModel->get_relation_options($relationType, $columnDefinition->relationName, $has_primary_key, $has_foreign_key);
			
			$object = new $options['class_name']();

			if (isset($options['conditions']))
				$object->where($options['conditions']);

			return $object;
		}
		
		public function formPrepareRecordFinderData()
		{
			$custom_model_class = post('phpr_recordfinder_model_class');
			if (!$custom_model_class)
				$model = $this->_controller->formCreateModelObject();
			else 
				$model = $this->create_custom_model($custom_model_class, post('phpr_recordfinder_model_id'));
			
			$field_definition = null;
			$db_name = post('db_name');
			$field_definition = $model->find_form_field($db_name);
			if (!$field_definition)
				throw new Phpr_ApplicationException('Field not found');

			return $this->formGetRecordFinderModel($model, $field_definition);
		}
		
		public function formPrepareRecordFinderList($model = null, $field_definition = null)
		{
			if (!$model)
			{
				$custom_model_class = post('phpr_recordfinder_model_class');
				if (!$custom_model_class)
					$model = $this->formCreateModelObject();
				else 
					$model = $this->create_custom_model($custom_model_class, post('phpr_recordfinder_model_id'));
			}
			
//			$model = $model ? $model : $this->formCreateModelObject();
			
			$db_name = post('db_name');
			$field_definition = $field_definition ? $field_definition : $model->find_form_field($db_name);
			if (!$field_definition)
				throw new Phpr_ApplicationException('Field not found');
				
			$listColumns = isset($field_definition->renderOptions['list_columns']) ? $field_definition->renderOptions['list_columns'] : 'name';
			$listColumns = Phpr_Util::splat($listColumns, true);
			
			$searchFields = isset($field_definition->renderOptions['search_columns']) ? $field_definition->renderOptions['search_columns'] : null;
			$searchFields = $searchFields ? Phpr_Util::splat($searchFields, true) : $listColumns;
			$searchPrompt = isset($field_definition->renderOptions['search_prompt']) ? $field_definition->renderOptions['search_prompt'] : 'search';
			$customPrepareFunc = isset($field_definition->renderOptions['custom_prepare_func']) ? $field_definition->renderOptions['custom_prepare_func'] : 'formPrepareRecordFinderData';

			$this->_controller->list_name = $this->formGetRecordFinderListName($model);
			$searchModel = $this->formGetRecordFinderModel($model, $field_definition);

			$result = array(
				'list_model_class'=>get_class($searchModel),
				'list_no_setup_link'=>true,
				'list_columns'=>$listColumns,
				'list_custom_body_cells'=>false,
				'list_custom_head_cells'=>false,
				'list_render_as_tree'=>false,
				'list_scrollable'=>false,
				'list_search_fields'=>$searchFields,
				'list_search_prompt'=>$searchPrompt,
				'list_no_form'=>true,
				'list_record_url'=>null,
				'list_items_per_page'=>10,
				'list_search_enabled'=>true,
				'list_render_filters'=>false,
				'list_name'=>$this->formGetRecordFinderListName($model),
				'list_custom_prepare_func'=> $customPrepareFunc,
				'list_top_partial'=>null,
				'list_no_js_declarations'=>true,
				'list_record_onclick'=>'return recordFinderUpdateRecord(%s);'
			);
			
			$this->_controller->list_options = $result;
			$this->_controller->listApplyOptions($this->_controller->list_options);

			return $result;
		}
		
		public function formGetRecordFinderContainerId($modelClass, $dbName)
		{
			return 'recordfinderRecord'.$modelClass.$dbName;
		}

		public function formListCollapsableElements($elements)
		{
			$result = array();
			foreach ($elements as $element)
			{
				if ($element->collapsable)
					$result[] = $element;
			}
			
			return $result;
		}
		
		public function formListNonCollapsableElements($elements)
		{
			$result = array();
			foreach ($elements as $element)
			{
				if (!$element->collapsable)
					$result[] = $element;
			}
			
			return $result;
		}
		
		protected function getCollapsableVisibleStatusVarName($tab_index)
		{
			return get_class($this->_controller).'_'.Phpr::$router->action.'_collapsable_visible_'.$tab_index;
		}
		
		public function formIsCollapsableAreaVisible($tab_index)
		{
			if (Phpr::$router->action == 'create')
				return true;

			$var_name = $this->getCollapsableVisibleStatusVarName($tab_index);
			return Db_UserParameters::get($var_name, null, true);
		}
		
		protected function formLoadResources()
		{
			if (Phpr::$request->isRemoteEvent())
				return;
			
			$this->_controller->addCss('/phproad/modules/db/behaviors/db_formbehavior/resources/css/forms.css?'.module_build('core'));
			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/datepicker.js?'.module_build('core'));
			$this->_controller->addCss('/phproad/resources/css/datepicker.css?'.module_build('core'));

			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/Fx.ProgressBar.js?'.module_build('core'));
			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/Swiff.Uploader.js?'.module_build('core'));
			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/FancyUpload2.js?'.module_build('core'));
			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/fileuploader.js?'.module_build('core'));

			if (!$this->_controller->form_disable_ace)
			{
				$this->_controller->addJavaScript('/phproad/thirdpart/ace/ace.js?'.module_build('core'));
				$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/ace_wrapper.js?'.module_build('core'));
			}

			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/grid_control.js?'.module_build('core'));
			$this->_controller->addJavaScript('/phproad/modules/db/behaviors/db_formbehavior/resources/javascript/fileuploader.js?'.module_build('core'));

			$tiny_mce_src = Phpr::$config->get('DEV_MODE') ? 
				'/phproad/thirdpart/tiny_mce/tiny_mce_src.js?'.module_build('core') : 
				'/phproad/thirdpart/tiny_mce/tiny_mce.js?'.module_build('core');
			$this->_controller->addJavaScript($tiny_mce_src);
			
			$this->_controller->addJavaScript('/phproad/thirdpart/autocompleter/autocompleter.js?'.module_build('core'));
		}
		
		public function formInitWidget($db_name, $model = null)
		{
			if (array_key_exists($db_name, $this->_widgets))
				return $this->_widgets[$db_name];
				
			if (!$model)
				$model = isset($this->viewData['form_model']) ? $this->viewData['form_model'] : null;
				
			if (!$model)
				throw new Phpr_SystemException('Unable to initialize widget - form model object is not defined');
				
			$form_field = $model->find_form_field($db_name);
			if (!$form_field)
				throw new Phpr_ApplicationException('Field '.$db_name.' not found in the model '.get_class($model));
				
			$widget_class_name = isset($form_field->renderOptions['class']) ? $form_field->renderOptions['class'] : null;
			if (!$widget_class_name)
				throw new Phpr_SystemException('Widget class name is not specified for the '.$db_name.' field');
				
			$relation_db_name = $this->formGetFieldDbName($db_name, $model);
			return $this->_widgets[$db_name] = new $widget_class_name($this->_controller, $model, $relation_db_name, $form_field->renderOptions);
		}
		
		/*
		 * Event descriptions
		 */
		
		/**
		 * Triggered before a new model is saved to the database.
		 * The event is triggered only when a record is created with an Administration Area form.
		 * @event core:onBeforeFormRecordCreate
		 * @see core:onAfterFormRecordCreate
		 * @see core:onBeforeFormRecordUpdate
		 * @see core:onAfterFormRecordUpdate
		 * @see core:onBeforeFormRecordDelete
		 * @see core:onAfterFormRecordDelete
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object which is going to be saved to the database.
		 */
		private function event_onBeforeFormRecordCreate($controller, $record) {}
			
		/**
		 * Triggered after a new model is saved to the database.
		 * The event is triggered only when a record is created with an Administration Area form.
		 * @event core:onAfterFormRecordCreate
		 * @see core:onBeforeFormRecordCreate
		 * @see core:onBeforeFormRecordUpdate
		 * @see core:onAfterFormRecordUpdate
		 * @see core:onBeforeFormRecordDelete
		 * @see core:onAfterFormRecordDelete
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object which was saved to the database.
		 */
		private function event_onAfterFormRecordCreate($controller, $record) {}
			
		/**
		 * Triggered before an existing model is saved to the database.
		 * The event is triggered only when a record is updated with an Administration Area form.
		 * @event core:onBeforeFormRecordUpdate
		 * @see core:onBeforeFormRecordCreate
		 * @see core:onAfterFormRecordCreate
		 * @see core:onAfterFormRecordUpdate
		 * @see core:onBeforeFormRecordDelete
		 * @see core:onAfterFormRecordDelete
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object which is going to be saved to the database.
		 */
		private function event_onBeforeFormRecordUpdate($controller, $record) {}
			
		/**
		 * Triggered after an existing model is saved to the database.
		 * The event is triggered only when a record is updated with an Administration Area form.
		 * @event core:onAfterFormRecordUpdate
		 * @see core:onBeforeFormRecordCreate
		 * @see core:onAfterFormRecordCreate
		 * @see core:onBeforeFormRecordUpdate
		 * @see core:onBeforeFormRecordDelete
		 * @see core:onAfterFormRecordDelete
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object which was saved to the database.
		 */
		private function event_onAfterFormRecordUpdate($controller, $record) {}
			
		/**
		 * Triggered before an existing model is deleted from the database.
		 * The event is triggered only when a record is deleted with an Administration Area form.
		 * The event handler can throw an exception to cancel the record deletion.
		 * @event core:onBeforeFormRecordDelete
		 * @see core:onAfterFormRecordDelete
		 * @see core:onBeforeFormRecordCreate
		 * @see core:onAfterFormRecordCreate
		 * @see core:onBeforeFormRecordUpdate
		 * @see core:onAfterFormRecordUpdate
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object the form is going to be rendered for.
		 */
		private function event_onBeforeFormRecordDelete($controller, $record) {}

		/**
		 * Triggered after an existing model is deleted from the database.
		 * The event is triggered only when a record is deleted with an Administration Area form.
		 * @event core:onAfterFormRecordDelete
		 * @see core:onBeforeFormRecordDelete
		 * @see core:onBeforeFormRecordCreate
		 * @see core:onAfterFormRecordCreate
		 * @see core:onBeforeFormRecordUpdate
		 * @see core:onAfterFormRecordUpdate
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object representing the deleted record
		 */
		private function event_onAfterFormRecordDelete($controller, $record) {}
			
		/**
		 * Triggered before an Administration Area form is rendered.
		 * @event core:onBeforeFormRender
		 * @see core:onBeforeFormRenderPreview
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object the form is going to be rendered for.
		 */
		private function event_onBeforeFormRender($controller, $record) {}
			
		/**
		 * Triggered before an Administration Area preview (read-only) form is rendered.
		 * @event core:onBeforeFormRenderPreview
		 * @see core:onBeforeFormRender
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller Specifies the Administration Area controller object.
		 * @param Db_ActiveRecord $record Specifies the model object the form is going to be rendered for.
		 */
		private function event_onBeforeFormRenderPreview($controller, $record) {}
	}
?>