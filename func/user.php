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
        	
        	$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
			//get username info
			$result = $this->connection->query("SELECT * FROM users WHERE username LIKE '".$this->username."' OR email LIKE '".$this->username."'");
        	$user_info = $result->fetch_object();
						
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
		
		$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
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
		$sql = "SELECT * FROM users WHERE username LIKE '".$this->username."' OR email LIKE '".$this->email."' ";
		$check_user = $this->connection->query($sql);
		
		if (!$this->connection->connect_errno){

			if($check_user->num_rows > 0){
				//raise error if duplicate entry
				$this->errors[] = "Sorry, Username or Email already taken.";	
			} else {
				$insert = "INSERT INTO users (username, name, password, email)
					VALUES ('".$this->username."','".$this->name."','".$user_password_hash."','".$this->email."')";
				
				$query_new_user_insert = $this->connection->query($insert);
				
				if($query_new_user_insert){
					$this->messages[] = "Your account has created successfully.";
					$_SESSION['username'] = $this->username;
					$_SESSION['email'] = $this->email;
					$_SESSION['user_login_status'] = 1;
				} else {
					$this->errors[] = "Sorry, registration failed, please try again.";
				}
			} 
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
    	$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
    	$check = "SELECT * FROM user_history WHERE username LIKE ".$_SESSION['username']." and ETF LIKE '".$etf."'";
    	$this->connection->query($check);
    	if($this->connection->num_rows > 0){
    		$query = "UPDATE user_history SET Date = '".date("Y-m-d H:i:s")."' WHERE username LIKE '".$_SESSION['username']."' and ETF like '".$etf."'";
    	} else {
    		$query = "INSERT INTO user_history (username, ETF) VALUES ('".$_SESSION['username']."','".$etf."')";
		
			if (!$this->connection->set_charset("utf8")) {
				$this->errors[] = $this->connection->error;
			}//set chars to utf8
		}
		$this->connection->query($query);
    }
    
    public function get_user_history(){
		$this->connection = new mysqli(HOST_NAME, DB_USER, DB_PASS, DATABASE);
    	//$s = "SELECT * FROM user_history WHERE username LIKE '".$_SESSION['username']."' order by Date ASC";
    	$s = "select * from users";
		if (!$this->connection->set_charset("utf8")) {
			$this->errors[] = $this->connection->error;
		}//set chars to utf8
		return $this->connection->query($s)->fetch_assoc();
    }
    
    function __destruct(){
    	$this->connection->close();
    }
}//class Users
?>