<?php

class streamPingNotify implements IObserver{
	private function _beautifyState($state){
		$answ = "$state[adress]:$state[port] \t" . ($state['status'] ? 'на связи' : 'нет связи');
		return $answ;
	}

	public function update($states, $sets) {
		header('Content-Type: text/html; charset=utf-8');
		echo date("Y-m-d H:i:s\n");
		foreach ($states as $state)
			echo $this->_beautifyState($state) . PHP_EOL;
	}

	public function getName(){
		return 'stream';
	}
}

class filelogPingNotify implements IObserver{
	private function _beautifyState($state){
		$answ = "$state[adress]:$state[port] \t" . ($state['status'] ? 'на связи' : 'нет связи');
		return $answ;
	}

	public function update($states, $sets) {
		$data = date("Y-m-d H:i:s\n");
		foreach ($states as $state)
			$data.= $this->_beautifyState($state) . PHP_EOL;
		file_put_contents($sets['path'], $data,  FILE_APPEND);
	}

	public function getName(){
		return 'filelog';
	}
}


class smsPingNotify implements IObserver{
	private $_serviceProvider = 'http://vipsms.net/api/soap.html';

	private function _beautifyStates($states){
		$answ = date("Y-m-d H:i:s");
		foreach ($states as $state)
			$answ.= "\n$state[adress]:$state[port]\t" . ($state['status'] ? 'on' : 'down');
		return $answ;
	}

	public function update($states, $sets) {
		$err = $this->_send(
				$this->_beautifyStates($states)
				, $sets['user']
				, $sets['pass']
				, $sets['tel']
				, $sets['sign']
			);
		if($err)
			echo $err;
	}

	private function _send($mess, $login, $pass, $tel, $sign) {
		if (!extension_loaded('soap'))
		  return 'Для отправки СМС необходимо подключить протокол SOAP';

		if(!$login or !$pass)
			return 'Не указан логин или пароль';

		if(!$tel)
			return 'Не указан целевой телефон (+380971234567) ';

		function explain_err($soap_res){
			if ($soap_res->extend && is_array($soap_res->extend))
				return $soap_res->extend['errors'][0];
			return $soap_res->message;
		}

		$client = new SoapClient($this->_serviceProvider);

		// Функция пытается осуществить подключение к серверу
		$res = $client->auth($login, $pass);
		if( $res->code )
		    return explain_err($res);

		$sessid = $res->message;

		// Сообщение обязательно писать в UTF-8
		$res = $client->sendSmsOne($sessid, $tel, $sign, $mess);
		if( $res->code )
		    return explain_err($res);

		return 0;
	}

	public function getName(){
		return 'sms';
	}
}

class jabberPingNotify implements IObserver{
	private function _beautifyState($state){
		$answ = "$state[adress]:$state[port] \t" . ($state['status'] ? 'на связи' : 'нет связи');
		return $answ;
	}

	public function update($states, $sets) {
		require_once './jabber/xmpp.class.php';

		$webi = new XMPP($sets);

		$webi->connect();
		
		$mess = date("Y-m-d H:i:s");
		foreach ($states as $state)
			$mess.= PHP_EOL . $this->_beautifyState($state);
		$webi->sendMessage($sets['target'], $mess); // отправка сообщения
	}
	
	public function getName(){
		return 'jabber';
	}
}

