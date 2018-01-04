<?php
class Server
{
    private $serv;
	private static $count = 6;
	private static $cache;
	private static $cache_set=	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	6379,
	);	
	
    public function __construct() {
        $this->serv = new swoole_server("127.0.0.1", 9501);
        $this->serv->set(array(
            'worker_num'     	=> self::$count, #开启任务数
            'daemonize'      	=> 1,
            'max_request'     	=> 1000,
            'dispatch_mode'    	=> 3,//进程数据包分配模式 1平均分配，2按FD取摸固定分配，3抢占式分配            
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
    public function onReceive(swoole_server $serv, $fd, $from_id, $data) {
        if($data=='shutdown'){
			$serv->send($fd, 'SHUTDOWN');
			$serv->close($fd);
			$serv->shutdown();
		}else{
			$serv->send($fd, 'STARTING');
			$this->taskQueue($data, $serv->worker_id);
		}
    }
	public function taskQueue($data, $worker_id) {
		#任务队列取值
		while($url	= self::$cache->rPop($data)){			
			try{
					Pickup::getInstance()->pick($url);
			}catch(Exception $e){
					self::$cache->lPush("errorlist", $url);
					Pickup::checkProxy(); #有任务出错,检测代理池
					self::Log("Exception:" . $url);
			}
		}
    }
	public function onWorkerStart($serv, $worker_id){
		self::Log("worker {$worker_id} start on ".date('Y-m-d H:i:s'));
		global $argv;
		if ($worker_id >= self::$count) {
			swoole_set_process_name("php {$argv[0]}: task");
		} else {
			swoole_set_process_name("php {$argv[0]}: worker");
		}		
		if(self::$cache==NULL){
			self::$cache = new Redis();
			self::$cache->connect(self::$cache_set['host'], self::$cache_set['port']);
			self::$cache->select(3);			
		}
		$this->taskQueue('queue', $worker_id);		
		#每五分钟执行一次
		$serv->tick(300000, function()use($worker_id) {			
            $this->taskQueue('queue', $worker_id);			
			self::Log("tick {$worker_id} at ".date('Y-m-d H:i:s'));
        });
	}
	public function onWorkerStop($serv, $worker_id)
	{
		if(self::$cache!=NULL){
			self::$cache->close();
			self::$cache=NULL;
		}
		self::Log("worker {$worker_id} stop on ".date('Y-m-d H:i:s'));
	}
	public static function Log($data) {
		$file = dirname(__FILE__) . "/../logs/" . date('Ymd') . ".log";
		file_put_contents($file, $data . "\r\n", FILE_APPEND | LOCK_EX);
	}
}
