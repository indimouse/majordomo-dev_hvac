<?php

class hvac{
	protected $name; 
	protected $host;
	protected $mac;
	protected $keys = '';
	protected $timeout = 10;
	protected $count;
	protected $id = array(0, 0, 0, 0);
	protected $devtype;
	const DEFAULT_KEY = 'a3K8Bx%2r8Y7#xDh';

	function __construct($h = "", $m = "", $d = 0, $k = '') {

		$this->host = $h;
		$this->devtype = is_string($d) ? hexdec($d) : $d;
		$this->keys = $k;

		if(is_array($m)){

			$this->mac = $m;      		
		}
		else{

			$this->mac = array();
			$mac_str_array = explode(':', $m);

			foreach ( array_reverse($mac_str_array) as $value ) {
				array_push($this->mac, $value);
			}

		}

				
		$this->count = rand(0, 0xffff);

	}

	function __destruct() {
			
	}

	public static function CreateDevice($h = "", $m = "", $d = 0, $k = ''){

		switch (self::model($d)) {
			case 0:
				return new CH($h, $m, $d);
				break;
			case 1:
				return new Gree($h, $m, $d, $k);
				break;
			default:
		} 
		return NULL;
	}

	public function mac(){

		$mac = "";
		foreach ($this->mac as $value) {
			$mac = sprintf("%02x", $value) . ':' . $mac;
		}
		return substr($mac, 0, strlen($mac) - 1);
	}

	public function macgree(){

		$mac = "";
		    $value = $this->mac[0];
		    $mac = substr($value, 0, 2).":".substr($value, 2, 2).":".substr($value, 4, 2).":".substr($value, 6, 2).":".substr($value, 8, 2).":".substr($value, 10, 2);
		    return $mac;
	}

	public function host(){
		return $this->host;
	}

	public function name(){
		return $this->name;
	}

	public function keys(){
		return $this->keys;
	}

	public function devtype(){
		return sprintf("0x%x", $this->devtype);
	}

	public function devmodel(){
		return self::model($this->devtype, 'model');
	}
	
	public function model($devtype, $needle='type'){
		
		$type = "Unknown";
		$model = "Unknown";
		if (is_string($devtype)) $devtype = hexdec($devtype);
		
		switch ($devtype) {
			case 0x202:
				$model = "CH";
				$type = 0;
				break;
			case 0x2711:
				$model = "Gree";
				$type = 1;
				break;
			default:
				break;
		}

		if($needle=='model') {
			return $model;
		} else {
			return $type;
		}
    }

    protected static function bytearray($size){

		$packet = array();

		for($i = 0 ; $i < $size ; $i++){
			$packet[$i] = 0;
		}

		return $packet;
	}

	protected static function byte2array($data){

	    return array_merge(unpack('C*', $data));
	}

    protected static function byte($array){

	    return implode(array_map("chr", $array));
	}

