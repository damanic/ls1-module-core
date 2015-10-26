<?php

	/**
	 * Module registration class
	 */
	class Core_ModuleInfo
	{
		public $id;
		
		public $name;
		public $author;
		public $webpage;
		public $description;

		public function __construct($name, $description, $author, $webPage = null )
		{
			$this->name = $name;
			$this->author = $author;
			$this->description = $description;
			$this->webpage = $webPage;
		}
		
		public function getVersion()
		{
			return Core_Version::getModuleVersion($this->id);
		}
		
		public function getBuild()
		{
			return Core_Version::getModuleBuild($this->id);
		}
	}

?>