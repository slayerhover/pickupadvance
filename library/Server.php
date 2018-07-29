<?php
namespace library;

use swoole\server as SW;
use library\Pickup as PK;
class Server
{
    private $serv;
	public  static $cache;
	private static $cache_set=	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	6379,
	);
	private static $count = 1;
	
    public function __construct() {		
        $this->serv = new SW("127.0.0.1", 9501);
        $this->serv->set(array(
            'worker_num'     	=> self::$count, #开启任务数
            'daemonize'      	=> 1,
            'max_request'     	=> 1000,
            'dispatch_mode'    	=> 1,//进程数据包分配模式 1平均分配，2按FD取摸固定分配，3抢占式分配            
            'log_file'      	=> "./logs/swoole.log" ,
        ));
        $this->serv->on('Receive',		array($this,'onReceive'));
		$this->serv->on('WorkerStart',	array($this,'onWorkerStart'));
		$this->serv->on('WorkerStop',	array($this,'onWorkerStop'));
		$this->serv->on('ManagerStart', function ($serv) {
			global $argv;
			swoole_set_process_name("php {$argv[0]}: manager");
		});
        $this->serv->start();
    }
    public function onReceive(SW $serv, $fd, $from_id, $data) {
        if($data=='shutdown'){
			$serv->send($fd, 'SHUTDOWN');
			$serv->close($fd);
			$serv->shutdown();
		}else{
			$serv->send($fd, 'STARTING');
			$this->taskQueue($data);
		}
    }
	private function isUrl($url){
		return preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?\/?/i', $url);
	}
	public function taskQueue($data) {
		#任务队列取值
		while(self::$cache->llen($data)>0 && $url=self::$cache->rPop($data)){		
			if($this->isUrl($url))	PK::getInstance()->pick($url);
		}
    }
	public function onWorkerStart($serv, $worker_id){		
		self::Log("worker {$worker_id} start on ".date('Y-m-d H:i:s'));
		global $argv;		
		if ($serv->taskworker) {
			swoole_set_process_name("php {$argv[0]}: task");
		} else {
			swoole_set_process_name("php {$argv[0]}: worker");
		}
		if(self::$cache==NULL){
            self::$cache = new \Redis();
            self::$cache->connect(self::$cache_set['host'], self::$cache_set['port']);
			self::$cache->select(3);
            self::Log("Connect redis on {$worker_id} at ".date('Y-m-d H:i:s'));
        }
				
		$this->taskQueue('tmp_queue');
        $serv->shutdown();
		/****
		#每五分钟执行一次
		$serv->tick(300000, function()use($worker_id) {			
            $this->taskQueue('pickup_queue');			
			self::Log("tick {$worker_id} at ".date('Y-m-d H:i:s'));
        });
		****/
	}
	public function onWorkerStop($serv, $worker_id)
	{		
		if(self::$cache!=NULL){
            self::$cache->close();
            self::$cache=NULL;
            self::Log("Close redis on {$worker_id} at " . date('Y-m-d H:i:s'));
        }
		self::Log("worker {$worker_id} stop on ".date('Y-m-d H:i:s'));
	}
	public static function Log($data) {
		$file = dirname(__FILE__) . "/../logs/" . date('Ymd') . ".log";
		file_put_contents($file, $data . "\r\n", FILE_APPEND | LOCK_EX);
	}	
}
