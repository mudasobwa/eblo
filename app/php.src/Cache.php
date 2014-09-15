<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class CacheException extends \Exception { }
class CacheAccessException extends CacheException { }

final class Cache
{
	private $config = null;

	private $files = null;
	private $tags = null;

	private $content = null;

	/** Default constructor. It’s private, since this class is intended to act as singleton. */
	private function __construct() {
		$this->reset();
	}

	/** Forbid cloning. This class is intended to act as singleton. */
	public function __clone() {
		throw new CacheAccessException('Singleton object Cache is not intended to be cloned.');
	}

	/** Forbid waking up. This class is intended to act as singleton. */
	public function __wakeup() {
		throw new CacheAccessException('Singleton object Cache is not intended to be woken up.');
	}

	/**
	 * Default getter for an instance.
	 * @return Cache instance of this class.
	 */
	public static function instance() {
		static $inst = null;
        if ($inst === null) {
            $inst = new Cache();
        }
        return $inst;
	}

	/**
	 * Loads new item in Cache.
	 * @return string content by the name given (might be loaded if needed.).
	 */
	public static function load($name) {
		return self::instance()->content($name);
	}

/* ================================================================================================ */
/* ========================               UTILS                 =================================== */
/* ================================================================================================ */

	/** Default sorter for files, having names like `YYYY-MM-DD-NN`. */
	public static function sorter($v_1, $v_2) {
		if ($v_1 === $v_2) { return 0; }

		$v1 = \explode('-', $v_1);
		$v2 = \explode('-', $v_2);

		if(count($v1) !== count($v2))
			return 0;

		for($i=0; $i<count($v1); $i++) {
			if(\intval($v1[$i]) !== \intval($v2[$i]))
				return \intval($v2[$i]) - \intval($v1[$i]);
		}

		return 0;
	}

	public static function getDateByFileName($f) {
		return implode('-', array_slice(explode('-', $f),0,3));
	}
/* ================================================================================================ */
/* ========================               FILES                 =================================== */
/* ================================================================================================ */

	/**
	 * Getter for a data directory.
	 * @return string the path to the data directory as specified in config.
	 */
	private function datadir() {
		return __DIR__ . '/../../' . $this->config['data']['dir'] . "/";
	}

	/**
	 * Getter for a relative path to a file in data directory.
	 * @param name string the name of file to retrieve the full path for.
	 * @return string the path to the file in the data directory.
	 */
	private function pathto($name) {
		return $this->datadir() . $name;
	}

	/**
	 * Getter for a file list.
	 * @param count int the amount of files to get
	 * @param offset int the offset to retrieve files from
	 * @return array the list of files in the data directory.
	 */
	public function files($count = 0, $offset = 0) {
		if(is_null($this->files)) {
			$this->files = \array_filter(\scandir($this->datadir()), function($f) { return !is_dir($this->datadir().$f); } );
			\usort($this->files, $this->config['data']['sorter']);
		}
		return $count > 0 ? array_slice($this->files, $offset, $count) : $this->files;
	}

	/**
	 * Getter for a list of files specified with a regular expression.
	 * @param name string (generally speaking, a regexp) specifying the filenames of interest.
	 * @return array the list of files that match the regular exception given as parameter..
	 */
	public function find($name) {
		return \array_filter($this->files(), function($file) use ($name) {
			return \preg_match("/\A{$name}/ux", $file);
		});
	}

	/** Getter for the previous file.
	 * @see #sorter
	 * @param name string the name of the file to find previous for.
	 * @return string filename if the file exists, `null` otherwise.
	 */
	public function prev($name) {
		return
			$this->locate($name, $idx) && ($idx > 0)
				? $this->files()[$idx - 1] : null;
	}

	/** Getter for the next file.
	 * @see #sorter
	 * @param name string the name of the file to find next for.
	 * @return string filename if the file exists, `null` otherwise.
	 */
	public function next($name) {
		return
			$this->locate($name, $idx) && ($idx < \count($this->files()))
				? $this->files()[$idx + 1] : null;
	}

	/** Getter for index of the file, specified by it’s name.
	 * @param name string the name of the file to locate
	 * @param idx int `out` the index of the file in the list.
	 * @return boolean `true` if the file with this name exists in the list, `false` otherwise.
	 */
	public function locate($name, &$idx = null) {
		return \is_int($idx = \array_search($name, $this->files()));
	}

	/** Get [cached] file content.
	 * @param name string file name to retrieve the content for.
	 * @return string the file content of `null` if there is no such file.
	 */
	public function content($name) {
		if(isset($this->content[$name])) {
			$this->content[$name]['count'] = $this->content[$name]['count'] + 1;
			return $this->content[$name]['content'];
		}

		if(!$this->locate($name))
			return null;

		$content = \file_get_contents($this->pathto($name));

		// FIXME Here we remove the last one. Have to think about the better algorithm.
		if(\count($this->content) >= $this->config['settings']['memos']) {
			$idx = null;
			$cnt = null;
			foreach($this->content as $k => $v) {
				if(is_null($cnt) || $cnt > $v['count']) {
					$cnt = $v['count'];
					$idx = $k;
				}
			}
			unset($this->content[$idx]);
		}

		$this->content[$name] = array('count' => 1, 'content' => $content);
		return $content;
	}

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

	public function tags($tag = null) {
		if(is_null($this->tags)) {
			$this->tags = array();
			foreach($this->config['tags'] as $tag => $re) {
				$this->tags[$tag] = \explode("\n", \trim(`cd {$this->datadir()} && grep -Pazor '{$re}' * | cut -d: -f1 | uniq | sort`));
			}
		}
		return is_null($tag) ? $this->tags : $this->tags[$tag];
	}

	public function reset($config = null) {
		if (null === $config) {
			$config = \Spyc::YAMLLoad(__DIR__.'/../config/.restark.yml');
		}
		$this->config	= $config;
		$this->tags		= null;
		$this->files	= null;
		$this->content	= array();
	}

/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

	/** @todo SANITIZE */
	public function search($kw) {
		return \explode("\n", \trim(`cd {$this->datadir()} && grep -Piazor '{$kw}' * | cut -d: -f1 | uniq | sort`));
	}

}
 
