<?php

require_once 'observer.php';
require_once 'inireader.php';

class pinguin implements IObservable{
	protected $_iniDef = Array(
		'ping'=> Array(
			'tries' => 2,
			'timeout' => 1,
			'port' => 80,
			'notify' => 'stream,sms',
			'firstgoodstop' => 1
		),'files'=>Array(
			'adress'=>'./adress.json'
			,'state'=>'./state.json'
		),'sms'=>Array(
			'user' => 'user'
			, 'pass' => 'pass'
			, 'tel' => 'recipient'
			, 'sign' => 'подпись'
				
		),'jabber'=>Array(
			'user' => 'sender'
			, 'pass'=> 'pass'
			, 'host'=> 'server'
			, 'port'=>	5222
			, 'domain'=> 'doma.in'
			, 'logtxt'=> false
			, 'log_file_name' => 'fpath_if_logtxt_is_true'
			, 'tls_off'=> 1
			, 'target' => 'recipient'
		)
	);
	
	private $_iniSets = Array();
	
	private $_pingSet = Array();
	private $_pingStates = Array();
	private $_iniFile = './config.ini';
	
	public function __construct($iniFile = null){
		if($iniFile)
			$this->_iniFile = $iniFile;
		$this->_iniRead();
		$this->_readAdr();
	}
	
	private function _iniRead(){
		if($this->_iniSets)
			return;
		$ir = new iniReader($this->_iniFile, $this->_iniDef);
		$this->_iniSets = $ir->read();
	}
	
	public function run(){
		$warnList = Array();
		$stateToSave = Array();
		
		$obss = Array();
		foreach ($this->_observers as $obs)
			$obss[$obs->getName()] = Array();
		
		foreach ($this->_pingSet as $ping){
			$state = $ping->make();
			$pnam = $state['adress'] . ':' . $state['port'];
			foreach ($this->_pingStates as $snam=>$status)
				if($pnam==$snam){
					if($status!=$state['status']){
						$nList = $ping->getNList();
						foreach ($nList as $noteNam)
							if(isset($obss[$noteNam]))
								$obss[$noteNam][] = $state;
					}
						
					unset($this->_pingStates[$snam]);
					break;
				}
			$stateToSave[$pnam] = $state['status'];
		}
		
		file_put_contents($this->_iniSets['files']['state'], json_encode($stateToSave));
		
		$this->notfyObservers($obss);
	}
	
	private function _readAdr(){
		$apath = $this->_iniSets['files']['adress'];
		$spath = $this->_iniSets['files']['state'];
		$psets = $this->_iniSets['ping'];
		
		$adrs = json_decode(file_get_contents($apath), true);
		
		$states = Array();
		if(is_file($spath))
			$this->_pingStates = json_decode( file_get_contents($spath), true );
		foreach ($adrs as $sets){
			if(!isset($sets['adress']))
				continue;
			$adr = $sets['adress'];
			unset($sets['adress']);
			iniReader::upfill($sets, $psets);
			$this->_pingSet[] = new ping($adr, $sets);
		}
	}
	
	public function getSets($partition=null){
		$this->_readAdr();
		if(!$partition)
			return $this->_iniSets;
		return isset($this->_iniSets[$partition]) ? $this->_iniSets[$partition] : Array();
	}
	
	private $_observers = Array();
	/**
	 * attaching observers
	 * @param IObserver $obj
	 */
	public function attachObserver(IObserver $obj) {
		if(!in_array($obj, $this->_observers))
			$this->_observers[]= $obj;
	}

	public function notfyObservers($messArr) {
// print_r($messArr);
		foreach ($this->_observers as $obs){
			$onam = $obs->getName();
// print_r($onam);
			if(!isset($messArr[$onam]) or !$messArr[$onam])
				continue;
			
			$sets = $this->_iniSets[$onam];
			$obs->update($messArr[$onam], $sets);
		}
	}
}


class ping{
	// 
	private $_qualityBasedStatus = 0;
	
	private $_status;
	private $_latency;
	private $_quality;
	
	private $_path;
	private $_port;
	private $_tries;
// 	private $_triesDone;
 	private $_stopOnGood;
	private $_timeout;
	private $_noteList;
	
	public function __construct($adr, $sets){
		$this->_path = $adr;
		$this->_port = $sets['port'];
		$this->_tries = $sets['tries'];
		$this->_stopOnGood = $sets['firstgoodstop'];
		$this->_timeout = $sets['timeout'];
		$this->_noteList = is_array($sets['notify']) ? $sets['notify'] : explode(',', $sets['notify']);
	}
	
	public function getNList(){
		return $this->_noteList;
	}
	
	public function getState(){
		$status = $this->_status;
		if($this->_qualityBasedStatus)
			$status = ($this->_quality > 1/2) ? 1 : 0;
		return Array(
			'status' => $status
			,'adress' => $this->_path
			,'port' => $this->_port
			,'latency' => $this->_latency
			,'quality' => $this->_quality
			,'tries' => $this->_triesDone
		);
	}
	
	public function make(){
		$cnt = 0;
		$this->_resetStatistic();
		$lat = Array();
		
		for($i=0; $i<$this->_tries; $i++){
			$starttime = microtime(true);
			$file      = @fsockopen ($this->_path, $this->_sock, $errno, $errstr, $this->_timeout);
			$stoptime  = microtime(true);
			
			if ($file)
				$cnt++;
				
			$this->_triesDone++;
			$this->_quality = $cnt / $this->_triesDone;
				
			if ($file){
				fclose($file);
				
				$this->_status = 1;
				
				$lat[] = floor(($stoptime - $starttime) * 1000);
				$this->_latency = $cnt ? floor(array_sum($lat)/$cnt) : 0;
				
				if($this->_stopOnGood)
					return $this->getState();
			}else
				$this->_status = 0;
		}
		
		return $this->getState();
	}
	
	private function _resetStatistic(){
		$this->_quality = null;
		$this->_status = null;
		$this->_latency = null;
		$this->_triesDone = 0;
	}
}

