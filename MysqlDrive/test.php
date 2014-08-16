<?php 
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PWD = '';
const DB_CHARSET = 'utf8';
const DB_NAME = 'test';
const DB_PREFIX = 't_';

include './MySql.Drive.php';
include './TestModel.class.php';

var_dump(getdate());
/*
$model = new MysqlDrive('test');
$result = $model->select('id>5','*','4','id desc');
echo '<hr />';
echo $model->sql;
echo '<hr />';
echo $model->error;
echo '<hr />';
var_dump($result);
*/


$model = new TestModel;
$result = $model->select('id>5','*','4','id desc');
echo '<hr />';
echo $model->sql;
echo '<hr />';
echo $model->error;
echo '<hr />';
var_dump($result);


?>