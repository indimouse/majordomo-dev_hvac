<?php
include_once("hvac.class.php");

global $session;

if ($this->owner->name == 'panel') {
    $out['CONTROLPANEL'] = 1;
}

$qry = "1";
global $save_qry;
if ($save_qry) {
    $qry = $session->data['hvac_devices_qry'];
} else {
    $session->data['hvac_devices_qry'] = $qry;
}
if (!$qry) $qry = "1";

$res = Scan();
$out['RESULT'] = $res;
if ($res[0]['ID']) {
$current .= "in if $res[0]['ID']\n";
    $total = count($res);
    for ($i = 0; $i < $total; $i++) {
        $tmp = explode(' ', $res[$i]['UPDATED']);
        $res[$i]['UPDATED'] = fromDBDate($tmp[0]) . " " . $tmp[1];
    }
    $out['RESULT'] = $res;
}


function Scan()
{
$result = array();
$devices = hvac::Discover();

 
   foreach ($devices as $device) {
	$obj = array();
	$obj['DEVTYPE'] = $device->devtype();
	$obj['NAME'] = $device->name();
	$obj['MAC'] = $device->mac();
	$obj['HOST'] = $device->host();
	$obj['TYPE'] = $device->devmodel();
	array_push($result, $obj);
}

$devices = hvac::DiscoverGree();

   foreach ($devices as $device) {
	$obj = array();
	$obj['DEVTYPE'] = $device->devtype();
	$obj['NAME'] = $device->name();
	$obj['MAC'] = $device->macgree();
	$obj['HOST'] = $device->host();
	$obj['TYPE'] = $device->devmodel();
	$obj['KEYS'] = $device->keys();
	array_push($result, $obj);
}
    return $result;
}
