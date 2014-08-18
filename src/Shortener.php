<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class ShortenerException extends \Exception { }
class ShortenerAccessException extends ShortenerException { }

final class Shortener
{
    const SYMBOLS = '0123456789[]';

    const BASE128 = 0;
    const UTFY = 1;

    private $base;

    private function __construct() {
        $this->base = array(
            - 1990 + \ord('A'),
            \ord('P') - 1,
            \ord('`') - 1,
            \ord('¡'),
            \ord('À'),
            256
        );
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

    private function unichr($u) {
        return \mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    private function uniord($u) {
        return \array_map('intval',
            \explode(
                ';',
                \preg_filter('/[&#]|;$/', '', \mb_encode_numericentity($u, array (0x0, 0xffff, 0, 0xffff), 'UTF-8'))
            )
        );
    }

    private function str_split_unicode($str, $l = 0) {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function __tiny_recurse($arr_or_s, $lvl = 0) {
        $res = '';

        if (is_array($arr_or_s)) {
            foreach($arr_or_s as $k => $v) {
                $res .= $this->unichr(intval($k) + $this->base[$lvl]) . $this->__tiny_recurse($v, $lvl + 1);
            }
        } else {
            foreach(\explode('+', $arr_or_s) as $d) {
                while(\strlen($d) < 10) { $d .= '-01'; } // make years format comliant
                $dt = \strtotime(\preg_replace(
                    '/(\d{4}\-\d{1,2}\-\d{1,2})\-(\d{1,2})\-(\d{1,2})\-(\d{1,2})/',
                    '\1 \2:\3:\4',
                    $d)
                );
                $res .= $this->unichr(($dt>>16) & 0xFFFF) . $this->unichr($dt & 0xFFFF);
            }
        }

        return $res;
    }

    public function __tiny($s, $method = self::UTFY) {
        return $this->__tiny_recurse($method = self::UTFY ? $s : $this->__pack_array($s));
    }

    public function __untiny($s, $method = self::UTFY) {
        if($method !== self::UTFY) {
            throw new ShortenerException('Untinying BASE128 is nto yet implemented.');
        }

        $res = [];
        foreach($this->str_split_unicode($s, 2) as $d) {
            $d = $this->uniord($d);
            if(count($d) < 2) throw new ShortenerException(__METHOD__ . ' :: bad input {$s}, failed on [{$d}].');
            $res[] = \preg_replace('/(\-00)+$/', '', \date('Y-m-d-H-i-s', ($d[0]<<16) + $d[1]));
        }
        return \implode('+', $res);
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

	private function __unserialize($s) {
        $s = \preg_replace_callback('/\[((?:\d+,?)+)\]/', function($mch) {
            $c = count($m = \explode(',', $mch[1])); // array of desired catches, like “12,14”
            return "a:{$c}:{". \implode('', \array_map(function ($s) { return 'i:'.$s.';a:0:{}'; }, $m)) ."}";
        }, $s);
        $s = \preg_replace('/,(\d+),/', ',\1a:0:{},', $s); // fucking years
		do {
			$s = \preg_replace_callback('/\[([^\[\]]+)\]/', function($mch) {
				$c = count($m = \explode(',', $mch[1]));
				return "a:{$c}:{". \preg_replace('/0?(\d+)a/', 'i:\1;a', \implode('', $m)) ."}";
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

    public static function tiny($s) {
        return Shortener::instance()->__tiny($s);
    }

    public static function untiny($s) {
        return Shortener::instance()->__untiny($s);
    }



}
 