<?php

	class Core_Xml
	{
		/**
		 * Creates DOM element and adds it to the parent element
		 * @param mixed $document DOMDocument object
		 * @param mixed $parent DOMElement object
		 * @param string $name New element name
		 * @param string $value New element value
		 * @param boolean $cdata Create CDATA element
		 * @return returns added DOMElement object
		 */
		public static function create_dom_element($document, $parent, $name, $value = null, $cdata = false)
		{
			$cdata_value = $value;
			if ($cdata)
				$value = null;
			
			$element = $document->createElement($name, $value);
			$parent->appendChild($element);
			
			if ($cdata)
				return self::create_cdata($document, $element, $cdata_value);
			
			return $element;
		}
		
		/**
		 * Creates DOM CDATA section and adds it to the parent element
		 * @param mixed $document DOMDocument object
		 * @param mixed $parent DOMElement object
		 * @param string $name New element name
		 * @param string $value New element value
		 * @return returns added DOMElement object
		 */
		public static function create_cdata($document, $parent, $value)
		{
			$element = $document->createCDATASection($value);
			$parent->appendChild($element);
			
			return $element;
		}

		/**
		 * Returns plain array representation of an XML document
		 * @param mixed $document DOMDocument object
		 * @param boolean $use_parent_keys Use XML node tag names as array keys
		 * @return array Returns array
		 */
		public static function to_plain_array($document, $use_parent_keys = false)
		{
			$result = array();
			self::node_to_array($document, $result, '', $use_parent_keys);
			
			return $result;
		}
		
		protected static function node_to_array($node, &$result, $parent_path, $use_parent_keys)
		{
			foreach ($node->childNodes as $child)
			{
				if (!$use_parent_keys)
				{
					if (!($child instanceof DOMText))
						$node_path = $orig_path = $parent_path.'_'.$child->nodeName;
					else
						$node_path = $orig_path = $parent_path;
				} else
				{
					if (!($child instanceof DOMText))
						$node_path = $orig_path = $child->nodeName;
					else
						$node_path = $orig_path = $child->parentNode->nodeName;
				}

				$counter = 2;
				while (array_key_exists($node_path, $result))
				{
					$node_path = $orig_path.'_'.$counter;
					$counter++;
				}
				
				if (substr($node_path, 0, 1) == '_')
					$node_path = substr($node_path, 1);
				
				if ($child instanceof DOMCdataSection)
					$result[$node_path] = $child->wholeText;
				elseif ($child instanceof DOMText)
				{
					if (!($child->parentNode->childNodes->length > 1))
						$result[$node_path] = $child->wholeText;
				}
				else
					self::node_to_array($child, $result, $node_path, $use_parent_keys);
			}
		}
	}
	
?>