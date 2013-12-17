<?php 

class iniReader{
	protected $_iniPath;
	protected $_iniDef = Array();
	
	public function __construct($iniFile, $defSets){
		$this->_iniPath = $iniFile;//'./config.ini';
		$this->_iniDef = $defSets;
	}

/*
	public function setPath($path){
		$this->_iniPath = $path;
	}
*/

	public function read($section = null){
		$res = $this->_iniDef;
		
		$needRewrite = false;
		
		if(is_readable($this->_iniPath)){
			$res = parse_ini_file($this->_iniPath, true);
			$needRewrite = self::upfill($res, $this->_iniDef);
		}elseif(!is_file($this->_iniPath))
			$needRewrite = true;
		
		if($needRewrite)
			$this->ini_write($res);
		
		if(is_null($section))
			return $res;
		
		return isset($res[$section]) ? $res[$section] : Array();
	}
	
	public static function upfill(&$current, $def){
		if( is_array($def) and !is_array($current) ){
			$current = $def;
			return true;
		}

		$answ = false;
		if(is_array($current) and is_array($def))
			foreach ($def as $k=>$v)
				if(!array_key_exists($k, $current)){
					$answ = true;
					$current[$k] = $v;
				}else
					$answ = self::upfill($current[$k], $v);
		return $answ;
	}

	private function _writeToFile($string){
		file_put_contents($this->_iniPath, $string);
	}

	private function ini_write($arr) {
		$string = '';
		foreach($arr as $k => &$v) {
			$string .= "[$k]" . PHP_EOL;
			$string .= $this->_write_get_string($v, '').PHP_EOL;
		}
		$this->_writeToFile($string);
	}

	private function _write_get_string(&$ini, $prefix) {
		$string = '';
		ksort($ini);
		foreach($ini as $key => &$val) {
			if (is_array($val))
				// ���� ����������� ����� 2, ����������� ��������
				$string .= $this->_write_get_string($val, $prefix.$key.'.');
			else
				$string .= $prefix.$key.' = '.$this->_setValue($val).PHP_EOL;
		}
		return $string;
	}

	private function _setValue($val) {
		if ($val === true)
			return 'true';
		if ($val === false)
			return 'false';
		return $val;
	}
}