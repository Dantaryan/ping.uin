<?php

$queueFile = 'jabb.stack';
$timeLimit = 600;

if(is_file($queueFile))
	exit;

@fclose(fopen($queueFile, 'wb'));

set_time_limit($timeLimit);

require_once './jabber/xmpp.class.php';


$conf =Array(
		'user' => "testeros001"
		, 'pass'=>"testeros1"
		, 'host'=>"webim.qip.ru"
		, 'port'=>5222
		, 'domain'=>"qip.ru"

		, 'logtxt'=>false
		, 'log_file_name'=>"loggerxmpp.log"
		, 'tls_off'=> 1
);

// header('Content-type: text/html; charset=utf-8;');

$webi = new XMPP($conf);
$webi->connect(); // установка соединения...

while (true){
	if(!is_file($queueFile))
		exit;
	if($txt = file_get_contents($queueFile)){
		if($txt=='kill')
			exit;
		$webi->sendMessage("testeros002@qip.ru", $txt); // отправка сообщения
		$fp = fopen($queueFile, 'wb'); //Открываем файл в режиме записи
		ftruncate($fp, 0); // очищаем файл
		fclose($fp);
	}else
		sleep(1);
}

