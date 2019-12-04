<?php 
	session_start();

	// variable declaration
	$username = "";
	$email = "";
	$errors = array(); 
	$_SESSION['success'] = "";

	// connect to mysql
	$db = mysqli_connect('localhost', 'root', '');

	// creates the database 'registration' if none exists
	if (!$db->query(
		"CREATE DATABASE IF NOT EXISTS registration"))
	{
		printf("Error message: %s\n", $db->error);
	}
	else{
		// creates the users table
		$db->query(
			"CREATE TABLE IF NOT EXISTS registration.users(
				id INT NOT NULL AUTO_INCREMENT,
				isAdmin INT NOT NULL DEFAULT (0),
				username VARCHAR(100) NOT NULL,
				email VARCHAR(100) NOT NULL,
				salt VARCHAR(10) NOT NULL,
				password VARCHAR(255) NOT NULL,
				PRIMARY KEY(id)) DEFAULT CHARSET=utf8mb4;") or exit($db->error);
		
		// creates ADMIN user
		$salt = uniqid(mt_rand());
		$admin_pass = "SAD_2019!";
		$pass = md5($admin_pass);
		$db->query(
			"INSERT INTO registration.users (id, isAdmin, username, email, salt, password)
			VALUES ('1', '1', 'ADMIN', 'admin@email.com', '$salt', MD5(salt + ':' + '$pass'));");

		// creates the session table
		$db->query(
			"CREATE TABLE IF NOT EXISTS registration.session(
				id INT NOT NULL AUTO_INCREMENT,
				username VARCHAR(100) NOT NULL,
				ip CHAR(16) COLLATE utf8_bin NOT NULL,
				userAgent VARCHAR(256) NOT NULL,
				isSucess INT NOT NULL DEFAULT (0),
				timestamp timestamp NOT NULL DEFAULT NOW(),
				PRIMARY KEY(id)) DEFAULT CHARSET=utf8mb4;") or exit($db->error);

		// creates the log table
		// $db->query(
		// 	"CREATE TABLE IF NOT EXISTS registration.log(
		// 		log VARCHAR(100) NOT NULL );") or exit($db->error);
	}

	// connect to database
	$db = mysqli_connect('localhost', 'root', '', 'registration');

	// REGISTER USER
	if (isset($_POST['reg_user'])) {
		// receive all input values from the form
		$username = mysqli_real_escape_string($db, $_POST['username']);
		$email = mysqli_real_escape_string($db, $_POST['email']);
		$password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
		$password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

		// form validation: ensure that the form is correctly filled
		if (empty($username)) { array_push($errors, "Username is required"); }
		if (empty($email)) { array_push($errors, "Email is required"); }
		if (empty($password_1)) { array_push($errors, "Password is required"); }
		
		$uppercase = preg_match('@[A-Z]@', $password_1);
        $lowercase = preg_match('@[a-z]@', $password_1);
        $number    = preg_match('@[0-9]@', $password_1);
		$specialChars = preg_match('@[^\w]@', $password_1);
		
		if ($password_1 != $password_2) {
			array_push($errors, "The two passwords do not match");
		}
		
        if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
			echo "<script>
					alert ('Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.');
					window.location.href = 'register.php';
					</script>";
			exit();
        }

		// check the database if a user does not already exist
		$user_check_query = "SELECT * FROM users WHERE username='$username' OR email='$email' LIMIT 1";
		$result = mysqli_query($db, $user_check_query);
		$user = mysqli_fetch_assoc($result);

		if ($user) { // if user exists
			if ($user['username'] === $username) {
			  array_push($errors, "Username already exists");
			}
		
			if ($user['email'] === $email) {
			  array_push($errors, "email already exists");
			}
		}

		// register user if there are no errors in the form
		if (count($errors) == 0) {
			$salt = uniqid(mt_rand());
			$pass = $password_1;
			$query = "INSERT INTO users (username, email, salt, password) 
					  VALUES('$username', '$email', '$salt', MD5(salt + ':' + '$pass'));";
			mysqli_query($db, $query);

			$_SESSION['username'] = $username;
			$_SESSION['success'] = "You are now logged in";
			header('location: index.php');
		}

	}

	// LOGIN USER
	if (isset($_POST['login_user'])) {
		$username = mysqli_real_escape_string($db, $_POST['username']);
		$password = mysqli_real_escape_string($db, $_POST['password']);

		if (empty($username)) {
			array_push($errors, "Username is required");
		}
		if (empty($password)) {
			array_push($errors, "Password is required");
		}

		if (count($errors) == 0) {
			$pass = md5($password);
			$query = "SELECT * FROM users WHERE username='$username' AND password=MD5(salt + ':' + '$pass')";
			$results = mysqli_query($db, $query);

			if (mysqli_num_rows($results) == 1) {
				$_SESSION['username'] = $username;
				$_SESSION['success'] = "You are now logged in";
				header('location: index.php');
				exit();
			}
			else {
				array_push($errors, "Invald credentals");
			}

			// session timeout section
			if (!isset($_SESSION['lockout']) && !isset($_SESSION['lockoutTime'])) {
				$_SESSION['lockout'] = false;
				$_SESSION['lockoutTime'] = 0;
			}

			$username = $_POST['username'];
			$password = $_POST['password'];
			$ip = $_SERVER['REMOTE_ADDR'] ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['HTTP_CLIENT_IP']);
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			$stmt = mysqli_stmt_init($db);
			$currentTime = time();
			// session timeout lenght
			if (($currentTime - $_SESSION['lockoutTime']) < 180 && $_SESSION['lockout'] == true) {
				echo "<script>
								alert ('You are within Lockout timeframe. Please try again later.');
										window.location.href = 'login.php';
										</script>";
			} else {
				$_SESSION['lockout'] = false;
				// checking empty forms
				if (empty($username) || empty($password)) {
					header("Location: login.php");
					exit();
				} 
				else {
					$sql = "SELECT * FROM users WHERE username=?";
					if (!mysqli_stmt_prepare($stmt, $sql)) {
						header("Location: login.php");
						exit();
					} 
					else {
						mysqli_stmt_bind_param($stmt, "s", $username);
						mysqli_stmt_execute($stmt);
						$result = mysqli_stmt_get_result($stmt);
						// if result holds a value
						if ($row = mysqli_fetch_assoc($result)) {
							// if passwords don't match
							$passwordCheck = password_verify($password, $row['password']);
							if ($passwordCheck == false) {
								// store and check logins
								$sql = "INSERT INTO `session`(`username`, `ip`, `userAgent`) VALUES (?,?,?)";
								if (mysqli_stmt_prepare($stmt, $sql)) {
									mysqli_stmt_bind_param($stmt, "sss", $username, $ip, $userAgent);
									mysqli_stmt_execute($stmt);
								}
								$result = mysqli_query($db, "SELECT COUNT(*) FROM `session` WHERE 
													`ip` LIKE '$ip' 
												AND `userAgent` LIKE '$userAgent'
												AND `isSucess` LIKE 0
												AND `timestamp` > DATE_SUB(NOW(), INTERVAL 3 MINUTE);");
								$_SESSION['count'] = mysqli_fetch_array($result, MYSQLI_NUM);
								// if over 5 attempts
								if ($_SESSION['count'][0] > 5) {
									// user is locked out, set Lockout to true and record lockout time
									$_SESSION['lockout'] = true;
									$_SESSION['lockoutTime'] = time();
									echo "<script>
										alert ('You are locked out.');
												window.location.href = 'login.php';
												</script>";
								}
								// else, if user is not locked out
								else {
									if ($_SESSION['count'][0] != 5) {
										echo "You have " . (5 - $_SESSION['count'][0]) . " attempts remaining";
									}
								}
							} 
							else {
								// create session
								session_start();
								$_SESSION['id'] = $row['id'];
								$_SESSION['username'] = $row['username'];
								header("Location: index.php");
							}
						}
					}
				}
			}

		}

	}


	// CHANGE PASSWORD
	if (isset($_POST['pass_reset'])) { 
		// receive all input values from the form
		$password_cur = mysqli_real_escape_string($db, $_POST['password_cur']);
		$password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
		$password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

		// form validation: ensure that the form is correctly filled
		if (empty($password_cur)) {
			array_push($errors, "Current password is required");
		}
		if (empty($password_1)) {
			array_push($errors, "New password is required"); 
		}
		if (empty($password_2)) {
			array_push($errors, "Matching new password is required");
		}
		if ($password_1 != $password_2) {
			array_push($errors, "The two passwords do not match");
		}

		if (count($errors) == 0) {

			if ($_SERVER['REQUEST_METHOD'] == 'GET') {
				// gets the new pass and checks for updated pass
				if ($_GET['newpassword'] == $_GET['confirmpassword']) {
					
					$salt = "salt"; // basic salt testing, need random generadted
					$password_cur = md5($salt.$_GET['newpassword']);
					$query = "SELECT * FROM users WHERE username='$username' and password='$password'";
					$results = mysqli_query($db, $query);
	
					if (mysqli_num_rows($results) == 1 && $password_cur == $password) {
						$password = md5($password_1);
						$sql = "UPDATE users SET password='$password', where username='$username'";
						if ($db->query($sql) == 1) {
							$_SESSION['username'] = $username;
							$_SESSION['success'] = "Password updated";
							header('location: index.php');
						}
					}
					else {
						$_SESSION['message'] = "Password did not match";
						header("location: index.php");
					}
				}
				
			}

			$_SESSION['username'] = $username;
			$_SESSION['success'] = "Password changed";
			header('location: index.php');
		}

	}

?>