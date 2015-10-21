<?php
include "simple_html_dom.php";

class Parser{
	
	public function get_etf_data($etf){
		
		//get html using cURL
		$ch = curl_init();
		try {
    		$header = array(
    			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
    			'Accept-Language: en-us;q=0.8,en;q=0.6'
    		);
    
    		$url = 'http://www.vaneck.com/funds/'.$etf.'.aspx';
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

		
		//preg_match("/<table class=\"holding\".(?s).*.[these]/", $return, $output_table[0]);
		preg_match("/<tbody>(.*)<\/tbody>/", $return, $output_table[0]);
		preg_match("/<div class=\"diagram_block\".*[Other]*[^<\/div>]/", $return, $output_table[1]);
		preg_match("/Sector Weightings.*.+?(?=<\/ul><\/div><\/div>)/", $return, $output_table[2]);
		
		return $output_table;
	}
	
}

	function parse_top_10($data, $etf){

		global $html;
		//load table to html dom library
		$html->load($data[0][0]);
		$table = array();
		$counter = 0;
		foreach($html->find('tr') as $row) {

			$name = ($row->find('td',0)->plaintext) ? '"'.$row->find('td',0)->plaintext.'",' : '';
			$val = ($row->find('td',1)->plaintext) ? '"'.$row->find('td',1)->plaintext.'",' : '';
			$val2 = ($row->find('td',2)->plaintext) ? '"'.$row->find('td',2)->plaintext.'",' : '';
			$val3 = ($row->find('td',3)->plaintext) ? '"'.$row->find('td',3)->plaintext.'",' : '';
			$val4 = ($row->find('td',4)->plaintext) ? '"'.$row->find('td',4)->plaintext.'",' : '';
			$val5 = ($row->find('td',5)->plaintext) ? '"'.$row->find('td',5)->plaintext.'",' : '';
			$val6 = ($row->find('td',6)->plaintext) ? '"'.$row->find('td',6)->plaintext.'",' : '';
			$val7 = ($row->find('td',7)->plaintext) ? '"'.$row->find('td',7)->plaintext.'"' : '';

			$table[] = $name.$val.$val2.$val3.$val4.$val5.$val6.$val7.PHP_EOL;
		}
		array_pop($table);
		$csv = implode($table,"");

		$country_chart = parse_country($data[1][0]);
		$sector_chart = parse_sector($data[2][0]);
		$response = store_html($etf, $data[0][0], $csv, $data[1][0], $country_chart, $data[2][0], $sector_chart);

		if($response){
			echo "ETF parsed and store in database.";
		} else {
			print_r($response);
		}
	}//parse top 10 table


	//store all data to database
	function store_html($etf,$html,$csv,$country_html,$country_csv,$sector_html,$sector_csv){
		global $mysqli;
		//$etf = mysqli_real_escape_string($etf);
		//$html = mysqli_real_escape_string($html);
		//$csv = mysqli_real_escape_string($csv);
		//$country_html = mysqli_real_escape_string($country_html);
		//$country_csv = mysqli_real_escape_string($country_csv);
		//$sector_html = mysqli_real_escape_string($sector_html);
		//$sector_csv = mysqli_real_escape_string($sector_csv);

		$result = $mysqli->query("INSERT INTO etf_data (ETF,html,csv,country_weight_html,weight_csv,sector_html,sector_csv,user_id) 
				VALUES ('".strtoupper($etf)."','".$html."','".$csv."','".$country_html."','".$country_csv."','".$sector_html."','".$sector_csv."','".$user_id."')");
		if($result->num_rows){
			return True;
		}
		return False;
	}

	/**
	check if ETF exist and get data from database
	 */
	function if_etf_exist($etf,$mysqli){
		global $mysqli;
		$res = $mysqli->query("SELECT * FROM etf_data WHERE ETF like '{$etf}'");
		if(mysqli_num_rows($res) > 0){
			return mysqli_fetch_object($res);
		}
		return False;
	}

	//parser for sector chart
	function parse_sector($table){
		//load table to html dom library		
		global $html;
		$html->load($table);

		foreach($html->find('li') as $val){
			$name = $val->find('p',0)->plaintext;
			$value = $val->find('span',1)->plaintext;

			$string[] = $name.','.$value.PHP_EOL;
		}
		return implode($string,"");
	}

	//parser for country chart
	function parse_country($table2){
		//load table to html om library
		global $html;
		$html->load($table2);

		foreach($html->find('li') as $val){

			$n = $val->find('p',0)->plaintext;
			$v = $val->find('span',1)->plaintext;

			$string2[] = $n.','.$v.PHP_EOL;
		}
		return implode($string2,"");
	}
	
	function user_history(){
		global $mysqli;
		//$sql = "SELECT * FROM user_history WHERE username LIKE '".$_SESSION['username']."' order by Date ASC";
		$sql = "SELECT * FROM user_history";
		return $mysqli->query($sql)->fetch_object();
	}
	
	function download_csv($etf){
		global $mysqli;
		$etf = strtoupper($etf);
		$select = "SELECT csv, weight_csv, sector_csv FROM etf_data WHERE ETF LIKE '".$etf."'";
		$res = $mysqli->query($select)->fetch_object();
		$file = $res->csv."\n".$res->weight_csv."\n".$res->sector_csv;
		file_put_contents("csv/".$etf.".csv", print_r($file, true));
	}
	
?>