<?php
/**
 * Created by PhpStorm.
 * User: tang
 * Date: 14-7-18
 * Time: 下午11:05
 */

const MEM_HOST = 'localhost';
const MEM_POST = 11211;
const MEM_FREFIX = 'test_';

include 'MyMemcache.class.php';

$mem = new MemcacheDrive(MEM_HOST,MEM_POST,MEM_FREFIX,array(array('192.168.140.90','11211'),array('192.168.140.60','11211')));
/*
for($i = 0 ;$i < 10000; $i++){
	$mem->set('test1'.$i,'123456');
}*/
//$mem->clear();
$mem->cacheDump();