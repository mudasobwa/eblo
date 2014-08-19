<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class CacheException extends \Exception { }
class CacheAccessException extends CacheException { }

final class Cache
{
	const TAGS = 'config/tags.yml';
	const DATADIR = 'p';
	private $files = null;
	private $tags = null;
	private $tag_hash = null;

	private function __construct() {
		$this->tags = \Spyc::YAMLLoad(self::TAGS);
	}

	public function __clone() {
		throw new CacheAccessException('Singleton object Cache is not intended to be cloned.');
	}

	public function __wakeup() {
		throw new CacheAccessException('Singleton object Cache is not intended to be woken up.');
	}

	public static function instance() {
		static $inst = null;
        if ($inst === null) {
            $inst = new Cache();
        }
        return $inst;
	}

/* ================================================================================================ */
/* ========================               FILES                 =================================== */
/* ================================================================================================ */

	public function files() {
		if(is_null($this->files)) {
			$cmd = "ls " . self::DATADIR . "/";
			$this->files = \explode("\n", `{$cmd}`);
			\usort($this->files, function($v_1, $v_2) {
				if ($v_1 === $v_2) { return 0; }

				$v1 = \explode('-', $v_1);
				$v2 = \explode('-', $v_2);

				if(count($v1) !== 4 || count($v2) !== 4)
					return 0; // throw new CacheException(__METHOD__ . ' ⇒ everything goes wrong...');

				for($i=0; $i<4; $i++) {
					if(\intval($v1[$i]) !== \intval($v2[$i]))
						return \intval($v2[$i]) - \intval($v1[$i]);
				}

				return 0;
			});
		}
		return $this->files;
	}

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

	public function tags($tag = null) {
		if(is_null($this->tag_hash)) {
			$this->tag_hash = array();
			foreach($this->tags as $tag_name => $tag_re) {
				$this->tag_hash[$tag_name] = \explode("\n", \trim(`cd p && grep -Pazor '{$tag_re}' * | cut -d: -f1 | uniq | sort`));
			}
		}
		return is_null($tag) ? $this->tag_hash : $this->tag_hash[$tag];
	}

	public function reset() {
		$this->tag_hash = null;
		$this->files = null;
	}

/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

	/** @todo SANITIZE */
	public function search($kw) {
		return \explode("\n", \trim(`cd p && grep -Piazor '{$kw}' * | cut -d: -f1 | uniq | sort`));
	}

}
 