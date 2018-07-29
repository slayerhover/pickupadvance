<?php
set_error_handler('displayErrorHandler');

#²¶»ñWarring´íÎó
function displayErrorHandler($errno, $errstr, $filename, $line)
{
	$error_no_arr = array(
			1=>'ERROR', 
			2=>'WARNING', 
			4=>'PARSE', 
			8=>'NOTICE', 
			16=>'CORE_ERROR',
			32=>'CORE_WARNING', 
			64=>'COMPILE_ERROR', 
			128=>'COMPILE_WARNING', 
			256=>'USER_ERROR', 
			512=>'USER_WARNING', 
			1024=>'USER_NOTICE', 
			2047=>'ALL', 
			2048=>'STRICT'
	);

	if(in_array($errno,[2])){
			#library\Server::Log("File:{$filename} on Line:{$line} \n" . $error_no_arr[$errno] . ":". $errstr."\n");
	}
	if(in_array($errno,[1,4])){			
			throw new \Exception($error_no_arr[$errno] . ":". $errstr, $errno);
	}
}
