<?php
		//get html using cURL
		$ch = curl_init();
		try {
    		$header = array(
    			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
    			'Accept-Language: en-us;q=0.8,en;q=0.6'
    		);
    
    		$url = 'http://www.vaneck.com/market-vectors/';
			$cookie_file = "cookie1.txt";
    
    		curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13');
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, True);
    		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    		//curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);    
        
        	$return = curl_exec($ch);
    	} catch (Exception $e){
        	echo "Couldn't load page: ",  $e->getMessage(), "\n";
    	}
	    curl_close($ch);

		file_put_contents("market2.html",print_r($return, true));


?>