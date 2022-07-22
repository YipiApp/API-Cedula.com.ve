<?php
	define('APPID_CEDULA', 'APP-ID-AQUI');
	define('TOKEN_CEDULA', 'TOKEN-AQUI');
	function getCurlData($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		$curlData = curl_exec($curl);
		curl_close($curl);
		return $curlData;
	}
	function getCI($cedula, $return_raw = false) {
		$res = getCurlData("https://api.cedula.com.ve/api/v1?app_id=".APPID_CEDULA."&token=".TOKEN_CEDULA."&cedula=".(int)$cedula);
		if($return_raw)
			return strlen($res)>3?$res:false;
		$res = json_decode($res, true);
		return isset($res['data']) && $res['data']?$res['data']:$res['error_str'];
	}
	$consulta = getCI(00000);
	if(is_array($consulta)) {
		print_r($consulta);
	}else{
		echo "Ocurrio un error en la consulta: ".$consulta;
	}
