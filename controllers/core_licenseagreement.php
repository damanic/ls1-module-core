<?

	class Core_LicenseAgreement extends Backend_Controller
	{
		public $implement = 'Db_FormBehavior';
		public $no_agreement_redirect = true;
		
		public function index()
		{
			Phpr::$response->redirect(url('/'));
		}
	}

?>