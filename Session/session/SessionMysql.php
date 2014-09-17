<?php

/**
 * 把session保存在Mysql数据库中
 * Class SessionMysql
 *
 */
class SessionMysql implements SessionHandlerInterface{

	//实例化的mysql
	private $mysql;
	private $prefix = 'sess_';
	private $ip;
	private $timeout;

	public function __construct(){
		$this->mysql = @mysql_connect('127.0.0.1','root','');
		mysql_select_db('test',$this->mysql);
		mysql_set_charset('utf8',$this->mysql);

		//过期时限
		$this->timeout = ini_get('session.gc_maxlifetime');

		//记录IP
		$this->ip = ip2long($_SERVER['REMOTE_ADDR']);
	}

	//这里是session_start()执行的时候调用的方法
	public function open($save_path, $session_id)
	{
		return true;
	}

	public function read($session_id)
	{
		$keyname = $this->prefix.$session_id;

		$minTime = time() - $this->timeout;

		//查询键的值，并且是非过期的。
		$result = mysql_query("SELECT value FROM session "
			."WHERE `key`='".$keyname."' and time>=$minTime",$this->mysql);
		if($result && mysql_affected_rows() > 0){
			$row = mysql_fetch_assoc($result);
			return $row['value'];
		}
		return false;
	}

	public function write($session_id, $session_data)
	{
		$keyname = $this->prefix.$session_id;

		//这里必须要判断$session_data是否有值
		if(!empty($session_data)){

			//判断是否有键
			$query = mysql_query("SELECT value,time FROM session WHERE key='".$keyname."'",$this->mysql);
			$data = mysql_fetch_assoc($query);
			//有键 用更新，没键 用插入
			if($query && mysql_affected_rows() > 0){

				//没超时则更新并且刷新时间，超时则删除该条并返回false
				if(time() - $this->timeout < $data['time']){
					mysql_query("update session set "
						."`valye`='{$session_data}',`time`=".time()
						." where key='{$keyname}' and ip={$this->ip}",$this->mysql);
				}else{
					$this->destroy($session_id);
					return false;
				}
			}else{
				mysql_query("INSERT INTO session(`key`,`value`,`ip`,`time`)"
					."VALUES('{$keyname}','{$session_data}',{$this->ip},".time().")",$this->mysql);
			}

			return mysql_affected_rows() > 0 ? true : false;
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
		mysql_query("DELETE FROM session "
			."WHERE `key`='{$keyname}'");
		return mysql_affected_rows() > 0 ? true : false;
	}

	//清理过期
	public function gc($maxlifetime)
	{
		$desTime = time() - $this->timeout;
		mysql_query("DELETE FROM session "
			."WHERE `time`<'{$desTime}'");
		return true;
	}
}
session_set_save_handler(new SessionMysql());
session_start();