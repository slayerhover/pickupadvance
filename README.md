# PHP爬虫，基于swoole与QueryList

**执行方式：CLI**
- 启动: #php start.php
- 停止: #php stop.php

**运行环境**
``` 
php >= 7.0 配置swoole扩展与redis扩展
``` 

**执行顺序**
```
1. 执行php tastqueue.php，安排任务队列queue。
2. swoole启动分布式任务,自定义启动进程数。
3. QueryList采集queue队列，将整理好的数据入库
```

**爬虫说明**
```
1. 执行失败的任务会进入errorlist队列
2. 有任务失败，会自动重新检测代理池，移除失效代理。
3. 依赖库composer.json：
{
    "require": {
        "jaeger/querylist": "^4.0",
		"illuminate/database":"~4.2"
    }
}
4. Pickup::setRule()方法里写页面采集规则。
5. Server::$count定义开启的爬虫数量。
6. 随机代理，随机agent
7. Redis使用到的key定义：
	queue: 采集任务队列
	errorlist:任务失败队列
	proxy:代理池
```
