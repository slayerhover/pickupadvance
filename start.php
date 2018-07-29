<?php
header('content-type:text/html;charset=utf-8');
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('PRC');
define('HOME_PATH', dirname(__FILE__));

require(HOME_PATH . '/vendor/autoload.php');
require(HOME_PATH . '/includes/autoload.php');
require(HOME_PATH . '/includes/errorReport.php');
require(HOME_PATH . '/includes/db.php');

use library\Server;
try{
	$run	=new Server;
}catch(Exception $e){
	Server::Log("[Start Failed]:" . $e->getMessage()."\n");	
}
