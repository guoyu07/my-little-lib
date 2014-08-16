<?php
class FileUpload{

	public $savePath = '';
	public $saveName = 'uniqid';
	public $allowType = array('jpeg','jpg','png','gif');
	public $allowMime = array('image/jpeg','image/png','image/gif');
	public $maxSize = 409600;

	private $files = array();
	public $prefix = '';

	private $error = array('errorCode' =>0 , 'errorFile' => '');

	public $succeess = '';

	public function __construct($path = './uploads'){
		$this->savePath = $path;
	}

	public function __set($porperty,$v){
		if($porperty == 'maxSize' || $porperty == 'maxsize'){
			if(is_numeric($v)){
				$this->maxSize = $v;
			}
		}
	}

	public function __get($porperty){
		if($porperty == 'error'){
			return $this->error['errorCode'];
		}
		return false;
	}


	//单文件上传
	public function uploadOne(){
		if (!$this->checkDir()) {
			return false;
		}
		$this->clearUp();
		if (!$this->checkError()) {
			return false;
		}
		if (!$this->checkType()) {
			return false;
		}
		if (!$this->checkMime()) {
			return false;
		}
		if (!$this->checkSize()) {
			return false;
		}
		$this->succeess = $this->save(array_values($this->files)[0],$this->getNewName());
		if($this->succeess){
			return true;
		}else{
			return false;
		}
	}

	//多文件上传
	public function uploads(){
		if (!$this->checkDir()) {
			return false;
		}

		$this->clearUp();

		if (!$this->checkError()) {
			return false;
		}

		if (!$this->checkType()) {
			return false;
		}

		if (!$this->checkMime()) {
			return false;
		}

		if (!$this->checkSize()) {
			return false;
		}
		foreach($this->files as $key => $value){
			$this->succeess[$key] = $this->save($value,$this->getNewName());
			if(!$this->succeess[$key]){
				return false;
			}
		}
		return true;

	}

	//------------------封装部分--------------------------------------------
	//整理文件数组以方便上传
	private function clearUp(){
		foreach ($_FILES as $key => $value) {
			if (is_array($value['name'])) {
				for($i = 0 ; $i < count($value['name']) ; $i++){
					if (!empty($value['name'][$i])) {
						$this->files[$key.'_'.$i] = array(
							'name' => $value['name'][$i],
							'type' => $value['type'][$i],
							'tmp_name' => $value['tmp_name'][$i],
							'error' => $value['error'][$i],
							'size' => $value['size'][$i]
						);
					}
				}
			}else{
				$this->files[$key] = $value;
			}
		}
	}

	/**
	 *	生成新的文件名
	 *	如果saveName规则是一个函数名的字符串那么它会自动调用这个函数并生成文件名
	 *	如果saveName规则是个普通的字符串，则直接返回这个字符串
	 *	如果saveNane是一个数组
	 *		数组中有函数名，则会调用这个函数生成一段名称
	 *		数组中元素是个普通字符串，直接连接这段字符串
	 *		假如数组是这样的array('time','Pic','mt_rand');就成生成 “秒数_Pic_随机数” 这样的一个字符串
	 */
	private function getNewName(){
		$new = '';
		$func = $this->saveName;
		if(is_array($func)){
			foreach($func as $value){
				if(function_exists($value)){
					$new .= $value().'_';
				}else{
					$new .= $value.'_';
				}
			}
			$new = rtrim($new,'_');
		}else{
			if(function_exists($func)){
				$new = $func();
			}else{
				$new = $func;
			}
		}
		return $new;
	}

	//检查保存路径是否存在
	private function checkDir(){

		//判断路径变量是否为空
		if(empty($this->savePath)){
			$this->setError('-1');
			return false;
		}else{

			//判断路径是否存在，是否可写
			if(file_exists($this->savePath) && is_writable($this->savePath)){
				return true;
			}else{

				//建立文件夹
				if(mkdir($this->savePath,0777,true)){
					return true;
				}else{
					$this->setError('-1');
					return false;
				}
			}
		}
	}

	//检查系统给的error属性值
	private function checkerror(){
		foreach ($this->files as $value) {
			if($value['error'] != 0){
				$this->setError($value['error'],$value['name']);
				return false;
			}
		}
		return true;
	}

	//检查文件类型
	private function checkType(){
		foreach ($this->files as &$value) {
			$subfix = strtolower(pathinfo($value['name'],PATHINFO_EXTENSION));
			if(!in_array($subfix,$this->allowType)){
				$this->setError(-4,$value['name']);
				return false;
			}
			$value['subfix'] = $subfix;
		}
		return true;
	}

	//检查文件mime
	private function checkMime(){
		foreach ($this->files as $value) {
			if(!in_array($value['type'],$this->allowMime)){
				$this->setError(-5,$value['name']);
				return false;
			}
		}
		return true;
	}

	//检查文件大小
	private function checkSize(){
		foreach ($this->files as $value) {
			if($value['size'] > $this->maxSize){
				$this->setError(-3,$value['name']);
				return false;
			}
		}
		return true;
	}

	//存入
	private function save($file,$newname){
		if(move_uploaded_file($file['tmp_name'], $this->savePath.'/'.$this->prefix.$newname.'.'.$file['subfix'])){
			$file['savepath'] = $this->savePath;
			$file['newname'] = $newname.'.'.$file['subfix'];
			return $file;
		}
		$this->setError(-8,$file['name']);
		return false;
	}

	//设置本类自带的error属性的值
	public function setError($errorcode,$errorfile = ''){
		$this->error = array('errorcode' => $errorcode, 'errorfile' => $errorfile);
	}

	//返回错误位信息
	public function errorInfo(){
		switch($this->error['errorcode']){
			case 1:
				return '文件大小超过PHP.INI,来自文件：'.$this->error['errorfile'];
			case 2:
				return '文件大小超过MAX_FILE_SIZE,来自文件：'.$this->error['errorfile'];
			case 3:
				return '部分文件上传,来自文件：'.$this->error['errorfile'];
			case 4:
				return '没有文件上传';
			case 6:
				return '找不到临时目录';
			case 7:
				return '没有写入权限';
			case -1:
				return '创建目录失败';
			case -2:
				return '未设置路径或者建立文件夹失败';
			case -3:
				return '上传大小超过了maxSize大小,来自文件：'.$this->error['errorfile'];
			case -4:
				return '文件类型不合法,来自文件：'.$this->error['errorfile'];
			case -5:
				return 'MIME不合法,来自文件：'.$this->error['errorfile'];
			case -6:
				return '生成文件名的规则不合法';
			case -7:
				return '不是上传文件,来自文件：'.$this->error['errorfile'];
			case -8:
				return '上传文件出错,来自文件：'.$this->error['errorfile'];
			default: return '成功';
		}
	}



}