<?php
const DB_DRIVE = 'mysql';
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8';
const DB_NAME = 'test';
const DB_PREFIX = 't_';

include 'PDODrive.class.php';
include 'TestModel.class.php';

$table = new TestModel();
//$table->insert(array('name'=> '88','password' => '34885678'));

//echo $table->update(array('name' =>'260','password' => '1111123111111'),'id=65');

//$table->delete('id=61');

//查询时用映射名代替字段名输出
$table->toMap = true;

//var_dump($table->find("id=60"));

//var_dump($table->select("id>60"));

echo $table->avg('id');
var_dump($table);
//var_dump($table);

