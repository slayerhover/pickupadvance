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
执行php tastqueue.php，安排任务队列queue。
swoole启动分布式任务,每五分钟会执行一次。
QueryList采集queue队列，将整理好的数据入库
```

**爬虫说明**
```
执行失败的任务会进入errorlist队列
有任务失败，会自动重新检测代理池，移除失效代理。
依赖库composer.json：
{
    "require": {
        "jaeger/querylist": "^4.0",
		"illuminate/database":"~4.2"
    }
}
在Pickup::setRule()方法里写页面采集规则。
```
