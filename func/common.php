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

		//top 10 holdings		
		preg_match("/<tbody>(.*)<\/tbody>/", $return, $tbl[0]);
		//country weighting
		preg_match("/<div class=\"diagram_block\".*[Other]*[^<\/div>]/", $return, $tbl[1]);
		//sector weighting
		preg_match("/Sector Weightings.*.+?(?=<\/ul><\/div><\/div>)/", $return, $tbl[2]);
		
		$output_table['country_csv'] = parse_country($tbl[1][0]);
		$output_table['sector_csv'] = parse_sector($tbl[2][0]);

		
		//delete class="" from html
		$replace_class = "/ class=(.*?)[^\>]*/";
		$output_table[0][0] = preg_replace($replace_class, "", $tbl[0][0]);
		$output_table[1][0] = preg_replace($replace_class, "", $tbl[1][0]);
		$output_table[2][0] = preg_replace($replace_class, "", $tbl[2][0]);
		
		//replace extra fields
		$output_table[1][0] = preg_replace("/<\/li><li><span>(.*?)<\/span>/", "</li><li>", $output_table[1][0]);
		$output_table[1][0] = preg_replace("/<p>/", "", $output_table[1][0]);
		$output_table[1][0] = preg_replace("/<\/p>/", " ", $output_table[1][0]);
		
		//replace extra fields, p
		$output_table[2][0] = preg_replace("/<\/li><li><span>(.*?)<\/span>/", "</li><li>", $output_table[2][0]);
		$output_table[2][0] = preg_replace("/<p>/", "", $output_table[2][0]);
		$output_table[2][0] = preg_replace("/<\/p>/", " ", $output_table[2][0]);
		
		//get ETF name and description
		preg_match("/fund-title(.*)<\//", $return, $output_table['name']);
		$output_table['name'] = etf_name($output_table['name'][0]);
		
		preg_match("/<MainText><p>(.*)(.*?)<\/p><\/MainText>/", $return, $output_table['d']);
		if(empty($output_table['d'])){
			unset($output_table['d']);
			preg_match("/<p>Market Vectors<sup>(.*)(.*?)<\//", $return, $output_table['d']);
		}
		//get rid of <sup>
		$output_table['desc'][1] = preg_replace("/<sup.*?<\/sup>/", "", $output_table['d'][1]);
		
		//if no response, either etf is not valid or time out occured, notify
		if(empty($output_table[0][0]) && empty($output_table[1][0]) || empty($output_table)){
			$output_table['error'] = $etf;
		}
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
			$val8 = ($row->find('td',8)->plaintext) ? '"'.$row->find('td',8)->plaintext.'"' : '';

			$table[] = $name.$val.$val2.$val3.$val4.$val5.$val6.$val7.$val8.PHP_EOL;
		}
		array_pop($table);
		$csv = implode($table,"");
		$country_csv = $data['country_csv'];
		$sector_csv = $data['sector_csv'];
		$name = $data['name'];
		
		$description = $data['desc'][1];
		
		//$response = store_html($etf, $data[0][0], $csv, $data[1][0], $country_chart, $data[2][0], $sector_chart,$description,$name);
		$response = store_html($etf, $data[0][0], $csv, $data[1][0], $country_csv, $data[2][0], $sector_csv,$description,$name);

		if($response){
			echo "ETF parsed and stored in the database.";
		} else {
			echo "ETF failed";
			print_r($response);
		}
	}//parse top 10 table


	//store all data to database
	function store_html($etf,$html,$csv,$country_html,$country_csv,$sector_html,$sector_csv,$description,$name){
		global $mysqli;
	
		$query = "INSERT INTO etf_data (ETF, html, csv, country_weight_html, weight_csv, sector_html, sector_csv, description, name) VALUES (?,?,?,?,?,?,?,?,?)";
		if($stmt = $mysqli->prepare($query)){
		
			echo "INSERT STATEMENT!!!!";
		
			$stmt->bind_param("sssssssss", $etf, $html, $csv, $country_html, $country_csv, $sector_html, $sector_csv, $description, $name);
			
			$etf = $mysqli->real_escape_string($etf);
			$html = $mysqli->real_escape_string($html);
			$csv = $mysqli->real_escape_string($csv);
			$country_html = $mysqli->real_escape_string($country_html);
			$country_csv = $mysqli->real_escape_string($country_csv);
			$sector_html = $mysqli->real_escape_string($sector_html);
			$sector_csv = $mysqli->real_escape_string($sector_csv);
			$name = $mysqli->real_escape_string($name);
			$description = $mysqli->real_escape_string($description);
			
			var_dump($stmt);
			
			$stmt->execute();
			return True;
		}
		return False;
	}

	/**
	check if ETF exist and get data from database
	 */
	function if_etf_exist($etf,$mysqli){
		global $mysqli;
		if($stmt = $mysqli->prepare("SELECT * FROM etf_data WHERE ETF = ? ")){
			$stmt->bind_param("s", $etf);
			$stmt->execute();
			$res = $stmt->get_result();
			$stmt->close();
			return $res->fetch_object();
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

			$string[] = '"'.$name.'",'.$value.PHP_EOL;
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

			$string2[] = '"'.$n.'",'.$v.PHP_EOL;
		}
		return implode($string2,"");
	}
	
	function etf_name($name){
		preg_replace("/<sup.*?<\/sup>/", "", $name);
		return substr(substr($name, 12),0, -2);
	}
	
	function download_csv($etf){
		global $mysqli;
		$select = "SELECT csv, weight_csv, sector_csv FROM etf_data WHERE ETF = ? ";
		if($csv = $mysqli->prepare($select)){
			$csv->bind_param("s", $etf);
			$csv->execute();
			$res = $csv->get_result();
			$row = $res->fetch_object();
			$file = $row->csv."\n".$row->weight_csv."\n".$row->sector_csv;
			file_put_contents("csv/".strtoupper($etf).".csv", print_r($file, true));
			$csv->close();
		}
	}
	
?>