<?php

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

function htmlFor($file) {
    return $file ? "/{$file}" : null;
}

function jsonFor($file) {
    return $file ? "/p/{$file}" : null;
}

function buildResponse($files, $len, $offset) {
	return array(
		'prev' => $offset <= 0 ? null : jsonFor($files[\max($offset - $len, 0)]),
		'next' => \count($files) > $offset + $len ? jsonFor($files[$offset + $len]) : null,
		'text' => \array_map(
				array('\Mudasobwa\Eblo\Markright', 'yo'),
				\array_map(
						array('\Mudasobwa\Eblo\Cache', 'load'),
						\array_slice($files, $offset, $len)
				)
		),
		'title' => '' // FIXME
	);
}

/* ================================================================================================ */
/* ========================               POSTS                 =================================== */
/* ================================================================================================ */


$app->get('/{id}/{len}/{offset}', function (Silex\Application $app, Request $req, $id, $len, $offset) {
	// FIXME Not simple ID here
	return new Response(
		\preg_replace('/'.MY_MUSTACHES_LEFT.'(.*?)'.MY_MUSTACHES_RIGHT.'/', jsonFor($id), $app['restark.template'])
	);
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
$app->get('/p/{id}/{len}/{offset}', function (Silex\Application $app, Request $req, $id, $len, $offset) {
	$cache = Cache::instance();
	if($cache->locate($id)) {
		$content = $cache->content($id);
		$result = array(
			'title' => $content['title'],
			'prev' => jsonFor($cache->prev($id)),
			'next' => jsonFor($cache->next($id)),
			'text' => \Mudasobwa\Eblo\Markright::yo($content['content'])
		);
	} else {
		$files = array();
		foreach(\explode('+', $id) as $file) {
			$files = \array_merge($files, $cache->find($file));
			if(\count($files) > $offset + $len) {
				break;
			}
		}
		$result = buildResponse($files, $len, $offset);
	}

	return (new JsonResponse($result))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
})
->assert('id', $app['restark.regex'])
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

$app->get('/ps/{len}/{offset}', function (Silex\Application $app, Request $req, $len, $offset) {
	return (new JsonResponse(
		buildResponse(Cache::instance()->files(), $len, $offset)
	))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
})
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
// $app->get('/pc', function (Silex\Application $app) {
// 	return $app->redirect(jsonFor(Cache::instance()->files()[0], true));
// });

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/', function (Silex\Application $app) {
	return $app->redirect(htmlFor(Cache::instance()->files()[0], true));
});

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

/* ================================================================================================ */
/* ========================               LEGACY                =================================== */
/* ================================================================================================ */
$app->get('/post/show/{id}', function (Silex\Application $app, $id) {
	return $app->redirect(htmlFor(\array_reverse(Cache::instance()->files())[$id], true));
})
->assert('count', '\d+')
;

/* ================================================================================================ */
/* ========================               RANDOM                =================================== */
/* ================================================================================================ */
/** Retrieves the content for random amount of files */
$app->get('/r/{count}', function (Silex\Application $app, Request $req, $count) {
	$files = Cache::instance()->files();
	shuffle($files);
	$files = \array_slice($files, 0, $count);
	return new JsonResponse(
		\array_map(function ($elem) {
			return array(
					'url'   => htmlFor($elem, true),
					'title' => Cache::instance()->content($elem)['title']
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
$app->get('/rss', function (Silex\Application $app) {
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
	return $app->redirect($app['restark.atom']);
});

$app->run();