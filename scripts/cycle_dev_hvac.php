<?php

chdir(dirname(__FILE__) . '/../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'dev_hvac/dev_hvac.class.php');
$br = new dev_hvac();
$br->getConfig();

$old_second = date('s');
$old_minute = date('i');
$old_hour = date('h');

$tmp = SQLSelectOne("SELECT ID FROM dev_hvac_devices LIMIT 1");
if (!$tmp['ID'])
   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check=0;
$checkEvery=5; // poll every 5 seconds

while (1)
{
   setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
   if ((time()-$latest_check)>$checkEvery) {
    $latest_check=time();
   }
	$s = date('s');
   	$m = date('i');
	$h = date('h');
	   if ($s != $old_second)
	   {
			$br->check_params('1s');
			if($s5>=5) {
				$br->check_params('5s');
				$s5=1;
			} else {
				$s5++;
			}
			if($s20>=20) {
				$br->check_params('20s');
				$s20=1;
			} else {
				$s20++;
			}
			$old_second = $s;
	   }	
	   if ($m != $old_minute)
	   {
			$br->check_params('1m');
			$old_minute = $m;
			if($m10>=10) {
				$br->check_params('10m');
				$m10=1;
			} else {
				$m10++;
			}
	   }

	   if ($h != $old_hour)
	   {
			$br->check_params('1h');
			$old_hour = $h;
	   }

	   if (file_exists('./reboot') || IsSet($_GET['onetime']))
	   {
		  $db->Disconnect();
		  exit;
	   }
   sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
