<?php
	session_start();
	include "func/common.php";
	include "func/user.php";
?>
<!DOCTYPE html>
<head>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<style>
    body {
      padding-bottom: 40px;
      background-color: #eee;
    }

    .form-signin {
      max-width: 330px;
      padding: 15px;
      margin: 0 auto;
    }
    .form-signin .form-signin-heading,
    .form-signin .checkbox {
      margin-bottom: 10px;
    }
    .form-signin .checkbox {
      font-weight: normal;
    }
    .form-signin .form-control {
      position: relative;
      height: auto;
      -webkit-box-sizing: border-box;
         -moz-box-sizing: border-box;
              box-sizing: border-box;
      padding: 10px;
      font-size: 16px;
    }
    .form-signin .form-control:focus {
      z-index: 2;
    }
    .form-signin input[type="email"] {
      margin-bottom: -1px;
      border-bottom-right-radius: 0;
      border-bottom-left-radius: 0;
    }
    .form-signin input[type="password"] {
      margin-bottom: 10px;
      border-top-left-radius: 0;
      border-top-right-radius: 0;
    }
	</style>
</head>
<body>
<?php

	//print_r(user_history());
	
	$user = new Users;
		
	if(isset($user->messages)){
		foreach($user->messages as $key => $message){
			echo '<div class="alert alert-info" role="alert">'.$message.'</div>';
		}
	}
	
	if(isset($user->errors)){
		foreach($user->errors as $key => $error){
			echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
		}
	}
if($_GET['new_user']){
?>
	<div class="container">
		<form class="form-signin" action="index.php" method="post">
        	<h2 class="form-signin-heading">Registration Form</h2>
        	<input type="name" name="username" class="form-control" placeholder="Username" required="" autofocus="" value="" minlength=3 maxlength=20>
			<input type="name" name="name" class="form-control" placeholder="Name (Optional)" value="">
        	<input type="email" name="email" class="form-control" placeholder="Email" required="" value="">
        	<input type="password" name="password" class="form-control" placeholder="Password" required="" value="" minlength=3 maxlength=20>
        	<button class="btn btn-lg btn-primary btn-block" type="submit" name="registration" value="registration">Register</button>
    	</form>
    </div>
<?php	
} else if(!$user->login_status()){
	//show login form
?>
	<div class="container">
		<form class="form-signin" action="index.php" method="post">
        	<h2 class="form-signin-heading">Please log in</h2>
        	<input type="name" name="username" class="form-control" placeholder="Name or Email address" required="" autofocus="" value="">
        	<input type="password" name="password" class="form-control" placeholder="Password" required="" value="">
        	<button class="btn btn-lg btn-primary btn-block" type="submit" name="login" value="login">Log in</button>
    	</form><br><br>
		<div class="alert alert-info" role="alert">Or Register <a href="?new_user=1"><strong>Here</strong></a></div> 
	</div>	
<?php
} else {
?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button> 
      <a class="navbar-brand" href="/">Hello <?php echo $_SESSION['username']?></a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li><a href="?logout=1">Logout</a></li>
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Your search history<span class="caret"></span></a>
          <ul class="dropdown-menu">
          <?php
          	//get all user history
			$user_history = $user->get_user_history();
			foreach($user_history as $key => $history){	
						
				echo '<li><a href="?etf='.$history[2].'">'.strtoupper($history[2]).'</a></li>';
			}
			?>          
            <li role="separator" class="divider"></li>
          </ul>
        </li>
      </ul>
      <form class="navbar-form navbar-left" role="search" action="" method="GET">
        <div class="form-group">
          <input type="text" class="form-control" placeholder="Search" name="etf" value="">
        </div>
        <button type="submit" class="btn btn-default" value="submit">Submit</button>
      </form>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
<?php

	//user logged in
	$class = new Parser();
	$html = new simple_html_dom();
	
	if(isset($_GET['etf'])){
			
		echo '<div class="container"';
		echo "<br><br>ETF: ".$etf;
		//sanitizing entered data
		$etf = preg_replace('/[^a-zA-Z0-9 .-]/','',substr($_GET['etf'], 0, 4));
		
		$user->save_user_history($etf);
		//determine whether new or exist in db
		$response = if_etf_exist($etf);

		//display accordingly
		if($response){
			echo "ETF data from Database:<br>";
			echo '<table class="table">'.$response->html.'</table><br>';
			echo '<table class="table">'.$response->country_weight_html.'</table><br>';
			echo '<table class="table">'.$response->sector_html.'</table>';
		} else {
			$data = $class->get_etf_data($etf);

			echo 'New ETF data stored in database:<br><table class="table">';
			print_r($data[0][0]);
			echo '</table>';

			echo '<table class="table">';
			print_r($data[1][0]);
			echo '</table>';
			echo '<br>';
			echo '<table class="table">';
			print_r($data[2][0]);
			echo '</table>';

			parse_top_10($data, $etf);
		}
		$etf = strtoupper($etf);
		$file = file_get_contents("csv/".$etf.".csv");
		if (empty($file)){
			download_csv($etf);
		}
		echo '<a href="csv/'.$etf.'.csv">Download csv format '.$etf.'</a>';
		echo '</div>';
	}//get data
}//if else
?>
</div>
<!-- Latest compiled and minified JavaScript -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html>

