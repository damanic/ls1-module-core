<?
	/**
	 * A simple class for creating a required environment for the email view.
	 * This class acts like a controller object making possible to use the $this->ViewData
	 * construction inside a view body
	 */
	class Core_EmailViewWrapper
	{
		public $viewData;
		public $view;
		public $moduleId;

		public function __construct( $moduleId, $view, &$viewData )
		{
			$this->viewData = $viewData;
			$this->view = $view;
			$this->moduleId = $moduleId;
		}

		/**
		 * Loads an email view and returns its contents as string
		 * @return string
		 */
		public function execute()
		{
			$viewPath = PATH_APP."/modules/".strtolower($this->moduleId).'/mailviews/'.$this->view.'.htm';
			extract($this->viewData);

			ob_start();
			include($viewPath);
			return ob_get_clean();
		}
	}

?>