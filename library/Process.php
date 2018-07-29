<?php
namespace library;
use swoole\process as SP;
use library\Pickup as PK;
use library\Server;
class Process{
	public	$mpid=0; //主进程ID.
	public	$works=[]; //子进程列表
	public	$max_process=10; //最大10个进程数量
	public  static $cache;
	private static $cache_set=	array(
				'host'		=>	'127.0.0.1',
				'port'		=>	6379,
	);
	
	public	function __construct($num=10){
		try{
			#SP::daemon();
			swoole_set_process_name(sprintf('php-ps:%s', 'master'));
			$this->max_process = $num;
			$this->mpid = posix_getpid();
			$this->run();
			$this->processWait();
		}catch(\Exception $e){
			exit($e->getMessage());
		}
	}	
	public function run(){		
		self::$cache = new \Redis();
		self::$cache->connect(self::$cache_set['host'], self::$cache_set['port']);
		self::$cache->select(3);
		
        for ($i=0; $i < $this->max_process; $i++) {
            $this->createProcess(++$this->new_index);
        }
    }	
	public function createProcess($index){
        $process = new SP(function(SP $worker)use($index){
            swoole_set_process_name(sprintf('php-ps:%s',$index));
			Server::Log("[create process]:" . $index . "\n");	
            $this->job($worker, $index);
        }, false, false);
        $pid=$process->start();
        $this->works[$pid]=$process;
        return $pid;
    }
	private function isUrl($url){
		return preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?\/?/i', $url);
	}
	public function job($worker, $index){		
		#redis任务队列取值
		while(self::$cache->llen('pickup_queue')>0 && $url=self::$cache->rPop('pickup_queue')){			
			if($this->isUrl($url)){
				PK::getInstance()->pick($url);
			}
			$this->checkMpid($worker);
		}		
	}
	public function checkMpid(&$worker){
        if(!SP::kill($this->mpid,0)){
            $worker->exit();
        }
    }
	public function rebootProcess($pid){
        if(!empty($pid)){
            $new_pid=$this->CreateProcess($pid);
			file_put_contents(date('Ymd').'.log', "rebootProcess: {$pid}={$new_pid} Done\n", FILE_APPEND);
            return;
        }else{
			throw new \Exception('rebootProcess Error: no pid');
		}
    }	
	public function processWait(){
        while(1) {			
            if(count($this->works)){
                $ret = SP::wait();
                if ($ret) {					
					$pid = $ret['pid'];  
					unset($this->works[$pid]);  
					//任务未结束，重启子进程
					//if(1){ 
					//	$this->rebootProcess($pid);
					//}
                }
            }else{
                break;
            }
        }
    }
}