	public static function Discover(){

		$devices = array();
		$cs = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

		if(!$cs){
			return $devices;
		}

		socket_set_option($cs, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_option($cs, SOL_SOCKET, SO_BROADCAST, 1);
		socket_set_option($cs, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
		socket_bind($cs, 0, 0);

		$packet = array(0xAA,0xAA,0x06,0x02,0xFF,0xFF,0xFF,0x00,0x59);

		socket_sendto($cs, self::byte($packet), sizeof($packet), 0, '255.255.255.255', 12414);
		while(socket_recvfrom($cs, $response, 2048, 0, $from, $port)){
                        $host = $from;
                        $responsepacket = self::byte2array($response);
                        $devtype = $responsepacket[0x5]|($responsepacket[0x6]<<8);
                        $mac = array_reverse(array_slice($responsepacket, 0x7, 6));
                        $host = substr($host, 0, strlen($host));

			$device = hvac::CreateDevice($host, $mac, $devtype);

			if($device != NULL){
				$device->name = str_replace(array("\0","\2"), '', hvac::byte(array_slice($responsepacket, 0x40)));
				array_push($devices, $device);
			}
		}

		@socket_shutdown($cs, 2);
		socket_close($cs);

		return $devices;
    }


    function send_packet($payload){

		$cs = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

	    if (!$cs) {
			return array();
		}
		
		socket_set_option($cs, SOL_SOCKET, SO_REUSEADDR, 1);
	        $crc = intval(substr(dechex(array_sum($payload)),1),16);
		array_push($payload, $crc);

		$packet = $payload;
		$starttime = time();
		$from = '';
		socket_connect($cs, $this->host, 12416);
		socket_send($cs, $this->byte($packet), sizeof($packet), 0);
		socket_set_option($cs, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$this->timeout, 'usec'=>0));

				$ret = socket_recv($cs, $response, 2048, 0);
		@socket_shutdown($cs, 2);
		socket_close($cs);
		
		$resp = array();
		$resp = self::byte2array($response);
		$crcresp = array_pop($resp);
	        $crc = intval(substr(dechex(array_sum($resp)),1),16);
                if ( $crcresp != $crc) {
			return array();
		}

		return $response;
	}

	protected static function str2hex_array($str){
		
		$str_arr = str_split(strToUpper($str), 2);
		$str_hex='';
		for ($i=0; $i < count($str_arr); $i++){
			$ord1 = ord($str_arr[$i][0])-48;
			$ord2 = ord($str_arr[$i][1])-48;
			if ($ord1 > 16) $ord1 = $ord1 - 7;
			if ($ord2 > 16) $ord2 = $ord2 - 7;
			$str_hex[$i] = $ord1 * 16 + $ord2;
		}
		return $str_hex;
	}

	public static function DiscoverGREE(){

                $devices = array();

                $data = array(
                    't'      => 'scan',
                );
                $content = json_encode($data);

                $cs = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

                if(!$cs){
                        return $devices;
                }

                socket_set_option($cs, SOL_SOCKET, SO_REUSEADDR, 1);
                socket_set_option($cs, SOL_SOCKET, SO_BROADCAST, 1);
                socket_set_option($cs, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
                socket_bind($cs, 0, 0);

                $packet = json_encode($data);

                socket_sendto($cs, $packet, strlen($packet), 0, '255.255.255.255', 7000);
                while(socket_recvfrom($cs, $json_response, 2048, 0, $from, $port)){
                        $host = $from;
                        $response = json_decode($json_response, true);
                        $mac = $response ['cid'];
                        $devtype = "0x2711";
                        $host = substr($host, 0, strlen($host));
			$keys = self::pair($mac, $host);

                        $device = hvac::CreateDevice($host, $mac, $devtype, $keys);

			if($device != NULL){
				array_push($devices, $device);
			}
		}

		@socket_shutdown($cs, 2);
		socket_close($cs);

		return $devices;
    }

public function pair($m, $h)
    {
        $sPack = '{
  "mac": "'.$m.'",
  "t": "bind",
  "uid": 0
}';
        $sEncPack = self::fnEncrypt($sPack, self::DEFAULT_KEY);
        $sRequest = '{
  "cid": "'.$m.'",
  "i": 1,
  "pack": "'.$sEncPack.'",
  "t": "pack",
  "tcid": "app",
  "uid": 0
}';
        $fp = fsockopen("udp://".$h, 7000, $errno, $errstr);
        fwrite($fp, $sRequest);
        $x = fread($fp, 1024);
        if ($x) {
            $oData = json_decode($x);
            $oResponse = json_decode(self::fnDecrypt($oData->pack, self::DEFAULT_KEY));
            $keys = $oResponse->key;
            fclose($fp);
	    return $keys;
        }
            fclose($fp);
    }

   function fnEncrypt($sValue, $sSecretKey, $sMethod = 'aes-128-ecb')
    {
        return base64_encode(openssl_encrypt($sValue, $sMethod, $sSecretKey, OPENSSL_RAW_DATA));
    }

