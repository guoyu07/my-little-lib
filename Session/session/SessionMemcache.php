<?php

/**
 * 把session保存在memcache数据库中
 * Class SessionMemcache1
 */
class SessionMemcache{

	//实例化的memcache
	private static $memcache ;
	private static $prefix = 'sess_';

	//这里是session_start()执行的时候调用的方法
	public static function open($save_path, $session_id)
	{
		self::$memcache = new Memcache();
		self::$memcache->connect('127.0.0.1',11211);

		return true;
	}

	public static function read($session_id)
	{

		$keyname = self::$prefix.$session_id;
		$result =self::$memcache->get($keyname);
		if($result){
			return $result;
		}
		return false;
	}

	public static function write($session_id, $session_data)
	{

		$keyname = self::$prefix.$session_id;

		if(self::$memcache->set($keyname,$session_data)){
			return true;
		}
		return false;
	}

	public static function close()
	{
		return true;
	}

	public static function destroy($session_id)
	{
		$keyname = self::$prefix.$session_id;
		return @self::$memcache->delete($keyname);
	}

	public static function gc($maxlifetime)
	{
		return true;
	}
}
/*--------------------------------------------------------------------------------------------------
 * 静态调用方法1
session_set_save_handler(
	array('SessionMemcache','open'),
	array('SessionMemcache','close'),
	array('SessionMemcache','read'),
	array('SessionMemcache','write'),
	array('SessionMemcache','destroy'),
	array('SessionMemcache','gc')

);*/

class SessionMemcache2 implements SessionHandlerInterface{

	//实例化的memcache
	private $memcache ;
	private $prefix = 'sess_';

	public function open($save_path, $session_id)
	{
		$this->memcache = new Memcache();
		$this->memcache->connect('127.0.0.1',11211);

		return true;
	}

	public function read($session_id)
	{
		$keyname = $this->prefix.$session_id;
		$result = $this->memcache->get($keyname);
		if($result){
			return $result;
		}
		return false;
	}

	public function write($session_id, $session_data)
	{
		$keyname = $this->prefix.$session_id;

		if($this->memcache->set($keyname,$session_data)){
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
		$keyname = $this->prefix.$session_id;
		return @$this->memcache->delete($keyname);
	}

	public function gc($maxlifetime)
	{
		return true;
	}
}
// PHP5.4中引入的新的调用方法
session_set_save_handler(new SessionMemcache2);
session_start();