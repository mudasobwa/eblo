<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class CacheAccessException extends \Exception { }

final class Cache
{
	const TAGS = 'config/tags.yml';
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
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

	public function tags() {
		if(is_null($this->tag_hash)) {
			$this->tag_hash = [];
			foreach($this->tags as $tag_name => $tag_re) {
				$this->tag_hash[$tag_name] = \explode("\n", \trim(`cd p && grep -Pazor '{$tag_re}' * | cut -d: -f1 | uniq | sort`));
			}
		}
		return $this->tag_hash;
	}

	public function tags_reset() {
		$this->tag_hash = null;
	}

/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

	/** @todo SANITIZE */
	public function search($kw) {
		return \explode("\n", \trim(`cd p && grep -Pazor '{$kw}' * | cut -d: -f1 | uniq | sort`));
	}

}
 