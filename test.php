<?php

require_once './jabber/xmpp.class.php';

$sets = Array(
	'user' => 'testeros001'
	, 'pass' => 'testeros1'
	, 'host' => 'webim.qip.ru'
	, 'port' => 5222
	, 'domain' => 'qip.ru'
	, 'log_file_name' => 'jabb.log'
	, 'logtxt' => true
	, 'tls_off' => 1
);

$webi = new XMPP($sets);

$webi->connect();

$webi->sendMessage('testeros002@qip.ru', 'трям2 парам пам пам');
// $webi->sendMessage('testeros001', 'трям1');
