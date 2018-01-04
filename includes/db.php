<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$dbconfig = [
  'driver'    => 'mysql',
  'read'	  => [	
				 'host'      => '127.0.0.1',
				],
  'write'	  => [	
				 'host'      => '127.0.0.1',
				],
  'database'  => 'zy52cn',
  'username'  => 'zy52cn',
  'password'  => 'asdfasdf',
  'port'	  => '3306',
  'charset'   => 'utf8',
  'collation' => 'utf8_general_ci',
  'prefix'    => ''
];  
$capsule = new Capsule;
$capsule->addConnection($dbconfig);
$capsule->setAsGlobal();
$capsule->bootEloquent();