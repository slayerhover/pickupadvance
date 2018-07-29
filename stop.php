<?php
header('content-type:text/html;charset=utf-8');
try{
	$client = new swoole_client(SWOOLE_SOCK_TCP);
	if (!$client->connect('127.0.0.1', 9501, -1))
	{
		throw new Exception("connect failed. Error: {$client->errCode}\n");
	}
	$sender	=	$client->send("shutdown");

	$n	=	1;
	while($result = $client->recv()){
		echo "第{$n}次接收到内容\n";
		if($result=='SHUTDOWN'){
			echo "服务器已正常关闭。bye~\n";
			break;
		}else{
			echo "服务器尚在运行之中,Please Wait~\n";
			break;
		}
		$n++;
	}
	$client->close();
}catch(Exception $e){
	echo "Failed: " . $e->getMessage();
}