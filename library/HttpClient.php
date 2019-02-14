<?php

class HttpClient {

	public static function post($url, $postData, $header = NULL, $timeout = 60, $ssl = FALSE){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		if($header){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		
		$retval = curl_exec($ch);
		if (curl_errno($ch)) {
	        $retval = curl_error($ch);
		}

		curl_close($ch);
		return $retval;
	}
}