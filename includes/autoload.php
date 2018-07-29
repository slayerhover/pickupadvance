<?php
spl_autoload_register(function ($class) {
    if ($class) {
        $file = str_replace('\\', '/', $class);
        $file = HOME_PATH . '/' . $file . '.php';
        if (file_exists($file)) {
            include $file;
        }else{
			file_put_contents(HOME_PATH . "/logs/autoload.log",  $file . "\n", FILE_APPEND);
		}
    }
});