    function fnDecrypt($sValue, $sSecretKey, $sMethod = 'aes-128-ecb')
    {
        $sText = base64_decode($sValue);
        return openssl_decrypt($sText, $sMethod, $sSecretKey, OPENSSL_RAW_DATA);
    }

function send_gree_packet($request){

$x='';
        $fp = fsockopen("udp://".$this->host, 7000, $errno, $errstr);

if (!$fp) {
        error_log("ERROR: $errno - $errstr<br />\n");
	return $x;
}
        fwrite($fp, $request);

	stream_set_timeout($fp, 1);
        $x = @fread($fp, 1024);

        if ($x) {
            @fclose($fp);
	    return $x;
} else {
return "";
}
            @fclose($fp);

}

function send_gree_packet_broadcast($request) {

                $cs = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

                if(!$cs){
                        return "";
                }

                socket_set_option($cs, SOL_SOCKET, SO_REUSEADDR, 1);
                socket_set_option($cs, SOL_SOCKET, SO_BROADCAST, 1);
                socket_set_option($cs, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
                socket_bind($cs, 0, 0);
                socket_sendto($cs, $request, strlen($request), 0, '255.255.255.255', 7000);
                socket_recv($cs, $response, 2048, 0);

		@socket_shutdown($cs, 2);
		socket_close($cs);

            $oData = json_decode($response);
            $oResponse = json_decode(self::fnDecrypt($oData->pack, $this->keys));

	return $oResponse;
}

function get_mac() {
$devmac='';
for ($i=5; $i>-1; $i--) {
 $devmac=$devmac.$this->mac[$i];
}
return $devmac;
}

function get_request($sEncPack, $devmac){
$sRequest = '{
  "cid": "app",
  "i": 0,
  "t": "pack",
  "tcid": "'.$devmac.'",
  "uid": 0,
  "pack": "'.$sEncPack.'"
}';
return $sRequest;
}
}

class CH extends hvac{

