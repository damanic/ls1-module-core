<?

	class Core_ConfigUser
	{
		public $id = 0;
		
		public function findUser( $Login, $Password )
		{
			$framework = Phpr_SecurityFramework::create();

			$config_content = $framework->get_config_content();
			if (!array_key_exists('config_user', $config_content) || !array_key_exists('config_pwd', $config_content))
				return false;

			$match = strtolower($Login) == strtolower($config_content['config_user']) && $framework->salted_hash($Password) == $config_content['config_pwd'];
			
			if ($match)
				return new self();
				
			return null;
		}
		
		public function find($id)
		{
			if ($id == 0)
				return new self();
		}
	}

?>