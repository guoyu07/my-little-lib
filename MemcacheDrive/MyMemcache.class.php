<?php
/**
 *
 *	简单的封装了memcache
 *	@author tangtang1251
 *
 */
class MyMemcache{

	private $host		= null; //主机地址
	private $memcache   = null;	//memcache对象
	private $post ;				//端口地址
	public $prefix      = '';	//前缀
	private $lastUseKey = '';	//最后操作的key

	public function __construct($host,$post = '11211',$frefix = 'MyMemcache_',$serverlist = ''){
		//获取参数
		$this->host = $host;
		$this->post = $post;
		$this->prefix = $frefix;

		//实例化对象
		$this->memcache = new Memcache();

		//连接服务器
		$this->memcache->connect($this->host,$this->post);

		if (!$this->memcache) {
			exit("连接memcache服务器失败");
		}

		//如果有多组服务器，接收参数并添加到连接池
		if(!empty($serverlist)){
			$this->addServer($serverlist);
		}
	}

	public function __get($method){
		if($method == 'lastUseKey'){
			return $this->$method;
		}
		return false;
	}


	/**
	 * 对键的名字进行标准化处理
	 * 一个标准的键值名称的组成
	 *
	 *      前缀_数据分类名:数据键名
	 *
	 * 此方法可以接受数组，自动把数组元素转换成标准名称。
	 * 使用方法：formaetKey(['数据分类名','数据键名']);
	 * 被格式化的名字会变成 前缀_分类名:键名 的格式
	 * 如果直接传字符串，则会直接加上前缀返回。
	 * @param string $key
	 * @return bool|string
	 */
	public function formatKey($key = ''){
		//如果参数为空，则直接返回最后一次操作的键名
		if(empty($key)){
			if(!empty($this->lastUseKey)){
				return $this->lastUseKey;
			}
			//如果没有操作过键名，而且也没有参数，则返回false
			return false;
		}else{

			if(is_string($key)){
				//如果用字符串，这直接使用这个键名
				$this->lastUseKey = $this->prefix.$key;
			}else{
				//如果用数组，则表示使用标准格式键名
				$newKey = '';
				foreach($key as $value){
					$newKey .= $value.':';
				}
				$this->lastUseKey = $this->prefix.rtrim($newKey,':');
			}

			//返回格式化好的键名
			return $this->lastUseKey;
		}
	}

	//添加数据
	public function set($key, $value, $expire = 0){
		if(is_numeric($expire) && $expire >=0 && $expire < 2592000){
			return $this->memcache->set($this->formatKey($key), $value, 0, $expire);
		}else{
			return '时间设置错误';
		}
	}

	//获取数据
	public function get($key){
		return $this->memcache->get($this->formatKey($key));
	}

	//删除数据
	public function delete($key,$timeout = '0'){
		if(is_numeric($timeout) && $timeout >=0 && $timeout < 2592000){
			return $this->memcache->delete($this->formatKey($key),$timeout);
		}else{
			return '时间设置出错';
		}

	}

	//清空数据
	public function clear(){
		$this->memcache->flush();
	}

	/**
	 * 支持单台服务器添加和批量服务器添加
	 * @param Array $serverList 数组第一个元素是服务器IP，第二个元素是服务器的端口
	 */
	public function addServer($serverList){

		if(is_array($serverList[0])){
			foreach($serverList as $value){
				$this->memcache->addserver($value[0],$value[1]);
			}
		}else{
			$this->memcache->addserver($serverList[0],$serverList[1]);
		}
	}

	//元素的值增加
	public function up($key,$value = 1){
		return $this->memcache->increment($this->formatKey($key),$value);
	}

	//元素的值减少
	public function down($key,$value = 1){
		return $this->memcache->decrement($this->formatKey($key),$value);
	}

	/**
	 * 查看memcache的服务器信息
	 * @param bool $show 是否显示值
	 * @param int $sub  显示哪个分区的值
	 * @param int $limit 显示多少个值
	 */
	public function cacheDump($show = false,$sub = 1,$limit = 100){

		//获取服务的分区信息
		$slabs = $this->memcache->getExtendedStats('slabs');

		//统计分区信息
		foreach($slabs as $key => $value){

			if($value){
				echo '<table rel="'.$key.'" style="border: 1px #aaa solid;">';
				echo '<caption style="border: 1px #555 solid;">服务器：'.$key.'-活动分区数：'.$value['active_slabs'].'-申请内存数：'.$value['total_malloced'].'</caption>';

				unset($value['active_slabs']);
				unset($value['total_malloced']);
				foreach($value as $k => $v){
					echo "<tr><td>分区:{$k}。</td><td>分区尺寸(chunk_size):</td><td>{$v['chunk_size']}。</td><td>分区页尺寸(chunks_per_page):</td><td>{$v['chunks_per_page']}。</td><td>总页数(total_pages):</td><td>{$v['total_pages']}。</td>";
					echo "<td>数据量(total_chunks):</td><td>{$v['total_chunks']}。</td><td>在用量(used_chunks):</td><td>{$v['used_chunks']}。</td><td>释放量(free_chunks):</td><td>{$v['free_chunks']}。</td></tr>";
				}
				echo '</table>';
			}else{
				echo '<table rel="'.$key.'" style="border: 1px #aaa solid;">';
				echo '<caption style="border: 1px #555 solid;">服务器：'.$key.'-离线</caption>';
			}
		}

		//显示数据库的数据
		if($show){
			$data = $this->memcache->getExtendedStats('cachedump',$sub,$limit);

			foreach($data as $k => $v){
				if($v){
					echo '<table rel="'.$k.'" style="border: 1px #aaa solid;float:left">';
					echo '<caption style="border: 1px #555 solid;">服务器：'.$k.'</caption>';
					echo '<tr><th>键名</th><th>键值</th><th>长度</th><th>到期时间</th></tr>';
					foreach($v as $kk => $vv){
						$keyData = $this->memcache->get($kk);
						echo "<tr><td>$kk</td><td>$keyData</td><td>$vv[0]</td><td>".date('Y/m/d H:i:s',$vv[1])."</td></tr>";
					}
					echo '</table>';
				}
			}
		}

	}
}