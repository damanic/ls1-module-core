<?

	/**
	 * This class helps in formatting XML documents with
	 * templates contained in files
	 */
	class Core_XmlController
	{
		/**
		 * Formats XML document
		 * @param string $template_name Specifies a name of XML document template
		 * The XML template must be placed in the directory with name matching
		 * the inherited class name. Example: class name is Shop_UpsShipping, 
		 * the directory is shop_upsshipping, in the same directory with the
		 * class file.  
		 * Do not add the XML header to the template. The method adds the header 
		 * automatically if the $add_header parameter value is true
		 * @param array $params Array of parameters to pass to the template
		 * @param bool $add_xml_header Add XML header in the beginning of the document
		 * @return Returns processed document as string
		 */
		public function format_xml_template($template_name, $params = array(), $add_xml_header = true)
		{
			$class_info = new ReflectionObject($this);
			$path = dirname($class_info->getFileName()).'/'.strtolower(get_class($this)).'/'.$template_name;
			if (!file_exists($path))
				throw new Phpr_SystemException('XML template not found: '.$template_name);
			
			extract($params);
			ob_start();
			try
			{
				include $path;
				$result = ob_get_clean();
				if ($add_xml_header)
					$result = '<?xml version="1.0" encoding="UTF-8"?>'."\n".$result;
					
				return $result;
			}
			catch (Exception $ex)
			{
				ob_end_clean();
				throw $ex;
			}
		}
	}

?>