<?php
include 'header.php';

	if($_GET['type'] == 'login'){
		if(!empty($_POST['user']) && !empty($_POST['pass'])){
			$_SESSION['islogin'] = true;
			$_SESSION['username'] = $_POST['user'];
			$_SESSION['password'] = $_POST['pass'];
			header("Location:user.php");
		}
	}
?>


<!DOCTYPE html>
<html lang="zh">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
	<style>
		td{
			border-bottom:1px #bbb solid;
		}
	</style>
</head>
<body>
	<form action="?type=login" method="post">
		<table>
		<tr>
			<td><label for="">用户名：</label></td>
			<td><input type="text" name="user" ></td>
		</tr>
		<tr>
			<td><label for="">密码：</label></td>
			<td><input type="text" name="pass"></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="登录"></td>
		</tr>
	</table>
	</form>
</body>
</html>