<?php

namespace Mudasobwa\Eblo;

require_once 'vendor/autoload.php';

class CacheException extends \Exception { }
class CacheAccessException extends CacheException { }

final class Cache {
	const DEFAULT_COLLECTION = '_';
	
	private $config = null;

	private $tags = null;
	private $metas = null;

	private $content = null;
	private $collections = array();

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
	 * @param $name string the name of the file to retrieve content for.
	 * @return string content by the name given (might be loaded if needed.).
	 */
	public static function load($name) {
		return self::instance()->content($name);
	}

	public static function yo($name, $collection = self::DEFAULT_COLLECTION) {
		$stuff = self::instance()->lookup($name, $collection, 1, true);
		$content = self::instance()->content($stuff[0]);
		return array(
			'title' =>  $content['title'],
			'prev'  =>  $stuff[-1],
			'next'  =>  $stuff[1],
			'text'  =>  \Mudasobwa\Eblo\Markright::yo($content['content'])
		);
	}

/* ================================================================================================ */
/* ========================               UTILS                 =================================== */
/* ================================================================================================ */

	/** Default sorter for files, having names like `YYYY-MM-DD-NN`. */
	public static function sorter($v_1, $v_2) {
		if ($v_1 === $v_2) { return 0; }

		$v1 = explode('-', $v_1);
		$v2 = explode('-', $v_2);

		if(count($v1) !== count($v2))
			return 0;

		for($i=0; $i<count($v1); $i++) {
			if(intval($v1[$i]) !== intval($v2[$i]))
				return intval($v2[$i]) - intval($v1[$i]);
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
	 * Getter for a meta data directory.
	 * @return string the path to the meta data directory as specified in config.
	 */
	private function metadir() {
		return __DIR__ . '/../../' . $this->config['data']['metadir'] . "/";
	}

	/**
	 * Getter for a relative path to a file in data directory.
	 * @param $name string the name of file to retrieve the full path for.
	 * @return string the path to the file in the data directory.
	 */
	private function pathto($name) {
		return $this->datadir() . $name;
	}

	/* ================================================================================================ */
	/* ========================                TAGS                 =================================== */
	/* ================================================================================================ */

	public function collections() {
		return array_merge(array_keys($this->tags), $this->metas);
	}
	/**
	 * Lazy loads predefined collection/tag.
	 * @param $name string the name of collection to load.
	 * @return array the array of collections.
	 */
	public function collection($name = self::DEFAULT_COLLECTION) {
		if(array_key_exists($name, $this->collections)) {
			return $this->collections[$name];
		}
		$filter = $name === self::DEFAULT_COLLECTION ?
				'' :
				preg_split(
					'/\n/sm',
					file_exists($this->metadir() . trim($name)) ?
						file_get_contents($this->metadir() . trim($name)) :
						array_key_exists($name, $this->tags) ?
								trim(`cd {$this->datadir()} && grep -Paor '{$this->tags[$name]}' * | cut -d: -f1 | uniq | sort`) :
								''
				);
		$datadir = $this->datadir();
		$files = array_filter(scandir($this->datadir()), function($f) use ($name, $filter, $datadir) {
			return (empty($filter) || in_array($f, $filter)) && !is_dir($datadir . $f);
		});
		return (count($files) > 0 && usort($files, $this->config['data']['sorter'])) ?
				$this->collections[$name] = $files : null;
	}

	/**
	 * Getter for a list of files specified with a regular expression.
	 * @todo Store the search as meta.
	 * @param $name string the name for the newly created collection.
	 * @param $regex string (generally speaking, a regexp) specifying the filenames of interest.
	 * @return array the list of files that match the regular exception given as parameter..
	 */
	public function find($name, $regex) {
		if(
			$name !== self::DEFAULT_COLLECTION &&
			!file_exists($this->metadir() . trim($name)) &&
			!array_key_exists($name, $this->tags)
		) {
			$this->tags[$name] = $regex;
		}
		return $this->collection($name);
	}

	/** Lookups collection for the specified name and sets the internal array pointer.
	 * @see #sorter
	 * @param $name string the name of the file to find previous for.
	 * @param $collection string the name of collection for lookup
	 * @param $steps int the amount of steps to move from origin (negative values for previous elements,
	 *        positive for nexts, zero (default) to return exact match.
	 * @param $neighborhood boolean if true, will return the array of results
	 * @return string filename if the file exists, `null` otherwise.
	 */
	public function lookup($name, $collection = self::DEFAULT_COLLECTION, $steps = 0, $neighborhood = false) {
		if(is_null($c = $this->collection($collection))) {
			return null;
		}
		while(current($c) !== $name) {
			next($c);
			if(is_null(key($c))) {
				return null;
			}
		}
		if(!$neighborhood) { // exact match
			for($i = 0; $i < abs($steps); $i++) {
				($steps > 0) ? next($c) : prev($c);
				if(is_null(key($c))) {
					return null;
				}
			}
			return current($c);
		}

		$result = array(0 => current($c));
		for($i = -1; $i >= -abs($steps); $i--) {
			if(is_null($result[$i + 1]) || (false === ($result[$i] = prev($c)))) {
				$result[$i] = null;
			}
		}
		for($i = -abs($steps) + 1; $i <= 0; $i++) {
			if (is_null($result[$i])) {
				continue;
			}
			is_null($result[$i - 1]) ? reset($c) : next($c);
		}
		for($i = 1; $i <= abs($steps); $i++) {
			$result[$i] = is_null(key($c)) ? null : next($c);
		}
		return $result;
	}

	public function locate($name) {
		return $this->lookup($name);
	}

	/** Getter for the previous file.
	 * @see #sorter
	 * @param $name string the name of the file to find previous for.
	 * @param $collection string the name of collection for lookup
	 * @param $steps int the amount of steps to move backward from origin.
	 * @return string filename if the file exists, `null` otherwise.
	 */
	public function prev($name, $collection = self::DEFAULT_COLLECTION, $steps = 1) {
		return $this->lookup($name, $collection, -abs($steps));
	}

	/** Getter for the next file.
	 * @see #sorter
	 * @param $name string the name of the file to find next for.
	 * @param $collection string the name of collection for lookup
	 * @param $steps int the amount of steps to move forward from origin.
	 * @return string filename if the file exists, `null` otherwise.
	 */
	public function next($name, $collection = self::DEFAULT_COLLECTION, $steps = 1) {
		return $this->lookup($name, $collection, abs($steps));
	}

	/** Get [cached] file content.
	 * @param $name string file name to retrieve the content for.
	 * @return array the file content of `null` if there is no such file.
	 */
	public function content($name) {
		if(isset($this->content[$name])) {
			$this->content[$name]['count'] = $this->content[$name]['count'] + 1;
			return $this->content[$name];
		}

		if(!file_exists($this->pathto($name)))
			return null;

		$content = trim(file_get_contents($this->pathto($name)));

		return $this->content[$name] = array(
				'count' => 1,
				'content' => $content,
				'title' => preg_match('/\A(.*)/mxu', $content, $m) ? $m[0] : ''
		);
	}

	public function reset($config = null) {
		if (null === $config) {
			$config = \Spyc::YAMLLoad(__DIR__.'/../config/.restark.yml');
		}
		$this->config		= $config;
		$this->tags			= $this->config['tags'];
		$metadir = $this->metadir();
		$this->metas		= array_filter(
			scandir($this->metadir()), function($f) use ($metadir) { return !is_dir($metadir . $f); }
		);
		$this->collections	= array();
		$this->content	= array();
	}

/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

	/** @todo SANITIZE */
	public function search($kw) {
		return explode("n", trim(`cd {$this->datadir()} && grep -Piaor '{$kw}' * | cut -d: -f1 | uniq | sort`));
	}

}
 
