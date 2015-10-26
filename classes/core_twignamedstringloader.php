<?php


	class Core_TwigNamedStringLoader implements Twig_LoaderInterface
	{
		public $source;
		
		/**
		 * Gets the source code of a template, given its name.
		 *
		 * @param  string $name The name of the template to load
		 *
		 * @return string The template source code
		 */
		public function getSource($name)
		{
			return $this->source;
		}

		/**
		 * Gets the cache key to use for the cache for a given template name.
		 *
		 * @param  string $name The name of the template to load
		 *
		 * @return string The cache key
		 */
		public function getCacheKey($name)
		{
			return $this->source;
		}

		/**
		 * Returns true if the template is still fresh.
		 *
		 * @param string	$name The template name
		 * @param timestamp $time The last modification time of the cached template
		 */
		public function isFresh($name, $time)
		{
			return true;
		}
	}

?>