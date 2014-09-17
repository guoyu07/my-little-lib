<?php 
include 'header.php';

if(!$_SESSION['islogin']){
	header("location:test.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>user</title>
</head>
<body>
	<table>
		<tr>
			<td>用户名：</td>
			<td><?php echo $_SESSION['username']?></td>
		</tr>
		<tr>
			<td>密码：</td>
			<td><?php echo $_SESSION['password']?></td>
		</tr>
		<tr>
			<td></td>
			<td><a href="logout.php">退出登陆</a></td>
		</tr>
	</table>
</body>
</html>
