<?php
include 'header.php';
	if($_SESSION['islogin']){
		//删除cookie信息
		setCookie(session_name(),'',time()-1,'/');
		//删除session信息
		$_SESSION=array();
		//注销session文件
		@session_destroy();
	}
	header("location:test.php");

?>