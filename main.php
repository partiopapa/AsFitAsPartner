<?php

function check_login($usr, $pass, $connection)
{
	// Get the user with the specified name from the database
	$sql_query = "SELECT * FROM users WHERE username='$usr'";
	$sql_result = $connection->query($sql_query);
	
	if($sql_result->num_rows == 1)
	{
		// User was found
			
		// Get the correct password of the user
		$pass_query = "SELECT password FROM users WHERE username='$usr'";
		$pass_result = $connection->query($pass_query);
		$row = $pass_result->fetch_array(MYSQLI_ASSOC);
		$correct_pass = $row["password"];
		encrypt_password($correct_pass);
		
		if($pass == $correct_pass) {
			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		// Username wasn't found
		return false;
	}
}

function encrypt_password($password)
{
	return crypt($password, "blgyfdrtze");
}

function database_connection()
{
	$new_connection = new mysqli("mysql.serversfree.com","u692401063_user","asfitaspartner", "u692401063_db");
	if($connection->connect_error) {
		die("MySQL connection error");
	}
	return $new_connection;
}

switch ($_POST["action"]) {
//---------------------------------------------------------------------------------------------------
	case "login":	
		// JSON response object to client
		$response = (object) array('success'=>false, 'message'=> '', 'user'=>'');
		
		//Create the connection
		$connection = database_connection();

		// Encrypt password and sanitize information
		$usr = mysqli_real_escape_string($connection, $_POST["log_usr"]);
		$pass = encrypt_password($_POST["log_pass"]);
		
		if(empty($usr) || empty($pass))
		{
			$response->message = "some fields are empty";
			echo json_encode($response);
			exit;
		}
		//Is login accepted
		$loginAccepted = check_login($usr, $pass, $connection);
			
		if($loginAccepted == true) 
		{
			$response->success = true;
			$response->user = $usr;
			echo json_encode($response);
			exit;
		}
		else
		{
			$response->message = "username/password incorrect";
			echo json_encode($response);
			exit;
		}
//---------------------------------------------------------------------------------------------------					
	case "register":
		
		// JSON response object to client
		$response = (object) array('success' => false, 'message' => '');
		
		$connection = database_connection();
		
		// Encrypt password and sanitize information
		$usr = mysqli_real_escape_string($connection, $_POST["reg_usr"]);
		$pass = encrypt_password($_POST["reg_pass"]);
		$phone = mysqli_real_escape_string($connection, $_POST["reg_phone"]);
		$email = mysqli_real_escape_string($connection, $_POST["reg_email"]);
		$ip = $_SERVER["REMOTE_ADDR"];
		
		
		if(empty($usr) || empty($pass) || empty($phone) || empty($email))
		{
			$response->message = "some fields are empty";	
			echo json_encode($response);
			exit;
		}
		
		// TODO: TARKISTA, ETTEI KÄYTTÄJÄNIMESSÄ OLE SQL KOMENTOJA
		$sql_query = "SELECT * FROM users WHERE username='$usr'";
		$sql_result = $connection->query($sql_query);
		
		if ($sql_result->num_rows > 0) 
		{
			$response->message = "The username is already in use!";
			echo json_encode($response);
			exit;
			
		}
		
		// Add user to database
		$add_query = "INSERT INTO users (username, password, email, phone, ip) VALUES ('$usr','$pass','$email','$phone','$ip');";
		
		if($connection->query($add_query) === TRUE)
		{
			$response->success = true;
			$response->message ="Done!";
			echo json_encode($response);
			exit;
		} else {
			$response->message ="Fail!".$connection->error;
			echo json_encode($response);
			exit;
		}
//---------------------------------------------------------------------------------------------------			
	case "newtask":
		$response = (object) array('success'=>false, 'message'=> '');
		
		$connection = database_connection();
		
		// Encrypt password and sanitize information
		$usr = mysqli_real_escape_string($connection, $_POST["log_usr"]);
		$pass = encrypt_password($_POST["log_pass"]);
		//DB-->
		$type = $_POST["activity_type"];
		$duration = $_POST["activity_duration"];
		$comment = $_POST["activity_comment"];
		$timestamp = date('Y-m-d H:i:s');
		
		
		//Is login ok?
		$loginAccepted = check_login($usr, $pass, $connection);
		
		
		if($loginAccepted == true)
		{
			//Login ok
			
			//Send data to db
			$sql_query = "INSERT INTO activities (sender, timestamp, type, duration, comment) VALUES ('$usr', '$timestamp', '$type', '$duration', '$comment');";
			//echo $sql_query;
			if($connection->query($sql_query) === TRUE)
			{
				//Success
				$response->success = true;
				$response->message = "Success!";
				json_encode($response);
				
			} else 
			{
				//Fail
				echo $connection->error;
			}
			
			//TODO Lisää tauluun & lisää timestamp
		}
		else
		{
			//Login unsuccesful
			$response->success = false;
			$response->message = "Authenication problem!";
			echo json_encode($response);	
		}
//---------------------------------------------------------------------------------------------------
	case "getdiaryfeed":
		$response = (object) array('success'=>false, 'diaryfeed'=> array());
		
		$connection = database_connection();
		
		// Encrypt password and sanitize information
		$usr = mysqli_real_escape_string($connection, $_POST["log_usr"]);
		$pass = encrypt_password($_POST["log_pass"]);
		
		$amount = intval($_POST["amount"]);
		
		//Is login ok?
		$loginAccepted = check_login($usr, $pass, $connection);
		
		if($loginAccepted == true)
		{
			//Login ok
			$sql_query = "SELECT * FROM activities WHERE sender='$usr'";
			$sql_result = $connection->query($sql_query);
			
			while ($row = $sql_result->fetch_row()) {
				$response->diaryfeed[] = $row;
				$amount--;
				if ($amount == 0) break;
			}
			
			$response->success = true;
			
			echo json_encode($response);
			exit;		
		}
		else
		{
			//Login unsuccesful
			$response->success = false;
			echo json_encode($response);
			exit;
		}	
//---------------------------------------------------------------------------------------------------		
	case "newgroup":
		
		$response = (object) array('success'=>false);
		
		$connection = database_connection();
		
		// Encrypt password and sanitize information
		$usr = mysqli_real_escape_string($connection, $_POST["log_usr"]);
		$pass = encrypt_password($_POST["log_pass"]);
		$newgroupname = mysqli_real_escape_string($connection, $_POST["new_group_name"]);
		
		$loginAccepted = check_login($usr, $pass, $connection);
		
		if($loginAccepted == true)
		{
			//Success
			$sql_query = "INSERT INTO groups (name, admin, users) VALUES ('$newgroupname', '$usr', '');";
			$sql_result = $connection->query($sql_query);
			
			if($sql_result === true)
			{
				$response->success = true;
				echo json_encode($response);
				exit;
				
			}
			else
			{
				echo json_encode($response);
				exit;
				
			}
		}
		else
		{
			//Login failed
			echo json_encode($response);
			exit;
		}
		
		
		

//---------------------------------------------------------------------------------------------------
		
	case "searchuser":
		$response = (object) array('suggestions'=>array());
		
		$searchword = $_POST[""];
		
//---------------------------------------------------------------------------------------------------
		
	case "addusertogroup":
		
	
		
		
		
		

		
		
		
}

?>
