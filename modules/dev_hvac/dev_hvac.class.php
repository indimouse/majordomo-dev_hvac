<?php
/**
* HVAC 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 10:11:44 [Nov 30, 2018])
*/
//
//
class dev_hvac extends module {
/**
* dev_hvac
*
* Module class constructor
*
* @access private
*/
function dev_hvac() {
  $this->name="dev_hvac";
  $this->title="HVAC";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 if (IsSet($this->page)) {
  $p["page"]=$this->page;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;

  global $mode;
  global $mac;
  global $keys;
  global $host;
  global $title;
  global $devtype;
  global $type;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  global $title_new;

   if (isset($title)) {
   $this->title=$title;
  }
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($mac)) {
   $this->mac=$mac;
  }
  if (isset($keys)) {
   $this->keys=$keys;
  }
    if (isset($host)) {
   $this->host=$host;
  }
  if (isset($devtype)) {
   $this->devtype=$devtype;
  }
  if (isset($type)) {
   $this->type=$type;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
  if (isset($title_new)) {
   $this->title_new=$title_new;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();

 if ($this->view_mode=='update_settings') {
   $this->saveConfig();
   $this->redirect("?");
 }
 if ($this->mode=='check_params') {
	 $this->check_params();
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='dev_hvac_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_dev_hvac_devices') {
   $this->search_dev_hvac_devices($out);
  }
  if ($this->view_mode=='edit_dev_hvac_devices') {
   $this->edit_dev_hvac_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_dev_hvac_devices') {
   $this->delete_dev_hvac_devices($this->id);
   $this->redirect("?data_source=dev_hvac_devices");
  }
  if ($this->view_mode=='hvac_devices_scan') {
        $this->hvac_devices_scan($out);
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* dev_hvac_devices search
*
* @access public
*/
 function search_dev_hvac_devices(&$out) {
	require(DIR_MODULES.$this->name.'/dev_hvac_devices_search.inc.php');
 }
/**
* hvac_devices_scan search
*
* @access public
*/
 function hvac_devices_scan(&$out) {
    require(DIR_MODULES.$this->name.'/hvac_devices_scan.inc.php');
 }
/**
* dev_hvac_devices edit/add
*
* @access public
*/
 function edit_dev_hvac_devices(&$out, $id) {
	require(DIR_MODULES.$this->name.'/dev_hvac_devices_edit.inc.php');
 }
/**
* dev_hvac_devices delete record
*
* @access public
*/
 function delete_dev_hvac_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM dev_hvac_devices WHERE ID='$id'");
  SQLExec("DELETE FROM dev_hvac_devices WHERE ID='".$rec['ID']."'");
  SQLExec("DELETE FROM dev_hvac_commands WHERE DEVICE_ID='".$rec['ID']."'");
 }

 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
	include_once(DIR_MODULES.$this->name.'/hvac.class.php');
	$table='dev_hvac_commands';
	$properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
	$total=count($properties);
	if ($total) {
    for($i=0;$i<$total;$i++) {
		$id=$properties[$i]['DEVICE_ID'];
		$rec=SQLSelectOne("SELECT * FROM dev_hvac_devices WHERE ID='$id'");
		$rm = hvac::CreateDevice($rec['IP'], $rec['MAC'], $rec['DEVTYPE'], $rec['KEYS']);
		if ($rec['TYPE']=='CH') {
			if($properties[$i]['TITLE']=='temperature') {
					$rm->set_temp($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='power') {
					$rm->set_power($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='ac_mode') {
					$rm->set_ac_mode($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='fan_speed') {
					$rm->set_fan_speed($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='quiet') {
					$rm->set_quiet($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='fan_direction') {
					$rm->set_fan_direction($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='stepless_max') {
					$rm->set_stepless_max($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='light') {
					$rm->set_light($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='health') {
					$rm->set_health($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='sleep') {
					$rm->set_sleep($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='energy_save') {
					$rm->set_energy_save($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
		}
		if ($rec['TYPE']=='Gree') {
			if($properties[$i]['TITLE']=='health') {
					$rm->set_health($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='temperature') {
					$rm->set_temp($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='power') {
					$rm->set_power($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='ac_mode') {
					$rm->set_ac_mode($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='fan_speed') {
					$rm->set_fan_speed($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='quiet') {
					$rm->set_quiet($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='fan_direction') {
					$rm->set_fan_direction($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='light') {
					$rm->set_light($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='sleep') {
					$rm->set_sleep($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
			if($properties[$i]['TITLE']=='energy_save') {
					$rm->set_energy_save($value);
					$properties[$i]['VALUE']=$value;
					SQLUpdate('dev_hvac_commands', $properties[$i]);
			}
		}
    }
   }
 }

 function check_params($chtime = '') {

        $this->getConfig();
        if(isset($chtime) && $chtime!='all' && $chtime!='') {
                $db_rec=SQLSelect("SELECT * FROM dev_hvac_devices WHERE CHTIME='$chtime'");
        } elseif (isset($chtime) && $chtime!='all') {
                $db_rec=SQLSelect("SELECT * FROM dev_hvac_devices");
        } else {
                $db_rec=SQLSelect("SELECT * FROM dev_hvac_devices WHERE CHTIME<>'none'");
        }
                include_once(DIR_MODULES.$this->name.'/hvac.class.php');
                foreach ($db_rec as $rec) {
                        $response = '';
                        $rm = hvac::CreateDevice($rec['IP'], $rec['MAC'], $rec['DEVTYPE'], $rec['KEYS'] );
                        if(!is_null($rm)) {
                                if ($rec['TYPE']=='CH') {
                                        $response = $rm->get_status();
                                        foreach ($response as $key => $value) {
                                                $this->table_data_set($key, $rec['ID'], $value);
                                        }
                                }
                                if ($rec['TYPE']=='Gree') {
                                        $response = $rm->get_status();
                                        foreach ($response as $key => $value) {
                                                $this->table_data_set($key, $rec['ID'], $value);
                                        }
                                }
                                if(isset($response) && $response!='' && $response!=false && !empty($response)) {
                                        $rec['UPDATED']=date('Y-m-d H:i:s');
                                        SQLUpdate('dev_hvac_devices', $rec);
                                }
                        } else {
                                DebMes('Device '.$rec['TITLE'].' is not available');
                        }
        }

}
 
 function table_data_set($prop, $dev_id, $val, $sg_val = NULL) {
	$table='dev_hvac_commands';
	$properties=SQLSelectOne("SELECT * FROM $table WHERE TITLE='$prop' AND DEVICE_ID='$dev_id'");
	$total=count($properties);
	if ($total) {
		if ($val!=$properties['VALUE']) {
			$properties['VALUE']=$val;
			SQLUpdate($table, $properties);
			if(isset($properties['LINKED_OBJECT']) && $properties['LINKED_OBJECT']!='' && isset($properties['LINKED_PROPERTY']) && $properties['LINKED_PROPERTY']!='') {
				if(is_null($sg_val)) {
					sg($properties['LINKED_OBJECT'].'.'.$properties['LINKED_PROPERTY'], $val);
				} else {
					sg($properties['LINKED_OBJECT'].'.'.$properties['LINKED_PROPERTY'], $sg_val);
				}
			}
			
		}
	} else {
		$properties['VALUE']=$val;
		$properties['DEVICE_ID']=$dev_id;
		$properties['TITLE']=$prop;
		SQLInsert($table, $properties);								
	}
 }


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS dev_hvac_devices');
  SQLExec('DROP TABLE IF EXISTS dev_hvac_commands');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data='') {
/*
dev_hvac_devices - 
*/
  $data = <<<EOD
 dev_hvac_devices: ID int(10) unsigned NOT NULL auto_increment
 dev_hvac_devices: TYPE varchar(10) NOT NULL DEFAULT ''
 dev_hvac_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_hvac_devices: DEVTYPE varchar(10) NOT NULL DEFAULT ''
 dev_hvac_devices: IP varchar(20) NOT NULL DEFAULT ''
 dev_hvac_devices: MAC varchar(20) NOT NULL DEFAULT ''
 dev_hvac_devices: CHTIME varchar(10) NOT NULL DEFAULT ''
 dev_hvac_devices: KEYS varchar(128) NOT NULL DEFAULT ''
 dev_hvac_devices: UPDATED datetime
 dev_hvac_commands: ID int(10) unsigned NOT NULL auto_increment
 dev_hvac_commands: TITLE varchar(100) NOT NULL DEFAULT ''
 dev_hvac_commands: VALUE TEXT NOT NULL DEFAULT ''
 dev_hvac_commands: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 dev_hvac_commands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 dev_hvac_commands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 dev_hvac_commands: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgSnVuIDI4LCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
