<?php
//建立全局变量存储路径
$session_path = '';

function open($path,$name){
	global $session_path;

	$session_path =rtrim($path,'/').'/';
	return true;
}

function close(){
	return true;
}

function read($sess_name){
	global $session_path;

	$filename = $session_path.'session_'.$sess_name;

	@$result=file_get_contents($filename);

	if($result){
		return $result;
	}
	return '';
}

function write($sess_name,$value){
	global $session_path;

	$filename = $session_path.'session_'.$sess_name;
	if(file_put_contents($filename,$value)){
		return true;
	}
	return false;
}

function destroy($sess_name){
	global $session_path;

	$filename = $session_path.'session_'.$sess_name;

	return @unlink($filename);
}

function gc($maxLifeTime){
	global $session_path;
	$files = glob($session_path.'session_*');
	foreach($files as $file){
		if(time()-fileatime($file) > $maxLifeTime){
			@unlink($file);
		}

	}
	return true;
}
session_set_save_handler('open','close','read','write','destroy','gc');
session_start();