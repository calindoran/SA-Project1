<?php include 'server.php' ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset='utf-8'>
	<meta http-equiv='X-UA-Compatible' content='IE=edge'>
	<meta name='description' content='This is the webpage to document project1 for secure apps.'>
	<title>SecureApps - Project1 - Calin Doran - 2019</title>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
	<link href='https://fonts.googleapis.com/css?family=Open Sans' rel='stylesheet'>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>

	<div class="header">
		<h2>Login</h2>
	</div>

	<form method="post" action="login.php">

		<?php include('errors.php'); ?>

		<div class="input-group">
			<label>Username</label>
			<input type="text" name="username">
		</div>
		<div class="input-group">
			<label>Password</label>
			<input type="password" name="password">
		</div>
		<div class="input-group">
			<button type="submit" class="btn" name="login_user">Login</button>
		</div>
		<p>
			Dont have an account? <a href="register.php">Sign up</a>
		</p>
	</form>

</body>

</html>