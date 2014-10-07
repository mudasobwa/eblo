<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) { // TODO
    return false;
}

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/php.src/Feeder.php';

use \Symfony\Component\HttpKernel\HttpKernelInterface,
	\Symfony\Component\HttpFoundation\Response,
	\Symfony\Component\HttpFoundation\Request,
	\Symfony\Component\HttpFoundation\JsonResponse;
use \Mudasobwa\Eblo\Parser,
	\Mudasobwa\Eblo\Cache;

const MY_MUSTACHES_LEFT		= '⦃';		// U+2983 &#10627;
const MY_MUSTACHES_RIGHT	= '⦄';		// U+2984 &#10628;

$app = new Silex\Application();

// Not more than 99 posts per day, not more than 99 parts of the post
$app['restark.config'] = \Spyc::YAMLLoad(__DIR__.'/config/.restark.yml');
$app['restark.atom'] = '/cache/atom.xml';
$app['restark.atomfile'] = __DIR__.$app['restark.atom'];
$app['restark.template'] = \file_get_contents($app['restark.config']['template']['article']);
$app['restark.regex'] = $app['restark.config']['template']['regex'];

$app['debug'] = $app['restark.config']['settings']['debug'];
$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

///////////////////////////////////////////////////////////////////////////////
// HELPERS
///////////////////////////////////////////////////////////////////////////////

function htmlFor($file, $collection) {
	if (!$collection) {
		$collection = \Mudasobwa\Eblo\Cache::DEFAULT_COLLECTION;
	}
  return "/★/{$collection}" . ($file ? "/{$file}" : '');
}

function jsonFor($file, $collection) {
    return $file ? "/☆/{$collection}/{$file}" : null;
}

/* ================================================================================================ */
/* ========================               POSTS                 =================================== */
/* ================================================================================================ */

/**
 * Main handler for HTTP requests.
 *
 * Accepts `id`s in the following forms:
 *   - `2000-12-24-1` for exact match
 *   - `2000-12-24` for all the posts, dated Dec 24, 2000
 *   - `2000-12-24+2000-12-23-20` for union of posts for Dec 24, 2000 and 20th post for Dec 23, 2000
 *
 * Accepts `offset` and `length` of the output (defaults to [0,9999])
 */
$app->get('/★/{collection}/{id}', function (Silex\Application $app, Request $req, $collection, $id) {
	if(!$id) {
		$c = Cache::instance()->collection($collection);
		$id = $c[0];
	}
	return new Response(
			\preg_replace('/(<(?:link|script)\s+(?:rel="\w+"\s+)?(?:href|src)=")(?=\w)/', '\1/', // FIXME dist preparation hotfix
					\preg_replace('/'.MY_MUSTACHES_LEFT.'(.*?)'.MY_MUSTACHES_RIGHT.'/', jsonFor($id, $collection), $app['restark.template'])
			)
	);
})
->assert('collection', '\w+')
->assert('id', $app['restark.regex'])
->value('id', 0)
;

/**
 * Main handler for ajax requests.
 *
 * @return JsonResponse the response
 */
$app->get('/☆/{id}/{len}/{offset}', function (Silex\Application $app, Request $req, $id, $len, $offset) {
	return $app->redirect("/☆/_/{$id}/{$len}/{$offset}");
})
->assert('id', $app['restark.regex'])
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

/**
 * Accepts `id`s in the following forms:
 *   - `2000-12-24-1` for exact match
 *   - `2000-12-24` for all the posts, dated Dec 24, 2000
 *   - `2000-12-24+2000-12-23-20` for union of posts for Dec 24, 2000 and 20th post for Dec 23, 2000
 *
 * Accepts `offset` and `length` of the output (defaults to [0,9999])
 */
