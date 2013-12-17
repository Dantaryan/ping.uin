<?php

define('LGPATH', './');

class m_lgr{
	public static function write($fpath, &$str, $mode, $newline=false){
		$f= fopen($fpath, $mode);
		@flock($f, LOCK_EX);  // блокировка записи
		if($newline)
			@fwrite($f,"\n");
		@fwrite($f,$str);
		@fflush($f); 			//очистка файлового буфера и запись в файл
		@flock($f, LOCK_UN); 	// Снятие блокировки
		fclose($f);
//		@chmod($fpath, 0660);
	}

	/**
	 * возвращает имя файла и строку из которой вызвана функция
	 *
	 * @param array $excludedfile
	 * @return array
	 */
	public static function errFileLine($excludedfile= array()){
		$a= array();
		$par = Array();
		$infs= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		foreach($infs as $v){
			$bname= basename($v['file']);
			if($bname != basename(__FILE__) && !in_array($bname, $excludedfile)){
				if($a){
					array_unshift($par, "$bname: $v[line]");
					$a['par'] = implode('=>', $par);
					if($bname != $a['file'])
						break;
				}else
					$a= array('file'=>$bname, 'line'=>$v['line']);
			}
		}
		return $a;
	}

	public static function fname($preffics="log"){
		return $preffics.date("Y-m-d").".txt";
	}

	/**
	 * рекурсивное преобразование кодировки массива
	 * @param $arr
	 * @return mixed
	 */
	public static function arr_conv(&$arr){
		foreach ($arr as &$val)
			if(is_array($val))
				self::arr_conv($val);
			else if(is_string($val))
				$val = iconv('UTF-8', 'CP1251//IGNORE//TRANSLIT', $val);
		return $arr;
	}

	/**
	 * убирает табуляции и преобразует в строку данные если это массив
	 * @param $msg
	 */
	public static function processStr(&$msg){
		if(is_array($msg))
			$msg= print_r($msg, true);

		$msg = str_replace ( "\t\t\t\t\t\t", "\t" . chr ( 2 ) . "\t", $msg );
		$msg = str_replace ( "\t\t\t", "", $msg );
		$msg = str_replace ( chr ( 2 ), "", $msg );
	}

	private static $ip = '';
	public static function lg($data, $code="", $dberr="", $fname=''){
		if(!self::$ip)
			self::$ip = getenv("REMOTE_ADDR");

		self::processStr($data);

		$dt= date("[Y-m-d H:i:s]");
		$str= "DATE:$dt\tIP:".self::$ip."\nCODE:$code\tERROR:\n$data\n";
		if($dberr != "")
			$str.= "DBERROR\n:$dberr\n";
		self::write(LGPATH . $fname, $str, "ab", true);
	}

	// запись строки в файл
	public static function logg($data, $rewrite= false, $fname=''){
		self::processStr($data);
		$mode= ($rewrite) ? "wb" : "ab";
		m_lgr::write(LGPATH . $fname, $data, $mode, true);
	}

	public static function logget($fname=''){
		$fpath = LGPATH . $fname;
		return (is_file($fpath)) ? file_get_contents($fpath) : '';
	}

	public static $timerTarget = 'profile.log';
	private static $timer;
	private static $timerTotal;
	/**
	 * начало замера времени
	 */
	public static function timer(){
		$stmtime = microtime();
		self::$timer = explode(' ', $stmtime);
		if(!self::$timerTotal)
			self::$timerTotal = self::$timer;
	}

	/**
	 * замер времени, промежуточное значение
	 * @param string $mark  - метка (берется индекс, если не указывать)
	 */
	public static function timerCheck($mark = ''){
		$t = self::$timerTotal;
		self::$timerTotal = false;
		$time = self::timerStop($mark);
		self::$timerTotal = $t;
		self::timer();
		return $time;
	}

	/**
	 * конец замера выполнения некоторого кода
	 * @param string $txt дополнительная информация в лог
	 */
	public static function timerStop($txt=''){
		$endtime = explode(' ', microtime());
		$eTime = ($endtime[1] - self::$timer[1]) + round($endtime[0] - self::$timer[0], 4);
		$txt = self::$timer ? "Время выполнения '$eTime' $txt" : (date('Y-m-d H:i:s').' -- '.$txt);
		
		if(self::$timerTotal){
			$ceTime = ($endtime[1] - self::$timerTotal[1]) + round($endtime[0] - self::$timerTotal[0], 4);
			$txt.= "\nОбщее время выполнения $ceTime";
		}
		
		if(self::$timerTarget)
			self::write(LGPATH . self::$timerTarget, $txt, 'ab', true);
		else
			echo $txt . "\n";
		return $eTime;
	}

	public static $nodie= FALSE;			// при вызове функции died вызывать ли die
	static function died($errMsg){
		// вызов функции die
		if(self::$nodie !== FALSE){
			echo $errMsg;
			return FALSE;
		}
		die ( htmlspecialchars_decode ( "error " . $errMsg . " Обратитесь к администратору" ) );
	}

}

