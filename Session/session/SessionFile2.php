<?php

/**
 * bool session_set_save_handler ( SessionHandlerInterface $sessionhandler [, bool $register_shutdown = true ] )
 * 此类只适用于PHP5.4以上的版本
 * Class SessionFile
 */
class SessionFile implements SessionHandlerInterface{

	//session 保存目录的名称
	private $savePath;
	private $prefix = 'sess_';

	//这里是session_start()执行的时候调用的方法
	public function open($save_path, $session_id)
	{
		$this->savePath = rtrim($save_path,'/').'/';
		if(!file_exists($this->savePath.$this->prefix.$session_id)){
			file_put_contents($this->savePath.$this->prefix.$session_id,'');
		}
		return true;
	}

	public function read($session_id)
	{
		$filename = $this->savePath.$this->prefix.$session_id;
		$result =@file_get_contents($filename);
		if($result){
			return $result;
		}
		return false;
	}

	public function write($session_id, $session_data)
	{
		$filename = $this->savePath.$this->prefix.$session_id;

		if(file_put_contents($filename,$session_data)){
			return true;
		}
		return false;
	}

	public function close()
	{
		return true;
	}

	public function destroy($session_id)
	{
		$filename = $this->savePath.$this->prefix.$session_id;
		return @unlink($filename);
	}

	public function gc($maxlifetime)
	{
		$files = glob($this->savePath.$this->prefix.'*');
		foreach($files as $file){
			if(time()-fileatime($file) > $maxlifetime){
				@unlink($file);
			}

		}
		return true;
	}
}
session_set_save_handler(new SessionFile());
session_start();