$app->get('/☆/{collection}/{id}/{len}/{offset}', function (Silex\Application $app, Request $req, $collection, $id, $len, $offset) {
	$data = \Mudasobwa\Eblo\Cache::yo($id, $collection);
	$result = array_map(function ($item) use (&$data, $collection) {
				$fixed = preg_match('/prev|next/', key($data)) === 0 ? $item : jsonFor($item, $collection);
				next($data);
				return $fixed;
			}, $data
	);
	$jr = new JsonResponse($result);
	return $jr->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
})
->assert('collection', '\w+')
->assert('id', $app['restark.regex'])
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

/** Retrieves the collections list */
$app->get('/▶', function (Silex\Application $app, Request $req) {
	return new JsonResponse(
			\array_map(function ($elem) {
				return array(
						'url'   => htmlFor('', $elem),
						'title' => preg_replace_callback(
								'/(?<=\A|\s)(\w)/',
								function($s) { return mb_strtoupper($s[1]); },
								preg_replace('/_/', ' ', $elem)
						)
				);
			}, Cache::instance()->collections())
	);
});

/* ================================================================================================ */
/* ========================               LEGACY                =================================== */
/* ================================================================================================ */

/* ================================================================================================ */
/* ========================               RANDOM                =================================== */
/* ================================================================================================ */
/** Retrieves the content for random amount of files */
$app->get('/∀/{count}', function (Silex\Application $app, Request $req, $count) {
	$files = Cache::instance()->collection();
	shuffle($files);
	$files = \array_slice($files, 0, $count);
	return new JsonResponse(
		\array_map(function ($elem) {
			$c = Cache::instance()->content($elem);
			return array(
					'url'   => htmlFor($elem, null),
					'title' => $c['title']
			);
		}, $files)
	);
})
->assert('count', '\d+')
;

/* ================================================================================================ */
/* ========================                RSS                  =================================== */
/* ================================================================================================ */

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/✍/', function (Silex\Application $app) {
	if(!file_exists($app['restark.atomfile'])) {
		$rss = new UniversalFeedCreator();
		$rss->useCached();
		$rss->title = "Mudasobwa’s Eblo";
		$rss->description = "Ipsissima Verba";
		$rss->link = "http://" . $app['restark.config']['main']['domain'];
		$rss->syndicationURL = "http://" . $app['restark.config']['main']['domain'] . "/rss";

		$image = new FeedImage();
		$image->title = "Buggy";
		$image->url = "http://" . $app['restark.config']['main']['domain'] . "/cache/buggy.jpg";
		$image->link = "http://" . $app['restark.config']['main']['domain'];
		$image->description = "Ipsissima Verba";
		$rss->image = $image;

		$cache = Cache::instance();
		foreach($cache->files($app['restark.config']['settings']['rss']) as $f) {
			$item = new FeedItem();
			$text = $cache->content($f);
			$item->title = (\preg_match('/\A(.*)/mxu', $text, $m)) ? $m[0] : '';
			$item->link = "http://" . $app['restark.config']['main']['domain'] . "/" . $f;
			$item->description = \Mudasobwa\Eblo\Markright::yo($text);
			$item->date = Cache::getDateByFileName($f);
			$item->source = "http://" . $app['restark.config']['main']['domain'];
			$item->authorEmail = "am@mudasobwa.ru";
			$item->author = "Aleksei “Mudasobwa” Matiushkin";
			$rss->addItem($item);
		}

		$rss->saveFeed($app['restark.config']['settings']['rssformat'], $app['restark.atomfile']);
	}
	return Response::create(
			file_get_contents($app['restark.atomfile']),
			200,
			array('Content-Type' => 'application/rss+xml; charset=utf-8')
	);
});

/* ================================================================================================ */
/* ========================           MAIN HANDLER              =================================== */
/* ================================================================================================ */

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/{collection}', function (Silex\Application $app, Request $req, $collection) {
	return $app->redirect(htmlFor('', $collection));
})
->assert('collection', '\w+')
->value('collection', '')
;


$app->run();