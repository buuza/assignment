<?php
	include "DB.php";

class Users {

	private $connection;
	public $username;
	public $_SESSION = array();
	public $email;
	public $errors = array();
	public $messages = array();
	
	function __construct(){
		//connect to database
		$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
	
		//determine the operation
		if (isset($_POST["login"])) {
            $this->login();
        } else if (isset($_POST['registration'])){
        	$this->registration();
        } else if(isset($_GET["logout"])) {
			$this->logout();
        }
	}

	public function ping($test){
		return "Your message: ".$test;
	}
	
	/**
     * login user with valid credentials. set the user's login status true
     */
	private function login(){
		if (empty($_POST['username'])) {
			$this->errors[] = "Username field was empty.";
        } elseif (empty($_POST['password'])) {
            $this->errors[] = "Password field was empty.";
        } elseif (!empty($_POST['username']) && !empty($_POST['password'])) {        
			$this->username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);			
        	
        	//$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
			//get username info
			//$result = $this->connection->query("SELECT * FROM users WHERE username = '".$this->username."' OR email = '".$this->username."'");
			
			//prepared statement to login user
			$sel = "SELECT * FROM users WHERE username = ? OR email = ? ";
        	if($stmt = $this->connection->prepare($sel)){
        		$stmt->bind_param("ss", $this->username, $this->username);
        		$stmt->execute();
        		//$stmt->bind_result();
        		$res = $stmt->get_result();
        		$user_info = $res->fetch_object();
        	}
			
        	//$user_info = $result->fetch_object();
						
			if (password_verify($_POST['password'], $user_info->password)) {
				$_SESSION['username'] = $user_info->username;
				$_SESSION['email'] = $user_info->email;
				$_SESSION['user_login_status'] = 1;
			} else if(empty($user_info)){
				$this->errors[] = "Wrong User name. Please try again";
			} else {
				$this->errors[] = "Wrong password. Please try again";
			}
		}
	}
	
	private function registration(){
		//even though there is html entry validation, data still needs to be checked
		if(empty($_POST['username'])){
			$this->errors[] = "Username field was empty.";
		} else if (empty($_POST['password'])){
			$this->errors[] = "Password field was empty.";	
		} else if(empty($_POST['email'])){
			$this->errors[] = "Email field was empty.";
		} else if(!preg_match('/^[a-z\d]{2,20}$/i', $_POST['username'])){
			$this->errors[] = "Username must be between 3 and 20 characters.";
		} else if(strlen($_POST['password']) < 2){
			$this->errors[] = "Password must be between 3 and 20 characters.";
		} else if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
			$this->errors[] = "Email is not valid.";
		}// validation to be continued
		
		//$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
		if (!$this->connection->set_charset("utf8")) {
			$this->errors[] = $this->connection->error;
		}//set chars to utf8
		
		$this->username = $this->connection->real_escape_string(strip_tags($_POST['username'], ENT_QUOTES));
		$this->name = $this->connection->real_escape_string(strip_tags($_POST['name'], ENT_QUOTES));
		$this->email = $this->connection->real_escape_string(strip_tags($_POST['email'], ENT_QUOTES));
		$password = $_POST['password'];
		
		//works only with PHP v5.5+
		$user_password_hash = password_hash($password, PASSWORD_DEFAULT);
		
		//check if user already exist
		//$sql = "SELECT * FROM users WHERE username = '".$this->username."' OR email = '".$this->email."' ";
		//$check_user = $this->connection->query($sql);
		$sql = "SELECT * FROM users WHERE username = ? OR email = ? ";
		if($stmt = $this->connection->prepare($sql)){
			$stmt->bind_param("ss", $this->username, $this->email);
			$stmt->execute();
			$check_user = $stmt->num_rows;
		}
		
		if (!$this->connection->connect_errno){

			//if($check_user->num_rows > 0){
			if($check_user > 0){
				//raise error if duplicate entry
				$this->errors[] = "Sorry, Username or Email already taken.";	
			} else {
				//prepare statement to insert new user data, validation and sanitation is done by Prepare
				$insert = "INSERT INTO users (username, name, password, email) VALUES (?, ?, ?, ?)";
					
				if($stmt->prepare($insert)){
					$stmt->bind_param("ssss", $this->username, $this->name, $user_password_hash, $this->email);
					if($stmt->execute()){//returns False on failure
						$this->messages[] = "Your account has created successfully.";
						$_SESSION['username'] = $this->username;
						$_SESSION['email'] = $this->email;
						$_SESSION['user_login_status'] = 1;
					} else {
						$this->errors[] = "Sorry, registration failed, please try again.";
					}//query execute failed
				} else {
					$this->errors[] = "Sorry, connection to database lost.";
				}//query prepare failed
			}//else 
		} else {
			$this->errors = "No database connection.";
		}//no connection to database
	}
	
	/**
     * logout user
     */
	public function logout(){
		 $_SESSION = array();
        session_destroy();
        // return a little feeedback message
        $this->messages[] = "You have been logged out.";
	}
	
	/**
     * return the current state of the user's login
     * @return boolean user login status
     */
	public function login_status()
    {
        if (isset($_SESSION['user_login_status']) AND $_SESSION['user_login_status'] == 1) {
            return true;
        }
        // default return
        return false;
    }
    
    public function save_user_history($etf){    	
    	//prepare statement and store user history
    	$query = "INSERT INTO user_history (username, ETF) VALUES (?, ?)";
    	if($insert = $this->connection->prepare($query)){
    		$insert->bind_param("ss", $_SESSION['username'], $etf);
    		$insert->execute();
    	}

    }//store user search in db
    
    public function get_user_history(){
    	$s = "SELECT * FROM user_history WHERE username = ? group by ETF order by Date ASC";
    	if($stmt = $this->connection->prepare($s)){
    		$stmt->bind_param("s", $_SESSION['username']);
    		$stmt->execute();
    		$res = $stmt->get_result();
    		while($row = $res->fetch_object()){
    			$history[] = $row;
    		}
    		return $history;
    	}
    	   	
    }
}//class Users
?>