	function __construct($h = "", $m = "", $d = 0x202) {

		parent::__construct($h, $m, $d);

	}

/*
	protected static function calc_time($tm1, $tm2){
	    tmr = int((bin(tm1)[2:] + bin(tm2)[2:].zfill(8)),2)
	    hrs = int(tmr / 60)
	    mins = int(tmr % 60)
	    return ($hrs, $mins);
	}
*/
        public function get_status(){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));

            $data = array();
            if(count($response) > 0){
	        $data['ac_mode'] =  $response[7] & 7;
	        $data['power'] = ($response[7] >> 3) & 1;
	        $data['fan_speed'] = (int)($response[7]) >> 4;
	        $data['temperature'] = (($response[8] & 15) + 16);
	        $data['temptype'] = ($response[8] >> 5) & 1;
	        $data['quiet'] = ($response[8] >> 6) & 1;
	        $data['fan_direction'] = $response[9] & 15;
	        $data['eco'] = $response[10] & 1;
	        $data['light'] = (int)($response[10] >> 7) & 1;
	        $data['health'] = ($response[10] >> 6) & 1;
	        $data['timing'] = ($response[10] >> 5) & 1;
	        $data['dry'] = ($response[10] >> 4) & 1;
	        $data['wdnumber_mode'] = ($response[10] >> 2) & 3;
	        $data['sleep'] = ($response[10] >> 1) & 1;
	        $data['energy_save'] = ($response[10] >> 4) & 1;
/*	        $tm1 = $response[11];
	        if ($tm1 & 0xF){
	            $tm2 = $response[12];
	            $tm1 = $tm1 & 7;
	            $tms = self.calc_time ($tm1, $tm2);
	    	    $data['timer'] = 1;
	            $data['timer_h'] = $tms[0];
	            $data['timer_m'] = $tms[1];
	        elif ($tm1 & 0xF0):
		    $tm2 = $response[13]
	            $tm1 = ($tm1 >> 4) & 7
	            $tms = self.calc_time (tm1, tm2)
	            $data['timer'] = 2
	            $data['timer_h'] = $tms[0]
	            $data['timer_m'] = $tms[1]
	        else:
	            $data['timer'] = 0
	            $data['timer_h'] = 0
	            $data['timer_m'] = 0
	        }
*/	        $data['stepless_max'] = $response[14];
	        $data['indoorTemperature'] = (strval($response[15]).".".strval($response[16]));

            }
            return $data;
        }


        public function set_temp($temperature){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
		    $response[8] = ($response[8] & 240) | ($temperature - 16);
		    $response[3] = ($response[3] | 1);
		    array_pop($response);
		    $response = $this->send_packet($response);
	    }
	}
	
        public function set_power($power){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[7] = $response[7] & 247;
	        if ($power == 1){
		    $response[7] = $response[7] | 8;
		}
		$response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_ac_mode($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[7] = $response[7] & 248;
	        $response[7] = $response[7] | $mode;
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_fan_speed($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[7] = $response[7] & 15;
		switch ($mode) {
			case 0:
				$result_mode = 0;
				break;
			case 1:
				$result_mode = 16;
				break;
			case 2:
				$result_mode = 32;
				break;
			case 3:
				$result_mode = 48;
				break;
			case 4:
				$result_mode = 64;
				break;
			case 5:
				$result_mode = 80;
				break;
			case 6:
				$result_mode = 96;
				break;
			case 8:
				$result_mode = 128;
				break;
//			default:
//				break;
		}
	        $response[7] = $response[7] | $result_mode;
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_quiet($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[8] = $response[8] & 191;
	        if ($mode){
	    	    $response[8] = $response[8] | 64;
	    	}
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_fan_direction($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[9] = $response[9] & 240;
	        $response[9] = $response[9] | $mode;
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_stepless_max($stepless_max){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[14] = $stepless_max;
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_light($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[10] = $response[10] & 127;
		if ($mode){
	    	    $response[10] = $response[10] | 128;
	    	}
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_health($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[10] = $response[10] & 191;
	        if ($mode){
	    	    $response[10] = $response[10] | 64;
	    	}
		$response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_sleep($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[10] = $response[10] & 253;
	        if ($mode){
		    $response[10] = $response[10] | 2;
		}
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

        public function set_energy_save($mode){
	    $payload = array(0xAA,0xAA,0x12,0xA0,0x0A,0x0A,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00,0x00);
	    $response = self::byte2array($this->send_packet($payload));
            if(count($response) > 0){
	        $response[10] = $response[10] & 239;
	        if ($mode){
		    $response[10] = $response[10] | 16;
		}
	        $response[3] = $response[3] | 1;
		array_pop($response);
		$response = $this->send_packet($response);
	    }
	}

}


class Gree extends hvac{

	function __construct($h = "", $m = "", $d = 0x2711, $k = '') {

		parent::__construct($h, $m, $d, $k);

	}


        public function get_status(){

$devmac = self::get_mac();

$sPack = '{
"cols": [
    "Pow", 
    "Mod", 
    "SetTem", 
    "WdSpd", 
    "Air", 
    "Blo", 
    "Health", 
    "SwhSlp", 
    "Lig", 
    "SwingLfRig", 
    "SwUpDn", 
    "Quiet", 
    "Tur", 
    "StHt", 
    "TemUn", 
    "HeatCoolType", 
    "TemRec", 
    "SvSt"
  ],
  "mac": "'.$devmac.'",
  "t": "status"
}';

$sEncPack = self::fnEncrypt($sPack, $this->keys);

$sRequest = self::get_request($sEncPack, $devmac);

$x = $this->send_gree_packet($sRequest);
            $oData = json_decode($x);
            $oResponse = json_decode(self::fnDecrypt($oData->pack, $this->keys));
	    $response = get_object_vars($oResponse)['dat'];

            $data = array();
            if(count($response) > 0){
	        $data['power'] = $response[0];
	        $data['ac_mode'] =  $response[1];
	        $data['temperature'] = $response[2];
	        $data['fan_speed'] = $response[3];
	        $data['air'] = $response[4];
	        $data['blow'] = $response[5];
	        $data['health'] = $response[6];
	        $data['sleep'] = $response[7];
	        $data['light'] = $response[8];
	        $data['fan_directionh'] = $response[9];
	        $data['fan_direction'] = $response[10];
	        $data['quiet'] = $response[11];
	        $data['turbo'] = $response[12];
	        $data['stht'] = $response[13];
	        $data['temptype'] = $response[14];
	        $data['heatcooltype'] = $response[15];
	        $data['temrec'] = $response[16];
	        $data['energy_save'] = $response[17];
            }
            return $data;
        }

public function set_temp($temperature){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["TemUn", "SetTem"],
  "p": [0, '.$temperature.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);

}


        public function set_power($power){


$devmac = self::get_mac();
$sPack = '{
  "opt": ["Pow"],
  "p": ['.$power.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_ac_mode($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["Mod"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_fan_speed($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["WdSpd"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_quiet($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["Quiet"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_fan_direction($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["SwUpDn"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_light($mode){

$devmac = self::get_mac();

        $sPack = '{
  "opt": ["Lig"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_health($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["Health"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}

        public function set_sleep($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["SwhSlp"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
	$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}


        public function set_energy_save($mode){

$devmac = self::get_mac();
        $sPack = '{
  "opt": ["SvSt"],
  "p": ['.$mode.'],
  "t": "cmd"
}';
        $sEncPack = self::fnEncrypt($sPack, $this->keys);
	$sRequest = self::get_request($sEncPack, $devmac);
$response = $this->send_gree_packet_broadcast($sRequest);
}

}


?>
