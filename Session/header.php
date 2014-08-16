<?php 
	//-------------------存储在文件中-------------------------------------------------
	//第一种方法 面向过程的方法
	//include './session/SessionFile1.php';
	//第二种方法(只适合PHP5,4+)面向对象实现session接口的方法
	include './session/SessionFile2.php';

	//-------------------存储在memcache中--------------------------------------------
	//include './session/SessionMemcache.php';

	//-------------------存储在数据库中-----------------------------------------------
	//include './session/SessionMysql.php';
?>