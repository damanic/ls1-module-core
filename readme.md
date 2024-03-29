# Core Module

### Lemonstand V1
This updated module can be installed using the updatecenter module: https://github.com/damanic/ls1-module-updatecenter

#### Updates
See release history: https://github.com/damanic/ls1-module-core/releases

### Cron
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

