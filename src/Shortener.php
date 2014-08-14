<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class ShortenerException extends \Exception { }
class ShortenerAccessException extends ShortenerException { }

final class Shortener
{
	const SYMBOLS = '0123456789-+';

	private function __construct() {
	}

	public function __clone() {
		throw new ShortenerAccessException('Singleton object Shortener is not intended to be cloned.');
	}

	public function __wakeup() {
		throw new ShortenerCacheAccessException('Singleton object ShortenerCache is not intended to be woken up.');
	}

	public static function instance() {
		static $inst = null;
        if ($inst === null) {
            $inst = new Shortener();
        }
        return $inst;
	}

/* ================================================================================================ */
/* ========================                PACK                 =================================== */
/* ================================================================================================ */

	public function sym_2_4_bits($s) {
		if(\strlen($s) !== 1) {
			throw new ShortenerException(__METHOD__ . ' failed on [' . $s . '] (not well-sized, must be 1)');
		}
		$i = null;
		switch($s) {
			case '0': $i = 0; break;
			case '1': $i = 1; break;
			case '2': $i = 2; break;
			case '3': $i = 3; break;
			case '4': $i = 4; break;
			case '5': $i = 5; break;
			case '6': $i = 6; break;
			case '7': $i = 7; break;
			case '8': $i = 8; break;
			case '9': $i = 9; break;
			case '-': $i = 10; break;
			case '+': $i = 11; break;
			default: throw new ShortenerException(__METHOD__ . ' failed on [' . $s . '] (not valid, must be [0-9+-])');
		}
		return $i;
	}

	private function __pack_array($s) {
		$res = array();
		foreach(\explode('+', $s) as $date) {
			$d = \explode('-', $date);
			$curr = &$res;
			for($i=0; $i<count($d); $i++) {
				$di = (string)$d[$i];
				if(!isset($curr[$di])) {								// no previous dates of this period
					$curr[$di] = array();
				} else if(is_array($curr[$di]) && !count($curr[$di])) {	// the whole period is already selected
					break;
				}														// continue adding subperiods
				$curr = &$curr[$di];
			}
		}
		return $res;
	}

	private function __pack_recurse($arr) {
		if(!count($arr)) return '';

		$a = array();
		foreach($arr as $k => $v) {
			$a[] = $k . $this->__pack_recurse($v);
		}
//		\sort($a);	// FIXME Do we need to sort the result here?

		return '[' . \implode(',', $a) . ']';
	}

	public function __pack($s) {
		return $this->__pack_recurse($this->__pack_array($s));
	}

	public function __unserialize($s) {
		$s = \preg_replace_callback('/\[((?:\d+,?)+)\]/', function($mch) {
			$c = count($m = \explode(',', $mch[1])); // array of desired catches, like “12,14”
			return "a:{$c}:{". \implode('',\array_map(function ($s) { return 'i:'.$s.';a:0:{}'; }, $m)) ."}";

		}, $s);
		do {
			$s = \preg_replace_callback('/\[([^\[\]]+)\]/', function($mch) {
				$c = count($m = \explode(',', $mch[1]));
				return "a:{$c}:{". \preg_replace('/(\d+)a/', 'i:\1;a', \implode('', $m)) ."}";
			}, $s, -1, $count);
		} while ($count);
		return \unserialize($s);
	}

	private function __unpack_recurse($s, $arr, &$memo) {
		if(!count($arr)) return 0;
		// [2002[12],2003[11[14,11]]]
		foreach($arr as $k => $v) {
			$kk = (strlen($k) < 2) ? \sprintf("%02d", $k) : $k;
			$ss = empty($s) ? $kk : "{$s}-{$kk}";
			if(! $this->__unpack_recurse($ss, $v, $memo)) {
				$memo[] = $ss;
			}
		}
		return $memo;
	}

	public function __unpack($s) {
		$memo = array();
		return \implode('+', $this->__unpack_recurse('', $this->__unserialize($s), $memo));
	}

/* ================================================================================================ */
/* ========================               PUBLIC                =================================== */
/* ================================================================================================ */

	public static function pack($s) {
		return Shortener::instance()->__pack($s);
	}

	public static function unpack($s) {
		return Shortener::instance()->__unpack($s);
	}



}
 