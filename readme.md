# Core Module

### Lemonstand Version 1
This updated module can be installed using the updatecenter module: https://github.com/damanic/ls1-module-updatecenter

#### Updates
- 1.12.0 Start community updates (github:damanic). Added events to update manager 'core:onAfterGetModuleVersions', 'core:onGetBlockedUpdateModules', 'core:onAfterRequestUpdateList'
- 1.12.1 Minor update to Core_UpdateManager: Added event core:onFetchSoftwareUpdateFiles. Updated PclZip to 2.8.2
- 1.12.2 Added the force parameter to update request events.
- 1.13.0 Added subscribe to crontab and add to cronjob support
- 1.13.1 Minor Bug Fix: sql now() being compared to localised PHP datetime causing cron timing issues.
- 1.13.2 Plugs Major Security Hole
- 1.13.3 Evocode Security Patch. Must read http://evocode.com/blog/lemonstand-v1-vulnerability/
- 1.13.4 Bug Fix: CURLOPT_HTTPHEADER set with underscores caused issue on some stacks
- 1.13.5 Improved crontab, prevent tasks overlapping if the task runs for longer than cron interval
- 1.13.6 Fire event added to execute_crontabs() `core:on_execute_cron_exception`
- 1.13.7 Added new fire event type 'update_result', behaves similar to type 'filter' (see: Phpr_Events::fire_event())
- 1.13.8 Updates mootools (fix: JSON.stringify.parse)
- 1.13.9 Cron Jobs can be re-inserted into the que when attempt fails (see Core_Cron::que_job())
- 1.13.10 Minor framework update for form file uploader
- 1.13.11 Minor fix
- 1.13.12 Support system settings display by user permission not just admin status.  See function: listSettingsItemsPermissible()
- 1.13.13 Minor framework update for list behaviour. Adds $list_ignore_native_controller
- 1.13.14 Minor framework update for dropdown fields.  Allow custom css classes on dropdowns. Eg. `$this->add_form_field()->renderAs(frm_dropdown)->cssClassName('my-custom-classname')`
- 1.13.15 Fix issue where backend actions are blocked when LS1 update/licence server is unresponsive
- 1.13.16 Skip lengthy update checks when ls1 update/licence servers are unresponsive.
- 1.13.17 Resolves some PHP error/warnings on 5.6+
- 1.13.18 IPV6 support
- 1.13.19 Update to caching API, adds method to delete cache keys
- 1.13.20 Minor framework update for Db_ModuleParameters
- 1.13.21 Update for PHPMailer
- 1.13.22 New parameter to Core_Cron::queue_job, handles duplicate job detection
- 1.13.23 Minor framework update adds get_query_string() function to phpr_request
- 1.13.24 Allows cron tab processes to be postponed until a given datetime has passed

More recent updates see release history: https://github.com/damanic/ls1-module-core/releases

### New Cron Features
#### Execute cron as a standalone
Example trigger to add to your system schedule: `/usr/local/bin/php -q /home/YOUR_USERNAME/public_html/modules/core/cron.php`
#### Subscribe a task to the cron tab
In any module add the function subscribe_crontab(). Eg.

```
class xCustom_Module{

	protected function createModuleInfo (){
		return new Core_ModuleInfo(
		'Module',
		'Example for you',
		'The dude');
	}

	public function subscribe_crontab(){
		return array(
			'update_products' => array('method'=>'update_products', 'interval'=>1440), // Every 24 hours
		);
	}
	
	public function update_products(){
		traceLog('I've been hit by cron interval');
	}
	
	public static function background_task($param1,$param2){
		traceLog('I've been hit by cron job');
	}
}
```

#### Add a job to a que so that it is processed in the background cron job process without delaying the currently executing script.
Anywhere in your code you can que a task and it will be executed as soon as possible. 

Example:

`Core_Cron::queue_job('xCustom_Module::background_task',array($param1, $param2 ));`

