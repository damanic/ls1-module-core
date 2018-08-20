<?php

	/**
	 * Adds record list features to back-end controllers.
	 * This class allows to extend any back-end controller with a list functionality.
	 * It allows to render configurable record lists along with processing corresponding
	 * AJAX events.
	 * 
	 * The extension can be added to a controller by listing the class name in the <em>$implement</em> property of the controller class:
	 * <pre>
	 * class AbcBlog_Posts extends Backend_Controller
	 * {
	 *   public $implement = 'Db_ListBehavior, Db_FormBehavior';
	 *   ...
	 * </pre>
	 * The class works only with {@link Db_ActiveRecord} models. {@link Db_ActiveRecord::define_columns() Model columns} 
	 * should be defined in the model in order to be displayed in a list.
	 * 
	 * To configure the extension, its properties should be defined in the extended controller class. Only the {@link Db_ListBehavior::$list_model_class $list_model_class}
	 * property is required. Please read the {@link http://lemonstand.com/docs/administration_area_lists/ Administration Area Lists} article for the usage examples and details.
	 *
	 * @documentable
	 * @see http://lemonstand.com/docs/administration_area_lists/ Administration Area Lists
	 * @author LemonStand eCommerce Inc.
	 * @package core.classes
	 * @see Db_FormBehavior
	 * @see Db_FilterBehavior
	 */
	class Db_ListBehavior extends Phpr_ControllerBehavior
	{
		/**
		 * @var string A name of the model class.
		 * @documentable
		 */
		public $list_model_class = null;
		
		public $list_name = null;

		/**
		 * @var integer Number of records to display on a single page.
		 * @documentable
		 */
		public $list_items_per_page = 20;
		
		/**
		 * @var string A message to display in case if there are no records to display.
		 * The default value is <em>"There are no items in this view"</em>.
		 * @documentable
		 */
		public $list_no_data_message = 'There are no items in this view';
		
		public $list_load_indicator = 'phproad/resources/images/form_load_50x50.gif';
		
		/**
		 * @var string An URL to redirect the browser when a list record is clicked.
		 * Use the {@link url()} for creating back-end URLs. Example:
		 * <pre>$this->list_record_url = url('/blog/posts/preview/');</pre>
		 * @documentable
		 */
		public $list_record_url = null;

		/**
		 * @var string JavaScript code to execute when a record is clicked.
		 * @documentable
		 */
		public $list_record_onclick = null;
		
		/**
		 * @var boolean Determines whether the list should handle mouse clicks with JavaScript.
		 * By default this feature is enabled, making entire list table cells clickable.
		 * @documentable
		 */
		public $list_handle_row_click = true;
		
		/**
		 * @var boolean Determines whether the list should be rendered as a tree.
		 * This feature is supported only if the model class is extended with {@link Db_Act_As_Tree} extension.
		 * @documentable
		 */
		public $list_render_as_tree = false;
		public $list_render_as_sliding_list = false;
		
		public $list_root_level_label = 'Root';
		
		/**
		 * @var string Sets the list sorting column.
		 * If this property is set, its value overrides any sorting column preferences selected by a user.
		 * @documentable
		 */
		public $list_sorting_column = null;

		/**
		 * @var string Sets the list sorting direction (asc/desc)
		 * Determines the sorting direction of the list, is only used if {@link Db_ListBehavior::$list_sorting_column $list_sorting_column} property is also set.
		 * @documentable
		 */
		public $list_sorting_direction = null;

		/**
		 * @var string Sets the default list sorting column.
		 * If the user has not selected any custom sorting properties for the list, then this property will be used to sort it.
		 * @documentable
		 */
		public $list_default_sorting_column = null;

		/**
		 * @var string Sets the default list sorting direction (asc/desc)
		 * Only used if the user has not selected any custom sorting properties for the list, is only used
		 * if {@link Db_ListBehavior::$list_default_sorting_column $list_default_sorting_column} property is also set.
		 * @documentable
		 */
		public $list_default_sorting_direction = null;
		
		/**
		 * @var boolean Disables list sorting feature.
		 * By default lists can be sorted by clicking a column title.
		 * @documentable
		 */
		public $list_no_sorting = false;
		public $list_data_context = null;
		public $list_reuse_model = true;
		public $list_node_expanded_default = true;
		
		public $list_csv_import_url = '#';
		public $list_csv_cancel_url = '#';
		public $list_csv_template_url = '#';
		
		/**
		 * @var boolean Enables the list search feature.
		 * @documentable
		 */
		public $list_search_enabled = false;

		/**
		 * @var boolean Specifies a minimum search phrase length which triggers the search feature.
		 * This property takes effect only if {@link Db_ListBehavior::$list_search_enabled $list_search_enabled} property has TRUE value.
		 * @documentable
		 */
		public $list_min_search_query_length = 0;
		
		/**
		 * @var boolean Determines whether the list records should be displayed of no search query was provided.
		 * This property takes effect only if {@link Db_ListBehavior::$list_search_enabled $list_search_enabled} property has TRUE value.
		 * @documentable
		 */
		public $list_search_show_empty_query = true;
		
		/**
		 * @var string Specifies a prompt to display in the Search field when it has no value.
		 * This property takes effect only if {@link Db_ListBehavior::$list_search_enabled $list_search_enabled} property has TRUE value.
		 * @documentable
		 */
		public $list_search_prompt = null;
		
		/**
		 * @var array A list of the model database columns the search function should use for the data search.
		 * This property takes effect only if {@link Db_ListBehavior::$list_search_enabled $list_search_enabled} property has TRUE value.
		 * @documentable
		 */
		public $list_search_fields = array();
		
		public $list_search_custom_func = null;

		/**
		 * @var boolean Disables all interaction features of the list - sorting, search, record links, pagination, etc.
		 * @documentable
		 */
		public $list_no_interaction = false;
		public $list_no_js_declarations = false;
		public $list_no_form = false;
		
		/**
		 * @var boolean Hides the list configuration button.
		 * By default the list configuration button is displayed above the list on the right side of the page.
		 * @documentable
		 */
		public $list_no_setup_link = false;

		/**
		 * @var boolean Disables list pagination.
		 * @documentable
		 */
		public $list_no_pagination = false;
		public $list_scrollable = false;
		
		/**
		 * @var string Specifies a partial name or a path to a partial to display in each list row.
		 * The partial can contain extra list columns (TD elements).
		 * @documentable
		 */
		public $list_custom_body_cells = null;
		
		/**
		 * @var string Specifies a partial name or a path to a partial to display in the list header.
		 * The partial can contain extra list columns (TH elements).
		 * @documentable
		 */
		public $list_custom_head_cells = null;
		
		public $list_cell_partial = false;
		public $list_custom_partial = null;
		public $list_cell_individual_partial = array();
		public $list_top_partial = null;
		
		/**
		 * @var string Specifies a partial name or a path to a partial to use as the list control panel.
		 * The control panel partial should contain the toolbar element with buttons. Example of 
		 * the {@link Db_ListBehavior::listRender() listRender()} call:
		 * <pre><?= $this->listRender(array('list_control_panel'=>'control_panel')) ?></pre>
		 * Example of the control panel partial:
		 * <pre>
		 * <div class="toolbar">
		 *   <?= backend_ctr_button('Return to the page list', 'go_back', url('/cms/pages')) ?>
		 *   <div class="clear"></div>
		 * </div>
		 * </pre>
		 * @documentable
		 */
		public $list_control_panel = null;
		public $list_sidebar_panel = null;
		
		/**
		 * @var boolean Determines whether {@link Db_FilterBehavior filters} should be rendered by the list extension.
		 * @documentable
		 */
		public $list_render_filters = false;

		public $list_columns = array();
		public $list_options = array();

		/**
		 * @var boolean Determines whether partials should load from models native controller.
		 */
		public $list_ignore_native_controller = false;

		protected $_model_object = null;
		protected $_total_item_number = null;
		protected $_list_settings = null;
		protected $_list_columns = null;
		protected $_list_column_number = null;
		protected $_list_sorting_column = null;
		protected $_children_number_cache = null;
		protected $_model_primary_key_name = null;

		public function __construct($controller)
		{
			parent::__construct($controller);
			$this->hideAction('listPrepareData');
			$this->addEventHandler('onListColumnClick');
			$this->addEventHandler('onLoadListSetup');
			$this->addEventHandler('onApplyListSettings');

			$this->addEventHandler('onListPrevPage');
			$this->addEventHandler('onListNextPage');
			$this->addEventHandler('onListSetPage');
			$this->addEventHandler('onListToggleNode');
			$this->addEventHandler('onListReload');
			$this->addEventHandler('onListSearch');
			$this->addEventHandler('onListSearchCancel');
			$this->addEventHandler('onListGotoNode');
			
			if (!Phpr::$request->isRemoteEvent())
				$this->_controller->addCss('/phproad/modules/db/behaviors/db_listbehavior/resources/css/list.css?'.module_build('core'));
		}

		/**
		 *
		 * Public methods - you may call it from your views
		 *
		 */
		
		/**
		 * Renders the list.
		 * Use this method in a page view document to render the list
		 * with the parameters defined in the controller. Example:
		 * <pre><?= $this->listRender() ?></pre>
		 * @documentable
		 * @param array $options A associative array of options.
		 * The options allow to override list option properties defined in the controller.
		 * Note that option values specified in the method are not persistent and will not 
		 * be used in the list AJAX calls (sorting, search, etc.).
		 * @param string $partial A name of partial to use instead of the default list partial.
		 */
		public function listRender($options = array(), $partial = null)
		{
			$this->_model_object = null;
			$this->_total_item_number = null;
			$this->_list_settings = null;
			$this->_list_columns = null;
			$this->_list_column_number = null;
			$this->_list_sorting_column = null;

			$this->applyOptions($options);

			$this->prepareRenderData();

			if (!$partial)
				$this->renderPartial('list_container');
			else
				$this->renderPartial($partial);
		}

		public function listCellClass($columnDefinition)
		{
			$list_display_path_column = isset($this->viewData['list_display_path_column']) && $this->viewData['list_display_path_column'];
			$result = $columnDefinition->type;
			$result .= (!$list_display_path_column && $columnDefinition->index == $this->_list_column_number-1) ? ' last' : null;
			
			$sortingColumn = $this->_controller->listOverrideSortingColumn($this->evalSortingColumn());
			if ($sortingColumn->field == $columnDefinition->dbName)
			{
				$result .= ' current ';
				$result .= $sortingColumn->direction == 'asc' ? 'order_asc' : 'order_desc';
			}

			return $result;
		}

		public function listApplyOptions($options)
		{
			$this->applyOptions($options);
		}

		public function listGetName()
		{
			if ($this->_controller->list_name !== null)
				return $this->_controller->list_name;

			return get_class($this->_controller).'_'.Phpr::$router->action.'_list';
		}
		
		public function listGetFormId()
		{
			return 'listform'.$this->listGetName();
		}
		
		public function listGetPopupFormId()
		{
			return 'listform_popup'.$this->listGetName();
		}

		public function listGetContainerId()
		{
			return 'list'.$this->listGetName();
		}

		public function listGetElementId($element)
		{
			return $element.$this->listGetName();
		}

		public function listRenderPartial($view, $params=array(), $throwNotFound=true)
		{
			$model = $this->createModelObject();
			$controller_class = (isset($this->_controller->list_ignore_native_controller) && $this->_controller->list_ignore_native_controller) ? get_class($this->_controller) : $model->native_controller;
			$this->renderControllerPartial($controller_class, $view, $params, false, $throwNotFound);
		}

		public function listEvalTotalItemNumber()
		{
			if ($this->_total_item_number !== null)
				return $this->_total_item_number;
				
			$model = $this->loadData();
			if ($this->_controller->list_render_as_sliding_list)
				$this->configureSlidingListData($model);
			return $this->_total_item_number = $this->_controller->listGetTotalItemNumber($model);
		}
		
		public function listGetRecordChildrenCount($record)
		{
			if ($this->_children_number_cache === null)
			{
				$model = $this->createModelObject();
				$this->_model_primary_key_name = $model->primary_key;
				$parent_id = $record->{$model->act_as_tree_parent_key};
				
				$query = 'select c1.{primary_key_field} as id, count(c2.{primary_key_field}) as cnt
				from {table_name} c1
				left join {table_name} c2 on c2.{parent_field}=c1.{primary_key_field}
				where %s
				group by c1.{primary_key_field}';
				
				if (strlen($parent_id))
					$query = sprintf($query, 'c1.{parent_field} = :parent_id');
				else
					$query = sprintf($query, 'c1.{parent_field} is null');
				
				$cache_data = Db_DbHelper::queryArray(strtr($query , array(
					'{primary_key_field}'=>$model->primary_key,
					'{table_name}'=>$model->table_name,
					'{parent_field}'=>$model->act_as_tree_parent_key
				)), array('parent_id'=>$parent_id));

				$this->_children_number_cache = array();
				foreach ($cache_data as $cache_item)
					$this->_children_number_cache[$cache_item['id']] = $cache_item['cnt'];
			}
			
			$record_pk = $record->{$this->_model_primary_key_name};
			
			if (!array_key_exists($record_pk, $this->_children_number_cache))
				return 0;
				
			return $this->_children_number_cache[$record_pk];
		}
		
		public function listGetPrevLevelParentId($model, $current_parent_id)
		{
			return Db_DbHelper::scalar(strtr('select {parent_field} from {table_name} where {primary_key_field}=:parent_id', array(
				'{table_name}'=>$model->table_name,
				'{parent_field}'=>$model->act_as_tree_parent_key,
				'{primary_key_field}'=>$model->primary_key
			)), array('parent_id'=>$current_parent_id));
		}
		
		public function listGetNavigationParents($model, $current_parent_id)
		{
			if (!$current_parent_id)
				return array();
			
			$sql = 'select {primary_key_field} as id, {parent_field} as parent_id, {title_field} as title from {table_name} where {primary_key_field}=:id';
			$sql = strtr($sql, array(
				'{table_name}'=>$model->table_name,
				'{parent_field}'=>$model->act_as_tree_parent_key,
				'{primary_key_field}'=>$model->primary_key,
				'{title_field}'=>$model->act_as_tree_name_field
			));
			
			$result = array();
			while ($current_parent_id)
			{
				$obj = Db_DbHelper::object($sql, array('id'=>$current_parent_id));
				if (!$obj)
					break;
				$result[] = $obj;
				$current_parent_id = $obj->parent_id;
			}
			
			return array_reverse($result);
		}

		public function listRenderTable()
		{
			$this->renderTable();
		}
		
		public function listRenderCsvImport()
		{
			$completed = false;
			
			if (post('postback'))
			{
				try
				{
					Phpr_Files::validateUploadedFile($_FILES['file']);
					$fileInfo = $_FILES['file'];

					$pathInfo = pathinfo($fileInfo['name']);
					if (!isset($pathInfo['extension']) || strtolower($pathInfo['extension']) != 'csv')
						throw new Phpr_ApplicationException('Imported file is not a CSV file.');

					$filePath = null;
					try
					{
						if (!is_writable(PATH_APP.'/temp/'))
							throw new Phpr_SystemException('There is no writing permissions for the directory: '.PATH_APP.'/temp');

						$filePath = PATH_APP.'/temp/'.uniqid('csv');
						if (!move_uploaded_file($fileInfo['tmp_name'], $filePath))
							throw new Phpr_SystemException('Unable to copy the uploaded file to '.$filePath);
							
						$modelClass = $this->_controller->list_model_class;
						$model_object = new $modelClass();
						if (!$model_object->isExtendedWith('Core_ModelCsv'))
							throw new Phpr_SystemException("The mode class {$modelClass} should be extended wit the Core_ModelCsv extension.");

						$row = 0;
						$handle = fopen($filePath, "r");
						$errors = array();
						$success = 0;
						
						$delimeter = Phpr_Files::determineCsvDelimeter($filePath);
						if (!$delimeter)
							throw new Phpr_SystemException('Unable to detect the file type');
							
						$completed = true;

						while (($data = fgetcsv($handle, 1000, $delimeter)) !== FALSE) 
						{
							$row++;
							if ($row == 1 || !Phpr_Files::convertCsvEncoding($data))
								continue;

							$model_object = new $modelClass();
							try
							{
								$model_object->csv_import_record($data);
								$success++;
							} catch (Exception $import_exception)
							{
								$errors[$row] = $import_exception->getMessage();
							}
						}
						$this->viewData['errors'] = $errors;
						$this->viewData['success'] = $success;

						@unlink($filePath);
					}
					catch (Exception $ex)
					{
						if (strlen($filePath) && @file_exists($filePath))
							@unlink($filePath);

						throw $ex;
					}
				}
				catch (Exception $ex)
				{
					$this->viewData['form_error'] = $ex->getMessage();
				}
			}

			$this->viewData['completed'] = $completed;
			$this->renderPartial('list_import_csv');
		}

		/*
		* @param array $extendCsvCallback: array with two possible elements, both arrays where
		* the key is 'headerCallback' or 'rowCallback' and value an array with valid callback
		* example: array('headerCallback' => array('Shop_Order', 'export_orders_and_products_header'));
		*/
		public function listExportCsv($filename, $options = array(), $filterCallback = null, $noColumnInfoInit = false, $extendCsvCallback = array())
		{
			Backend::$events->fireEvent('core:onBeforeListExport', $this->_controller);

			$this->applyOptions($options);

			$data_model = $this->loadData();
			$column_defintions = $data_model->get_column_definitions($this->_controller->list_data_context);
			$sortingColumn = $this->_controller->listOverrideSortingColumn($this->evalSortingColumn());
			$sortingField = $column_defintions[$sortingColumn->field]->getSortingColumnName();

			$list_sort_column = $sortingField.' '.$sortingColumn->direction;
			$data_model->reset_order();
			$data_model->order($list_sort_column);

			$list_columns = $listColumns = $this->evalListColumns();

			$data_model->applyCalculatedColumns();
			$query = $data_model->build_sql();
			
			header("Expires: 0");
			header("Content-Type: Content-type: text/csv");
			header("Content-Description: File Transfer");
			header("Cache-control: private");
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header("Content-disposition: attachment; filename=$filename");

			$this->_controller->suppressView();

			$header = array();
			foreach ($list_columns as $column)
				$header[] = strlen($column->listTitle) ? $column->listTitle : $column->displayName;
				
			$iwork = array_key_exists('iwork', $options) ? $options['iwork'] : false;
			$separator = $iwork ? ',' : ';';
			
			if(array_key_exists('headerCallback', $extendCsvCallback))
				$header = call_user_func($extendCsvCallback['headerCallback'], $header);

			Phpr_Files::outputCsvRow($header, $separator);
			
			$disable_currency_formatting = Phpr::$config->get('DISABLE_CSV_CURRENCY_FORMATTING');

			$list_data = Db_DbHelper::queryArray($query);
			foreach ($list_data as $row_data)
			{
				$row = $data_model;
				$row->fill($row_data);
				
				if ($filterCallback)
				{
					if (!call_user_func($filterCallback, $row))
						continue;
				}
				
				$row_data = array();
				foreach ($list_columns as $index=>$column)
				{
					if (!$column->currency)
						$row_data[] = $row->displayField($column->dbName, 'list');
					else {
						if (!$disable_currency_formatting)
							$row_data[] = $row->displayField($column->dbName, 'list');
						else
							$row_data[] = $row->{$column->dbName};
					}
				}
				
				if(array_key_exists('rowCallback', $extendCsvCallback))
					call_user_func($extendCsvCallback['rowCallback'], $row, $row_data, $separator);
				else
					Phpr_Files::outputCsvRow($row_data, $separator);
			}
		}

		public function listCancelSearch()
		{
			Phpr::$session->set($this->listGetName().'_search', '');
		}
		
		public function listResetCache()
		{
			$this->_model_object = null;
			$this->_total_item_number = null;
			$this->_list_settings = null;
			$this->_list_columns = null;
			$this->_list_column_number = null;
			$this->_list_sorting_column = null;
		}
		
		/**
		 *
		 * Common methods - you may want to override them in the controller
		 *
		 */

		/**
		 * Prepares the list model. 
		 * This method can be overridden in the controller. By default the method
		 * creates an object of the class specified in the {@link Db_ListBehavior::$list_model_class $list_model_class} property.
		 * If you use the {@link Db_FilterBehavior Filter Behavior}, in this method you should call its
		 * {@link Db_FilterBehavior::filterApplyToModel() filterApplyToModel()} method. Example:
		 * <pre>
		 * public function listPrepareData()
		 * {
		 *   $obj = Shop_Order::create();
		 *   $this->filterApplyToModel($obj);
		 *   
		 *   return $obj;
		 * }
		 * </pre>
		 * @documentable
		 * @return Db_ActiveRecord Returns a configured model object.
		 */
		public function listPrepareData()
		{
			$obj = $this->createModelObject();
			return $obj;
		}
		
		/**
		 * Allows to apply additional configuration to the list model object.. 
		 * This method can be overridden in the controller. Inside the method
		 * you can call the model's {@link Db_ActiveRecord::where() where()} method
		 * to apply additional filters.
		 * @documentable
		 * @param Db_ActiveRecord A model object to configure.
		 * @return Db_ActiveRecord Returns a configured model object.
		 */
		public function listExtendModelObject($model)
		{
			return $model;
		}

		/**
		 * Returns a total number of items, not limited by a current page.
		 * @return int
		 */
		public function listGetTotalItemNumber($model)
		{
			return $model->requestRowCount();
		}

		public function listFormatRecordUrl($model)
		{
			$record_url = $this->_controller->list_record_url;
			
			if (!strlen($record_url))
			{
				if (!strlen($this->_controller->list_record_onclick))
					return null;
					
				return "#";
			}

			if (strpos($record_url, '%s'))
				return sprintf($record_url, $model->id);
			else
				return $record_url.$model->id;
		}

		public function listFormatRecordOnClick($model)
		{
			$onclick = $this->_controller->list_record_onclick;
			
			if (!strlen($onclick))
				return null;

			if (strpos($onclick, '%s'))
				return 'onclick="'.sprintf($onclick, $model->id).'"';
			else
				return 'onclick="'.$onclick.'"';
		}
		
		public function listFormatCellOnClick($model)
		{
			$onclick = $this->_controller->list_record_onclick;
			
			if (!strlen($onclick))
				return null;

			if (strpos($onclick, '%s'))
				return sprintf($onclick, $model->id);

			return $onclick;
		}

		public function listNodeIsExpanded($node)
		{
			return Db_UserParameters::get($this->listGetName().'_treenodestatus_'.$node->id, null, $this->_controller->list_node_expanded_default);
		}
		
		public function listResetPage()
		{
			Phpr::$session->set($this->listGetName().'_page', 0);
		}

		/**
		 * Returns a CSS class name for a list row.
		 * This method can be overridden in the controller if list rows require additional styling.
		 * Example:
		 * <pre>
		 * public function listGetRowClass($model)
		 * {
		 *   if ($model->status == -1)
		 *     return 'error';
		 * }
		 * </pre>
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies the model object being rendered in the list row.
		 * @return string Returns the CSS class name.
		 */
		public function listGetRowClass($model)
		{
			return null;
		}
		
		/**
		 * Executed before a list row is displayed.
		 * This method can be overridden in the controller.
		 * @documentable
		 * @param Db_ActiveRecord $model Specifies a model object to be rendered.
		 */
		public function listBeforeRenderRecord($model)
		{
		}

		public function listOverrideSortingColumn($sorting_column)
		{
			return $sorting_column;
		}

		/**
		 *
		 * Event handlers
		 *
		 */
		
		public function onListColumnClick()
		{
			$column = post('columnName');
			if (strlen($column))
			{
				$sortingColumn = $this->_controller->listOverrideSortingColumn($this->evalSortingColumn());
				if ($sortingColumn->field == $column)
					$sortingColumn->direction = $sortingColumn->direction == 'asc' ? 'desc' : 'asc';
				else
				{
					$sortingColumn->field = $column;
					$sortingColumn->direction = 'asc';
				}
				
				$sortingColumn = $this->_controller->listOverrideSortingColumn($sortingColumn);
				
				$this->saveSortingColumn($sortingColumn);
				$this->renderTable();
			}
		}
		
		public function onListNextPage()
		{
			try
			{
				$page = $this->evalPageNumber() + 1;
				$this->setPageNumber($page);
			
				$this->renderTable();
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		public function onListPrevPage()
		{
			try
			{
				$page = $this->evalPageNumber() - 1;
				$this->setPageNumber($page);

				$this->renderTable();
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}
		
		public function onListSetPage()
		{
			try
			{
				$this->setPageNumber(post('pageIndex'));

				$this->renderTable();
			}
			catch (Exception $ex)
			{
				Phpr::$response->ajaxReportException($ex, true, true);
			}
		}

		public function onLoadListSetup()
		{
			$listSettings = $this->loadListSettings();
			$this->viewData['columns'] = $this->evalListColumns(false);
			$this->viewData['visibleColumns'] = $listSettings['visible_list'];
			$this->viewData['invisibleColumns'] = $listSettings['invisible_list'];
			$this->viewData['list_load_indicator'] = $this->_controller->list_load_indicator;
			$this->viewData['records_per_page'] = $listSettings['records_per_page'];

			$this->renderPartial('list_settings_form');
		}
		
		public function onApplyListSettings()
		{
			$model = $this->createModelObject();
			$listSettings = $this->loadListSettings();

			/*
			 * Apply visible columns
			 */
			$visibleColumns = array_keys(post('list_visible_colums', array()));
			$listSettings['visible_list'] = $visibleColumns;
			
			/*
			 * Apply invisible columns
			 */
			$invisibleColumns = array();
			$definitions = $model->get_column_definitions($this->_controller->list_data_context);
			foreach ($definitions as $dnName=>$definition)
			{
				if (!in_array($dnName, $visibleColumns))
					$invisibleColumns[] = $dnName;
			}
			$listSettings['invisible_list'] = $invisibleColumns;

			/*
			 * Apply column order columns
			 */
			$listSettings['column_order'] = post('ordered_list', array());

			/*
			 * Apply records per page
			 */
			$listSettings['records_per_page'] = post('records_per_page', $this->_controller->list_items_per_page);

			$this->saveListSettings($listSettings);
			$this->renderTable();
		}

		public function onListToggleNode()
		{
			Db_UserParameters::set($this->listGetName().'_treenodestatus_'.post('nodeId'), post('status') ? 0 : 1);
			$this->renderTable();
		}
		
		public function onListGotoNode()
		{
			$this->setCurrentParentId(post('nodeId'));
			$this->setPageNumber(0);
			$this->renderTable();
		}
		
		public function onListReload()
		{
			$this->renderTable();
		}
		
		public function onListSearch()
		{
			$search_string = trim(post('search_string'));
			if ($this->_controller->list_min_search_query_length > 0 && mb_strlen($search_string) < $this->_controller->list_min_search_query_length)
				throw new Phpr_ApplicationException(sprintf('The entered search query is too short. Please enter at least %s symbols', $this->_controller->list_min_search_query_length));
			
			Phpr::$session->set($this->listGetName().'_search', $search_string);

			$this->renderTable();
		}
		
		public function onListSearchCancel()
		{
			Phpr::$session->set($this->listGetName().'_search', '');
			$this->renderTable();
		}

		/**
		 *
		 * Protected methods - used by the behavior
		 *
		 */

		/**
		 * Returns a list of list columns in correct order
		 */
		protected function evalListColumns($onlyVisible = true)
		{
			if ($this->_list_columns !== null && $onlyVisible)
				return $this->_list_columns;

			$model = $this->createModelObject();
			$model->init_columns_info('list_settings');
			$listSettings = $this->loadListSettings();

			$definitions = $model->get_column_definitions($this->_controller->list_data_context);
			if (!count($definitions))
				throw new Phpr_ApplicationException('Error rendering list: model columns are not defined.');

			$visibleFound = false;
			foreach ($definitions as $definition)
			{
				if ($definition->visible)
				{
					$visibleFound = true;
					break;
				}
			}
			if (!$visibleFound)
				throw new Phpr_ApplicationException('Error rendering list: there are no visible columns defined in the model.');

			if (count($this->_controller->list_columns))
				$orderedList = $this->_controller->list_columns;
			else
			{
				$columnList = array();

				/*
				 * Add visible columns
				 */
				foreach ($listSettings['visible_list'] as $columnName)
				{
					if (array_key_exists($columnName, $definitions) && $definitions[$columnName]->visible)
						$columnList[] = $columnName;
				}

				/*
				 * Add remaining columns if they are not invisible
				 */
				foreach ($definitions as $columnName=>$definition)
				{
					if (!in_array($columnName, $columnList) && (!in_array($columnName, $listSettings['invisible_list']) || !$onlyVisible) && $definition->visible
					&& (($onlyVisible && $definitions[$columnName]->defaultVisible) || !$onlyVisible))
						$columnList[] = $columnName ;
				}
			
				/*
				 * Apply column order
				 */
				$orderedList = array();
				if (!count($listSettings['column_order']))
					$listSettings['column_order'] = array_keys($definitions);
				
				foreach ($listSettings['column_order'] as $columnName)
				{
					if (in_array($columnName, $columnList))
						$orderedList[] = $columnName;
				}
			
				foreach ($columnList as $columnName)
				{
					if (!in_array($columnName, $orderedList))
						$orderedList[] = $columnName;
				}
			}

			$result = array();
			foreach ($orderedList as $index=>$columnName)
			{
				$definitionObj = $definitions[$columnName];
				$definitionObj->index = $index;
				$result[] = $definitionObj;
			}
			
			$this->_list_column_number = count($result);
			if ($onlyVisible)
				$this->_list_columns = $result;
				
			return $result;
		}

		protected function evalSortingColumn()
		{
			if (strlen($this->_controller->list_sorting_column))
			{
				$column = $this->_controller->list_sorting_column;

				$direction = $this->_controller->list_sorting_direction;
				if(strtoupper($direction) != 'ASC' && strtoupper($direction) != 'DESC')
					$direction = 'asc';
				return (object)(array('field'=>$column, 'direction'=>$direction));
			}
			
			if ($this->_list_sorting_column !== null)
				return $this->_list_sorting_column;
				
			$listColumns = $this->evalListColumns();
			$model = $this->createModelObject();
			$listSettings = $this->loadListSettings();
			$definitions = $model->get_column_definitions();

			if (strlen($listSettings['sorting']->field) && array_key_exists($listSettings['sorting']->field, $definitions) )
				return $listSettings['sorting'];

			if(strlen($this->_controller->list_default_sorting_column))
			{
				$column = $this->_controller->list_default_sorting_column;
				$direction = $this->_controller->list_sorting_direction;
				if(strtoupper($direction) != 'ASC' && strtoupper($direction) != 'DESC')
					$direction = 'asc';
				$this->_list_sorting_column = (object)(array('field'=>$column, 'direction'=>$direction));
				return $this->_list_sorting_column;
			}

			foreach ($definitions as $columnName=>$definition)
			{
				if ($definition->defaultOrder !== null)
					return (object)(array('field'=>$columnName, 'direction'=>$definition->defaultOrder));
			}

			if (!count($listColumns))
				return null;

			$columnNames = array_keys($listColumns);
			$firstColumn = $columnNames[0];

			$this->_list_sorting_column = (object)(array('field'=>$listColumns[$firstColumn]->dbName, 'direction'=>'asc'));
			return $this->_list_sorting_column;
		}
		
		protected function evalPageNumber()
		{
			return Phpr::$session->get($this->listGetName().'_page', 0);
		}
		
		protected function setPageNumber($page)
		{
			Phpr::$session->set($this->listGetName().'_page', $page);
		}
		
		protected function getCurrentParentId()
		{
			return Phpr::$session->get($this->listGetName().'_parent_id', null);
		}
		
		protected function setCurrentParentId($parent_id)
		{
			Phpr::$session->set($this->listGetName().'_parent_id', $parent_id);
		}
		
		protected function saveSortingColumn($sortingObj)
		{
			$listSettings = $this->loadListSettings();
			$listSettings['sorting'] = $sortingObj;
			$this->saveListSettings($listSettings);
		}

		protected function prepareRenderData($no_pagination = false, $noColumnInfoInit = false)
		{
			$form_context = $this->_controller->list_data_context;
			
			$this->viewData['list_columns'] = $listColumns = $this->evalListColumns();
			$this->viewData['list_sorting_column'] = $sortingColumn = $this->_controller->listOverrideSortingColumn($this->evalSortingColumn());
			$this->viewData['list_column_definitions'] = $this->createModelObject()->get_column_definitions();

			$model = $this->loadData();

			if ($this->_controller->list_render_as_sliding_list)
			{
				$current_parent_id = $this->viewData['list_current_parent_id'] = $this->configureSlidingListData($model);
				$this->viewData['list_upper_level_parent_id'] = $this->listGetPrevLevelParentId($model, $current_parent_id);
				$this->viewData['list_navigation_parents'] = $this->listGetNavigationParents($model, $current_parent_id);
			}
			
			$column_defintions = $model->get_column_definitions($form_context);
			$totalRowCount = $this->listEvalTotalItemNumber();
			
			if (!$no_pagination && 
				!$this->_controller->list_render_as_tree  && 
				!$this->_controller->list_no_interaction && 
				!$this->_controller->list_no_pagination)
			{
				$listSettings = $this->loadListSettings();
				
				$pagination = new Phpr_Pagination($listSettings['records_per_page']);
				$pagination->setRowCount($totalRowCount);

				$pagination->setCurrentPageIndex($this->evalPageNumber());
				$pagination->limitActiveRecord($model);

				$this->viewData['list_pagination'] = $pagination;
			}

			$sortingField = $column_defintions[$sortingColumn->field]->getSortingColumnName();

			$list_sort_column = $sortingField.' '.$sortingColumn->direction;
			$model->order($list_sort_column);

			$this->viewData['list_model_class'] = get_class($model);
			$this->viewData['list_total_row_count'] = $totalRowCount;

			if ($noColumnInfoInit)
			{
				global $activerecord_no_columns_info;
				$activerecord_no_columns_info = true;
			}
			
			if (!$this->_controller->list_render_as_tree)
			{
				if (!$this->_controller->list_reuse_model)
					$this->viewData['list_data'] = $model->find_all(null, array(), $form_context);
				else
				{
					$model->applyCalculatedColumns();
					$query = $model->build_sql();
					$this->viewData['list_data'] = Db_DbHelper::queryArray($query);
					$this->viewData['reusable_model'] = $model;
				}
			} else
			{
				$this->_controller->list_reuse_model = false;
 				$this->viewData['list_data'] = $model->list_root_children($list_sort_column);
			}
			
			if ($noColumnInfoInit)
				$activerecord_no_columns_info = false;

			$this->viewData['list_no_data_message'] = $this->_controller->list_no_data_message;
			$this->viewData['list_sort_column'] = $list_sort_column;

			$this->viewData['list_column_count'] = count($listColumns);
			$this->viewData['list_load_indicator'] = $this->_controller->list_load_indicator;
			$this->viewData['list_tree_level'] = 0;
			$this->viewData['list_search_string'] = Phpr::$session->get($this->listGetName().'_search');
		}
		
		protected function configureSlidingListData($model)
		{
			$current_parent_id = $this->getCurrentParentId();
			if ($current_parent_id === null || !strlen($current_parent_id))
			{
				$model->where($model->act_as_tree_parent_key.' is null');
				return null;
			}
			else
			{
				$parent_exists = Db_DbHelper::scalar('select count(*) from `'.$model->table_name.'` where `'.$model->primary_key.'`=:id', array('id'=>$current_parent_id));

				if (!$parent_exists)
				{
					$model->where($model->act_as_tree_parent_key.' is null');
					return null;
				}
				else
				{
					$model->where($model->act_as_tree_parent_key.'=?', $current_parent_id);
					return $current_parent_id;
				}
			}
		}

		protected function renderTable()
		{
			$this->prepareRenderData();

			if (!$this->_controller->list_custom_partial)
				$this->renderPartial('list');
			else
				$this->renderPartial($this->_controller->list_custom_partial);
		}
		
		protected function loadListSettings()
		{
			if ($this->_list_settings === null)
			{
				$this->_list_settings = Db_UserParameters::get($this->listGetName().'_settings');

				if (!is_array($this->_list_settings))
					$this->_list_settings = array();
					
				if (!array_key_exists('visible_list', $this->_list_settings))
					$this->_list_settings['visible_list'] = array();
					
				if (!array_key_exists('invisible_list', $this->_list_settings))
					$this->_list_settings['invisible_list'] = array();
					
				if (!array_key_exists('column_order', $this->_list_settings))
					$this->_list_settings['column_order'] = array();
					
				if (!array_key_exists('sorting', $this->_list_settings))
					$this->_list_settings['sorting'] = (object)array('field'=>null, 'direction'=>null);
					
				if (!array_key_exists('records_per_page', $this->_list_settings))
					$this->_list_settings['records_per_page'] = $this->_controller->list_items_per_page;
			}
			
			return $this->_list_settings;
		}
		
		protected function saveListSettings($settings)
		{
			$this->_list_settings = $settings;
			Db_UserParameters::set($this->listGetName().'_settings', $settings);
		}

		protected function createModelObject()
		{
			if ($this->_model_object !== null)
				return $this->_model_object;

			if (!strlen($this->_controller->list_model_class))
				throw new Phpr_SystemException('Data model class is not specified for List Behavior. Use the list_model_class public field to set it.');
				
			$modelClass = $this->_controller->list_model_class;
			$result = $this->_model_object = new $modelClass();
			
			$result = $this->_controller->listExtendModelObject($result);
			
			return $result;
		}

		protected function applyOptions($options)
		{
			$this->_controller->list_options = $options;
			foreach ($options as $key=>$value)
				$this->_controller->$key = $value;
		}

		protected function loadData()
		{
			$model = null;

			if (strlen($this->_controller->list_custom_prepare_func))
			{
				$func = $this->_controller->list_custom_prepare_func;
				$model = $this->_controller->$func($this->createModelObject(), $this->_controller->list_options);
			} else
				$model = $this->_controller->listPrepareData();
			
			/*
			 * Apply search
			 */
			
			$search_string = Phpr::$session->get($this->listGetName().'_search');
			if ($this->_controller->list_search_enabled)
			{
				if (!$this->_controller->list_search_fields)
					throw new Phpr_ApplicationException('List search is enabled, but search fields are not specified in the list settings. Please use $list_search_fields public controller field to define an array of fields to search in.');
				
				if (!strlen($search_string) && !$this->_controller->list_search_show_empty_query)
				{
					$firstField = $this->_controller->list_search_fields[0];
					$model->where($firstField.' <> '.$firstField);
				} else
					if (strlen($search_string))
					{
						$this->_controller->list_render_as_tree = false;
						
						if ($this->_controller->list_render_as_sliding_list)
						{
							$this->viewData['list_display_path_column'] = true;
							$this->viewData['list_model_parent_field'] = $model->act_as_tree_parent_key;
						}
						
						$this->_controller->list_render_as_sliding_list = false;
						
						if (strlen($this->_controller->list_search_custom_func))
						{
							$func = $this->_controller->list_search_custom_func;
							$this->_controller->$func($model, $search_string);
						} else
						{
							$words = explode(' ', $search_string);
							$word_queries = array();
							$word_queries_int = array();
							foreach ($words as $word)
							{
								if (!strlen($word))
									continue;

								$word = trim(mb_strtolower($word));
								$word_queries[] = '%1$s like \'%2$s'.Db_DbHelper::escape($word).'%2$s\'';
								$word_queries_int[] = '%1$s = ((\''.Db_DbHelper::escape($word).'\')+0)';
							}

							$field_queries = array();
							foreach ($this->_controller->list_search_fields as $field)
							{
								$field_name = $field;
								
								$field = str_replace('@', $model->table_name.'.', $field);

								if ($field_name == 'id' || $field_name == '@id')
									$field_queries[] = '('.sprintf(implode(' and ', $word_queries_int), $field, '%').')';
								else
									$field_queries[] = '('.sprintf(implode(' and ', $word_queries), $field, '%').')';
							}

							$query = '('.implode(' or ', $field_queries).')';
							$model->where($query);
						}
					}
			}
			
			return $model;
		}
		
		/*
		 * Event descriptions
		 */
		
		/**
		 * Triggered before an Administration Area list is exported as a CSV file.
		 * @event core:onBeforeListExport
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller The Administration Area controller object.
		 */
		private function event_onBeforeListExport($controller) {}

		/**
		 * Triggered before an Administration Area list row is displayed.
		 * In the event handler you can update the model's properties.
		 * @event core:onBeforeListRecordDisplay
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller The Administration Area controller object.
		 * @param string $model_class Specifies class of a model being rendered by the list.
		 * @param Db_ActiveRecord $model Specifies a model object being rendered in the current list row.
		 */
		private function event_onBeforeListRecordDisplay($controller, $model_class, $model) {}
			
		/**
		 * Triggered after the Administration Area list table is displayed.
		 * @event core:onAfterRenderListTable
		 * @triggered /phproad/modules/db/behaviors/db_listbehavior/partials/_list.htm
		 * @see core:onAfterRenderListPagination
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller The Administration Area controller object.
		 */
		private function event_onAfterRenderListTable($controller) {}
			
		/**
		 * Triggered after the Administration Area list pagination is displayed.
		 * @event core:onAfterRenderListPagination
		 * @triggered /phproad/modules/db/behaviors/db_listbehavior/partials/_list.htm
		 * @see core:onAfterRenderListTable
		 * @package core.events
		 * @author LemonStand eCommerce Inc.
		 * @param Backend_Controller $controller The Administration Area controller object.
		 */
		private function event_onAfterRenderListPagination($controller) {}
	}

?>