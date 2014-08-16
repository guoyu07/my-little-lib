<?php
/**
 *
 *  MySql数据库类
 *
 *
 *
 *
 */

class MysqlDrive{

	private $host           = '';               //数据库主机IP
	private $user           = '';               //数据库账号
	private $password       = '';               //账号的密码
	private $dbName         = '';               //数据库名
	private $charset        = '';               //字符集
	public $cache           = './Cache/table';  //表信息缓存位置
	private $prefix         = '';               //表前缀

	private $link           = '';               //连接资源名称

	private $tabName        = '';               //表名称
	private $field          = '';               //表字段信息
	private $sql            = '';               //最后运行的那条SQL语句
	private $error          = '';               //错误信息


	public function __construct($table = ''){

		//初始化配置变量
		$this->host=DB_HOST;
		$this->user=DB_USER;
		$this->password=DB_PASS;
		$this->charset=DB_CHARSET;
		$this->dbName=DB_NAME;
		$this->prefix=DB_PREFIX;

		//初始化表名
		if(!empty($table)){
			$this->tabName = $this->prefix.$table;
		}else{
			$this->tabName = $this->prefix.strtolower(substr(get_class($this),0,-5));
		}

		//初始化数据库连接
		$this->link = $this->connect();
		//初始化合法字段信息
		$this->field = $this->getField();

	}

	//数据库连接
	private function connect(){

		//连接数据库
		$conn = mysql_connect($this->host,$this->user,$this->password);

		//判断是否成功连接
		if(mysql_errno($conn)){
			return false;
		}

		//设置要操作的数据库
		mysql_select_db($this->dbName,$conn);

		//设置字符集
		mysql_set_charset($this->charset,$conn);
		return $conn;
	}

	/**
	 * 获取表中的字段信息
	 * 从两个方面获取，如果有表信息缓存，则从缓存中获取
	 * 如果没有表信息缓存，这先从数据库中读取信息，并且读取后在缓存文件夹中存储。
	 * @return array|mixed
	 */
	private function getField(){
		$cache = $this->cache.'/'.$this->tabName.'.cache.php';
		if(file_exists($cache)){
			$f = include $cache;
		}else{
			$sql="DESC ".$this->tabName;

			$data=$this->query($sql);

			$f = $this->writeField($data);
		}
		return $f;
	}

	/**
	 * 添加数据操作 insert
	 * 如果添加成功，返回字段自增值
	 * @param array $data 接收一个以键为字段名，值为字段值的数组
	 * @return bool|int
	 */
	public function insert($data){
		$field = '';
		$values = '';
		foreach ($data as $key => $value) {
			if(!in_array($key,$this->field)){
				$this->error = '不合法的字段名';
				return false;
			}
			$field .= '`'.$key.'`,';
			$values .= "'".$value."',";
		}
		$field = rtrim($field,',');
		$values = rtrim($values,',');

		$this->sql = "insert into {$this->tabName}({$field}) values({$values})";

		return $this->exec($this->sql);

	}

	/**
	 * 修改数据操作 update
	 * @param array $data   接收一个以键为字段名，值为字段值的数组
	 * @param string $where 接收where条件
	 * @param string $limit 接收限制行数
	 * @param string $order 排序规则
	 * @return bool|int
	 */
	public function update($data,$where = '',$limit = '',$order = ''){
		$values = '';

		if(!empty($where)){
			$where = 'where '.$where;
		}
		if(!empty($limit)){
			$limit = 'limit '.$limit;
		}
		if(!empty($order)){
			$order = 'order by '.$order;
		}
		foreach ($data as $key => $value) {
			if(!in_array($key,$this->field)){
				$this->error = '不合法的字段名';
				return false;
			}
			$values .= "`".$key."`='".$value."',";
		}
		$values = rtrim($values,',');

		$this->sql = "update {$this->tabName} set {$values} {$where} {$order} {$limit}";

		return $this->exec($this->sql);
	}

	/**
	 * //删除数据操作 delete
	 * @param $where        //删除的条件
	 * @param string $limit 删除影响的行数
	 * @param string $order 排序规则
	 * @return bool|int
	 */
	public function delete($where,$limit = '1',$order = ''){
		//delete form table where order limit
		if(!empty($where)){
			$where="where {$where}";
		}

		if($limit){
			$limit="limit {$limit}";
		}else{
			$limit = '';
		}

		if(!empty($order)){
			$order = "order by {$order}";
		}

		$this->$sql="delete from {$this->tabName} {$where}  {$order} {$limit}";

		return $this->exec($this->sql);
	}

	//单数据查询 find
	public function find($where,$field = '*'){
		$this->sql = "select {$field} from {$this->tabName} where {$where} limit 1";
		return $this->query($this->sql);
	}

	//多数据查询 select
	public function select($where,$field = '*',$limit = '',$order = ''){
		if($where){
			$where = 'where '.$where;
		}
		if(!empty($limit)){
			$limit = "limit {$limit}";
		}
		if(!empty($order)){
			$order = "order by {$order}";
		}
		$this->sql = "select {$field} from {$this->tabName} {$where} {$order} {$limit}";
		return $this->query($this->sql);
	}


	//--------------------------封装方法-------------------------------------
	//执行查询类操作 query
	private function query($sql){
		$result = mysql_query($sql);
		if($result && mysql_affected_rows() > 0){
			while($row = mysql_fetch_assoc($result)){
				$data[] = $row;
			}
			return $data;
		}
		$this->error = mysql_error();
		return false;
	}

	//执行增删改操作 exec
	private function exec($sql){
		$result = mysql_query($sql);
		if(strtolower(substr($sql,0,6)) == 'insert' && mysql_insert_id() > 0){
			return mysql_insert_id();
		}else{
			if($result && mysql_affected_rows() > 0){
				return mysql_affected_rows();
			}elseif($result){
				$this->error = '没有影响行数';
				return false;
			}
		}
		$this->error = mysql_error();
		return false;

	}

	private function writeField($data){

		//获取缓存文件的文件名
		$cache = $this->cache.'/'.$this->tabName.'.cache.php';

		//开始循环整理数据
		foreach ($data as $value) {

			//获取主键
			if($value["Key"] == 'PRI'){
				$fileds['_pk']=$value['Field'];
			}

			//获取自增字段
			if($value['Extra'] == 'auto_increment'){
				$fileds['_autoUp']=$value['Field'];
			}

			//获得数组
			$fileds[]=$value['Field'];
		}
		if(!file_exists($cache)){
			if(!is_dir($this->cache)){
				mkdir($this->cache,0777,true);
			}
			file_put_contents($cache,"<?php \n    return ".var_export($fileds,true)."\n?>");
		}
		

		return $fileds;
	}

	public function __get($property){

		if($property == 'sql' || $property == 'error'){
			return $this->$property;
		}
		return false;
	}

}