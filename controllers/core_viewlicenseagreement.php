<?

	class Core_ViewLicenseAgreement extends Backend_SettingsController
	{
		public $implement = 'Db_FormBehavior';
		
		public function index()
		{
			$this->app_tab = 'system';
			$this->app_page_title = 'License Agreement';

			try
			{
				$eula_info = Core_EulaInfo::get();
				$this->viewData['eula_info'] = $eula_info;
				if (!$eula_info->agreement_text)
					throw new Phpr_ApplicationException('License agreement not found');
					
				$this->viewData['accepted_user_name'] = $eula_info->get_accepted_user_name();
				Core_EulaInfo::mark_read($this->currentUser->id);
			}
			catch (exception $ex)
			{
				$this->handlePageError($ex);
			}
		}
	}

?>