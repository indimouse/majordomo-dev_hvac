<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='dev_hvac_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  include_once(DIR_MODULES.$this->name.'/hvac.class.php');

  if ($this->mode=='add_from_scan') {
   global $type;
   $rec['TYPE']=$type;
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
   global $ip;
   $rec['IP']=$ip;
   global $devtype;
   $rec['DEVTYPE']=$devtype;
   global $mac;
   $rec['MAC']=$mac;
   global $keys;
   $rec['KEYS']=$keys;
   global $updated_date;
   global $updated_minutes;
   global $updated_hours;
   $rec['UPDATED']=toDBDate($updated_date)." $updated_hours:$updated_minutes:00";

   }

  if ($this->mode=='update') {
   $ok=1;
   
   if ($this->tab=='') {
  //updating 'LANG_TITLE' (varchar, required)
   global $type;
   $rec['TYPE']=$type;
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;
  //updating 'DEVTYPE' (varchar)
   global $devtype;
   $rec['DEVTYPE']=$devtype;
  //updating 'MAC' (varchar)
   global $mac;
   $rec['MAC']=$mac;
  //updating 'KEYS' (varchar)
   global $keys;
   $rec['KEYS']=$keys;
  //updating 'CHTIME' (varchar)
   global $chtime;
   $rec['CHTIME']=$chtime;
  //updating 'LANG_UPDATED' (datetime)
   global $updated_date;
   global $updated_minutes;
   global $updated_hours;
   $rec['UPDATED']=toDBDate($updated_date)." $updated_hours:$updated_minutes:00";
   }
   
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  if ($rec['UPDATED']!='') {
   $tmp=explode(' ', $rec['UPDATED']);
   $out['UPDATED_DATE']=fromDBDate($tmp[0]);
   $tmp2=explode(':', $tmp[1]);
   $updated_hours=$tmp2[0];
   $updated_minutes=$tmp2[1];
  }
  for($i=0;$i<60;$i++) {
   $title=$i;
   if ($i<10) $title="0$i";
   if ($title==$updated_minutes) {
    $out['UPDATED_MINUTES'][]=array('TITLE'=>$title, 'SELECTED'=>1);
   } else {
    $out['UPDATED_MINUTES'][]=array('TITLE'=>$title);
   }
  }
  for($i=0;$i<24;$i++) {
   $title=$i;
   if ($i<10) $title="0$i";
   if ($title==$updated_hours) {
    $out['UPDATED_HOURS'][]=array('TITLE'=>$title, 'SELECTED'=>1);
   } else {
    $out['UPDATED_HOURS'][]=array('TITLE'=>$title);
   }
  }
  }
  
    if ($this->tab=='data'||$this->tab=='data_usage') {
		$this->getConfig();
   $new_id=0;
   
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM dev_hvac_commands WHERE ID='".(int)$delete_id."'");
   }
   global $sort_by_name;
   if ($sort_by_name) {
	   $properties=SQLSelect("SELECT * FROM dev_hvac_commands WHERE DEVICE_ID='".$rec['ID']."' ORDER BY TITLE");
   } else {
	   $properties=SQLSelect("SELECT * FROM dev_hvac_commands WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   }
   paging($properties, 20, $out);
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
		global $title_new;
		if ($title_new) {
		 $prop=array('TITLE'=>$title_new,'DEVICE_ID'=>$rec['ID']);
		 $new_id=SQLInsert('dev_hvac_commands',$prop);
		}
      global ${'title'.$properties[$i]['ID']};
      $properties[$i]['TITLE']=trim(${'title'.$properties[$i]['ID']});
      global ${'value'.$properties[$i]['ID']};
      $properties[$i]['VALUE']=trim(${'value'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      SQLUpdate('dev_hvac_commands', $properties[$i]);
      $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
     }
	if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
    }
	$properties[$i]['DEVTYPE']=$rec['TYPE'];
   }
   $out['PROPERTIES']=$properties;
  }

  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
