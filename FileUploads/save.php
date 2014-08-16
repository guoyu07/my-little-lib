<?php 
echo '<hr />';
include 'FileUploads.Drive.php';

$up = new FileUpload;
if($up->uploads()){
	var_dump($up->succeess);
}else{
	echo $up->errorInfo();
}

?>