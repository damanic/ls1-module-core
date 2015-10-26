<?php

	class Core_Twig
	{
		protected $environment = null;
		protected $loader = null;
		protected static $instance = null;
		
		public function __construct()
		{
			require_once PATH_APP.'/modules/core/thirdpart/Twig/Autoloader.php';
			Twig_Autoloader::register();
			
			$cache_dir = $this->get_cache_dir();
			
			$this->loader = new Core_TwigNamedStringLoader();
			$this->environment = new Twig_Environment($this->loader, array('cache'=>$cache_dir, 'auto_reload'=>true));
			$this->configure_environment();
		}
		
		protected function get_cache_dir()
		{
			$cache_dir = PATH_APP.'/temp/twig_cache';
			
			if (!file_exists($cache_dir) || !is_dir($cache_dir))
			{
				if (!@mkdir($cache_dir, Phpr_Files::getFolderPermissions()))
					throw new Phpr_ApplicationException('Error creating Twig cache directory (temp/twig_cache)');
			}

			return $cache_dir;
		}
		
		public function parse($string, $parameters, $object_name = 'Template')
		{
			$this->loader->source = $string;
			
			return $this->environment->render($object_name, $parameters);
		}
		
		protected function configure_environment()
		{
			$html_safe = array('is_safe' => array('html'));

			$this->environment->addFunction('resource_url', new Twig_Function_Function('resource_url'));
			$this->environment->addFunction('site_url', new Twig_Function_Function('site_url'));
			$this->environment->addFunction('open_form', new Twig_Function_Function('open_form', $html_safe));
			$this->environment->addFunction('close_form', new Twig_Function_Function('close_form', $html_safe));
			$this->environment->addFunction('root_url', new Twig_Function_Function('root_url', $html_safe));
			$this->environment->addFunction('traceLog', new Twig_Function_Function('traceLog'));
			$this->environment->addFunction('trace_log', new Twig_Function_Function('traceLog'));
			$this->environment->addFunction('format_currency', new Twig_Function_Function('format_currency'));
			$this->environment->addFunction('uniqid', new Twig_Function_Function('uniqid'));
			$this->environment->addFunction('post', new Twig_Function_Function('post'));
			$this->environment->addFunction('post_array_item', new Twig_Function_Function('post_array_item'));
			$this->environment->addFunction('zebra', new Twig_Function_Function('zebra', $html_safe));

			$core_extension = new Core_TwigExtension();
			$this->environment->addExtension($core_extension);

			$functions = $core_extension->getFunctions();
			foreach ($functions as $function)
				$this->environment->addFunction($function, new Twig_Function_Method($core_extension, $function, array('is_safe' => array('html'))));
		}
		
		public static function get()
		{
			if (self::$instance != null)
				return self::$instance;
				
			return self::$instance = new self();
		}
	}

?>