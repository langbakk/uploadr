<?php
$username = (isset($_POST['username'])) ? $_POST['username'] : '';
$password = (isset($_POST['password'])) ? $_POST['password'] : '';

if (isset($_POST['submit_login']) && $username != '' && $password != '') {

	if (in_array_recursive($username,$user_array) && in_array_recursive($password,$user_array)) {
		$_SESSION['loggedin'] = true;
		header('refresh: 0');
	}

} elseif (isset($_POST['submit_logout'])) {
		session_destroy();
		session_unset();
		header('refresh: 0');
}

if ((isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == false) || !isset($_SESSION['loggedin'])) {
?>
<form id="loginform" method="post" action="index.php">
	<input type="text" id="username" name="username" value="<?php echo $username; ?>" placeholder="Please input your username (email)">
	<input type="password" id="password" name="password" value="<?php echo $password; ?>" placeholder="Please input your password">
	<input type="submit" name="submit_login" value="Login">
</form>
<?php } else { ?>
<form id="loginform" method="post" action="index.php">
	<input type="submit" name="submit_logout" value="Log out">
</form>
<?php } ?>