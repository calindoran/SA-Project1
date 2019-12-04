<?php include 'server.php'; // only used for production = require_once 'header.php'; 

	if (!isset($_SESSION['username'])) {
        session_destroy();
		$_SESSION['msg'] = "You must log in first";
		header('location: login.php');
    }
    
?>
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
		<h2>Reset password</h2>
	</div>

	<form method="post" action="resetpass.php">

		<?php include('errors.php'); ?>

        <p>Welcome <strong><?php echo $_SESSION['username']; ?></strong></p>
		<br>

		<div class="input-group">
			<label>Current Password</label>
			<input type="password" name="password_cur">
		</div>
		<div class="input-group">
			<label>New Password</label>
			<input type="password" name="password_1">
		</div>
		<div class="input-group">
			<label>Confirm New Password</label>
			<input type="password" name="password_2">
		</div>
		<div class="input-group">
			<button type="submit" class="btn" name="pass_reset">Reset</button>
		</div>
		<p><a href="index.php">Cancel</a></p>
	</form>
</body>

</html>