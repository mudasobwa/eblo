<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require_once __DIR__.'/vendor/autoload.php';

use \Symfony\Component\HttpKernel\HttpKernelInterface,
	\Symfony\Component\HttpFoundation\Response,
	\Symfony\Component\HttpFoundation\Request,
	\Symfony\Component\HttpFoundation\JsonResponse;
use \Mudasobwa\Markright\Parser,
	\Mudasobwa\Eblo\Cache;

$app = new Silex\Application();

$app['debug'] = true;
$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

// Not more than 99 posts per day, not more than 99 parts of the post
$app['restark.regex'] = '^(\d{4}(\-\d{1,2}){0,5}\+?)+$';
$app['restark.config'] = \Spyc::YAMLLoad(__DIR__.'/.restark.yml');


///////////////////////////////////////////////////////////////////////////////
// HELPERS
///////////////////////////////////////////////////////////////////////////////

function buildResponse($files, $len, $offset) {
	return array(
		'prev' => $offset <= 0 ? null : $files[\max($offset - $len, 0)],
		'next' => \count($files) > $offset + $len ? $files[$offset + $len] : null,
		'html' => \array_map(
					array('\Mudasobwa\Markright\Parser', 'yo'),
					\array_map(
						array('\Mudasobwa\Eblo\Cache', 'load'),
						\array_slice($files, $offset, $len)
					)
				),
		'title' => '' // FIXME
	);
}

function urlFor($file) {
	return "/p/{$file}";
}

/* ================================================================================================ */
/* ========================               POSTS                 =================================== */
/* ================================================================================================ */

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
		$text = $cache->content($id);
		$result = array(
			'title' => (\preg_match('/\A(.*)/mxu', $text, $m)) ? $m[0] : '',
			'prev' => $cache->prev($id),
			'next' => $cache->next($id),
			'html' => Parser::yo($text)
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
$app->get('/pc', function (Silex\Application $app) {
	return $app->redirect(urlFor(Cache::instance()->files()[0], true));
});

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */



$app->run();