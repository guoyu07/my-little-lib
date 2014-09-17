<?php 
CLASS PDODrive{
	/**
	 * 数据库属性
	 */
	protected $_dsn = '';             //data source name 数据源名称
	protected $_dbDrive = '';         //数据驱动
	protected $_host = '';            //服务器地址
	protected $_user = '';            //授权账号
	protected $_pass = '';            //账号的密码
	protected $_dbname = '';          //要操作的数据库
	protected $_charset = '';         //字符集

	/**
	 * 表模型属性
	 */
	protected $pdo = NULL;            //PDO对象
	protected $tabName = '';          //表名
	protected $_pk = 'id';            //主键
	protected $_autoField = 'id';     //自增键
	protected $_map = '';             //字段映射
	public $toMap = false;            //把查询出来的数据字段名修改为映射名
	protected $fileds = array();      //全部字段属性
	protected $_prefix = '';          //表前缀
	public $cache = './Cache/table';  //表缓存的位置

	/**
	 * 运行状态属性
	 */
	protected $sql = '';              //最近一次运行的sql语句
	protected $lastInsertId = array();//上一次插入操作返回的自增值
	protected $error = array();       //一个可追溯的报错信息

	public function __construct($table=''){

		//获取参数
		$this->_dbDrive = DB_DRIVE;
		$this->_host = DB_HOST;
		$this->_user = DB_USER;
		$this->_pass = DB_PASS;
		$this->_dbname = DB_NAME;
		$this->_charset = DB_CHARSET;
		$this->_prefix = DB_PREFIX;
		$this->_dsn = "{$this->_dbDrive}:host={$this->_host};dbname={$this->_dbname};charset={$this->_charset}";

		//获取表名
		if(empty($table)){
			//如果没有设置表名则直接使用类名中的信息,模型类的格式：xxxxModel
			$this->tabName = $this->_prefix.strtolower(substr(get_class($this),0,-5));
		}else{
			$this->tabName = $this->_prefix.$table;
		}

		//连接数据库
		try{
			$this->pdo = new PDO($this->_dsn,$this->_user,$this->_pass);
		}catch (PDOException $e){
			echo '啊哦，连接数据库出错了:'.$e->getMessage();
			exit;
		}


		//处理表信息并缓存表信息
		$this->getField();

		//设置的表映射
		$this->addMap();
	}

	public function __set($property,$value){
		if($property == ''){

		}
	}

	public function __get($property){
		if($property == 'lastInsertId' || $property = 'error' || $property == 'sql'){
			return $this->$property;
		}
		return '封装属性，无法查看';
	}

	/**
	 *  获取表属性
	 *  1、缓存文件中有没有表属性的信息
	 *  2、如果没有缓存文件，这从数据库读取，并写入缓存
	 *
	 */
	private function getField(){
		$cache = $this->cache.'/'.$this->tabName.'.cache.php';

		//判断缓存文件夹是否存在，如果不存在将建立，建立失败将报错
		if(!file_exists($this->cache) || !is_writable($this->cache)){
			//如果路径不存在(写了路径但是没文件夹)
			if(!mkdir($this->cache,0777,true)){
				//设置失败信息  返回结果
				$this->setError('-1','创建缓存文件夹失败');
				return false;
			}
		}

		//判断是否有缓存文件
		if(file_exists($cache)){
			//载入缓存文件并赋值给一个变量
			$cacheFile = include $cache;

			//把缓存中的_pk数据 给成员属性_pk
			$this->_pk = $cacheFile['_pk'];
			unset($cacheFile['_pk']);

			//把缓存中的_autoField 给成员属性_autoField
			$this->_autoField = $cacheFile['_autoField'];
			unset($cacheFile['_autoField']);

			//把剩下的字段数组 给成员属性fileds
			$this->fileds = $cacheFile;
		}else{
			//获取表属性
			$stmt = $this->pdo->query('desc '.$this->tabName);

			//存入文件的内容
			$forFile = '';

			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

				//获取主键字段名
				if($row['Key'] == 'PRI'){
					$this->_pk = $row['Field'];
					$forFile['_pk'] = $row['Field'];
				}

				//获取自增字段名
				if($row['Extra'] == 'auto_increment'){
					$this->_autoField = $row['Field'];
					$forFile['_autoField'] = $row['Field'];
				}

				//去掉不必要的的属性
				unset($row['Key']);
				unset($row['Extra']);
				unset($row['Type']);
				unset($row['Default']);

				$forFile[] = $row;

				//存储在$this->fileds中
				$this->fileds[] = $row;
			}
			//
			file_put_contents($cache,"<?php \n    return ".var_export($forFile,true)."\n?>");
		}
		return true;
	}

	/**
	 *  执行字段的映射
	 *
	 */
	private function addMap(){
		//临时字段数组
		$field_tmp = array();

		//查看字段映射属性是否为数组
		if(is_array($this->_map)){

			//添加一个统计变量
			$count = 0;
			foreach($this->fileds as $value){

				//在_map中搜索是否有这个字段名
				$mapKey = array_search($value['Field'],$this->_map);
				if($mapKey){
					//如果有，则给字段设置别名
					$field_tmp[$mapKey] = $value;

					//统计变量加一
					$count++;
				}else{
					//如果没有，那么它的别名就是他自己的字段名
					$field_tmp[$value['Field']] = $value;
					$this->setError('0','提示：'.$value['Field'].'字段没有设置别名，请用字段名操作');
				}
			}

			//如果统计变量的数量和_map的总数不对，说明_map中有错误的字段
			if($count != count($this->_map)){
				$this->setError('-1','在别名数组中有'.(count($this->_map) - $count).'个错误的字段映射');
			}
		}else{
			//如果完全没有设置别名则所有的Map都用字段名
			foreach($this->fileds as $value){
				$field_tmp[$value['Field']] = $value;
			}
			$this->setError('0','提示：没有设置任何别名,请用字段名操作');
		}

		$this->fileds = $field_tmp;
	}

	//在查询之前，把字段名替换成设置的映射名称
	private function fieldToMap($field){
		//接收新field的变量
		$newField = '';

		//如果是查询所有字段，则全部字段转换
		if($field == "*"){
			foreach($this->fileds as $k => $v){
				$newField .= "{$v['Field']} as {$k},";
			}
			$newField = rtrim($newField,',');
		}else{
			//如果是查询部分字段，则部分字段转换
			$arr = explode($field,',');
			foreach($this->fileds as $k => $v){
				if(in_array($v['Field'],$arr)){
					$newField .= "{$v['Field']} as {$k},";
				}
			}
			$newField = rtrim($newField,',');
		}
		return $newField;
	}

	//设置错误信息
	private function setError($errorCode,$errorMsg){
		$this->error[] = array('errorCode' => $errorCode,'errorMsg' => $errorMsg);
	}


	/**
	 * --------------------------外部接口---------------------------------------------------
	 */

	//执行query功能
	public function query($where = '',$field = '*',$limit = '',$order = ''){
		if(!empty($where)){
			$where = 'where '.$where;
		}
		if(!empty($limit)){
			$limit = 'limit '.$limit;
		}
		if(!empty($order)){
			$order = 'order by '.$order;
		}

		$this->sql = "select {$field} from {$this->tabName} {$where} {$order} {$limit}";
		$result = $this->pdo->query($this->sql);
		if($result){
			return $result->fetchAll(PDO::FETCH_ASSOC);
		}
		$this->setError($this->pdo->errorCode(),'语句执行失败：'.$this->pdo->errorInfo()[2]);
		return false;
	}


	/**
	 * 把数组转换成可操作的数组格式
	 * 1、关联数组   所有数据都要一一对应
	 *      array(
	 *          '字段1的别名' => array('值1','值2'),
	 *          '字段2的别名' => array('值1','值2'),
	 *          ......
	 *      )
	 * 2、索引数组
	 *      array(
	 *          0 => array('字段1的别名' => '值1', '字段2的别名' => '值1'),
	 *          1 => array('字段1的别名' => '值2', '字段2的别名' => '值2'),
	 *          ........
	 *      )
	 *
	 * 不管是批量添加还是单个添加都会转换成 索引数组方式
	 */
	private function transData($data){
		$newData = array();
		if(!isset($data[0])){
			//转换开始
			foreach($data as $key => $value){
				if(is_array($value)){
					$i = 0;
					foreach($value as $v){
						$newData[$i][$key] = $v;
						$i++;
					}
				}else{
					//如果不是索引数组，并且字段名也不是数组，就会被判定位单数据插入
					$newData[] = $data;
					break;
				}
			}
		}
		return $newData;
	}


	/**
	 * 添加数据功能
	 * 支持批量添加,批量添加有两种格式(详见transData方法)
	 * @param array $data
	 * @return bool
	 */
	public function insert($data){

		//建立要用的变量，映射名作为预处理预留位 字段名作为要用的字段名
		$f = '';
		$p = '';

		//判断是否是索引数组，如果不是，就转换成索引数组
		$newData = $this->transData($data);

		//组合一个预处理语句
		foreach($newData[0] as $k => $v){
			$f .= '`'.$this->fileds[$k]['Field'].'`,';
			$p .= ':'.$k.',';
		}
		$f = rtrim($f,',');
		$p = rtrim($p,',');

		$this->sql = "INSERT INTO {$this->tabName}($f) VALUES($p)";
		$stmt = $this->pdo->prepare($this->sql);

		//执行插入
		for($i =0; $i< count($newData); $i++){
			if($stmt->execute($newData[$i])){
				$this->lastInsertId[] = $this->pdo->lastInsertId();
			}else{
				$this->setError($stmt->errorInfo()[0],'插入出错，程序停止，已成功插入'.$i.'行：'.$stmt->errorInfo()[2]);
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 修改数据功能
	 * 不支持批量修改
	 *
	 * @param array $data 要修改的数据
	 * @param string $where where判断
	 * @param int|string $limit 操作最大影响行数
	 * @param string $order 排序
	 * @return bool|int
	 */
	public function update($data,$where,$limit = '',$order = ''){
		//建立要用的变量，映射名作为预处理预留位 字段名作为要用的字段名
		$f = '';

		//判断可选参数
		if($limit != ''){
			$limit = 'limit '.$limit;
		}
		if($order != ''){
			$order = 'order by '.$order;
		}

		//组合一个预处理语句
		foreach($data as $k => $v){
			$f .= "`{$this->fileds[$k]['Field']}`='{$v}',";
		}
		$f = rtrim($f,',');

		$this->sql = "UPDATE {$this->tabName} SET {$f} WHERE {$where} {$order} {$limit}";
		$stmt = $this->pdo->prepare($this->sql);
		$stmt->execute();
		if($stmt->rowCount()){
			return $stmt->rowCount();
		}else{
			return false;
		}
	}

	//删除数据功能
	public function delete($where,$limit = '',$order = ''){

		//判断可选参数
		if($limit != ''){
			$limit = 'limit '.$limit;
		}
		if($order != ''){
			$order = 'order by '.$order;
		}


		$sql="DELETE FROM {$this->tabName} WHERE {$where} {$order} {$limit}";
		$result=$this->pdo->exec($sql);

		if($result){
			return true;
		}else{
			$this->setError($this->pdo->errorInfo()[0],'删除失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}
	}

	//单条数据查询
	public function find($where,$field = "*",$order = ''){

		if($order != ''){
			$order = 'order by '.$order;
		}
		if($this->toMap){
			$field = $this->fieldToMap($field);
		}
		$this->sql = "SELECT {$field} FROM {$this->tabName} WHERE {$where} {$order} limit 1";
		$stmt = $this->pdo->prepare($this->sql);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if($result){
			return $result;
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}
	}

	//多条数据查询
	public function select($where,$field = "*",$limit = '',$order = ''){
		if($order != ''){
			$order = 'order by '.$order;
		}
		if($limit != ''){
			$limit = 'limit '.$limit;
		}
		if($this->toMap){
			$field = $this->fieldToMap($field);
		}
		$this->sql = "SELECT {$field} FROM {$this->tabName} WHERE {$where} {$order} {$limit}";
		$stmt = $this->pdo->prepare($this->sql);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if($result){
			return $result;
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}
	}

	//数据总数(可带条件)
	public function count($field = '',$where = ''){
		if(empty($field)){
			$field = $this->_pk;
		}
		if(!empty($where)){
			$where = 'WHERE '.$where;
		}

		//把映射字段转换为真实字段
		$field = $this->fileds[$field]['Field'];

		$this->sql = "select count({$field}) as count FROM {$this->tabName} {$where}";
		$stmt = $this->pdo->prepare($this->sql);
		$result = $stmt->execute();
		if($result){
			return $stmt->fetch()['count'];
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}

	}

	//数据最大值(可带条件)
	public function max($field,$where = ''){
		if(!empty($where)){
			$where = 'WHERE '.$where;
		}

		//把映射字段转换为真实字段
		$field = $this->fileds[$field]['Field'];

		$this->sql = "select max({$field}) as max FROM {$this->tabName} {$where}";
		$stmt = $this->pdo->prepare($this->sql);
		$result = $stmt->execute();
		if($result){
			return $stmt->fetch()['max'];
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}

	}

	//数据最小值(可带条件)
	public function min($field,$where = ''){
		if(!empty($where)){
			$where = 'WHERE '.$where;
		}

		//把映射字段转换为真实字段
		$field = $this->fileds[$field]['Field'];

		$this->sql = "select min({$field}) as min FROM {$this->tabName} {$where}";
		$stmt = $this->pdo->prepare($this->sql);
		$result = $stmt->execute();
		if($result){
			return $stmt->fetch()['min'];
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}

	}

	//数据平均值(可带条件)
	public function avg($field,$where = ''){
		if(!empty($where)){
			$where = 'WHERE '.$where;
		}

		//把映射字段转换为真实字段
		$field = $this->fileds[$field]['Field'];

		$this->sql = "select avg({$field}) as avg FROM {$this->tabName} {$where}";
		$stmt = $this->pdo->prepare($this->sql);
		$result = $stmt->execute();
		if($result){
			return $stmt->fetch()['avg'];
		}else{
			$this->setError($this->pdo->errorInfo()[0],'查询失败：'.$this->pdo->errorInfo()[2]);
			return false;
		}

	}

}