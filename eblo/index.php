<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require_once 'vendor/autoload.php';
require_once 'vendor/mudasobwa/markright/src/Parser.php';
require_once 'src/Cache.php';

use \Symfony\Component\HttpKernel\HttpKernelInterface,
	\Symfony\Component\HttpFoundation\Response,
	\Symfony\Component\HttpFoundation\Request,
	\Symfony\Component\HttpFoundation\JsonResponse;
use \Mudasobwa\Markright\Parser,
	\Mudasobwa\Eblo\Cache;

// Helpers
function yo($s) { return Parser::yo($s); }
function p___($s, $web_path = false) { return $web_path ? "/p/{$s}" : "p/{$s}"; }
//	$app['monolog']->addDebug($id, Cache::instance()->files());

$app = new Silex\Application();

$app['debug'] = true;
$app->register(new \Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));

$app['fname_regex'] = '^(\d{4}(\-\d{1,2}){0,5}\+?)+$';

/*
$app->error(function (\Exception $e, $code) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message);
});
*/

/*
 * Setting cookie: http://stackoverflow.com/questions/13021440/silex-set-cookie
	$dt = new \DateTime();
	$dt->modify("+1 year");
	$c = new Cookie("juniorkupon_letoltve", "1", $dt);
	$r = new Response(file_get_contents(ROOT . "/data/kupon.pdf"), 200, array("Content-Type" => "application/pdf"));
	$r->headers->setCookie($c);
 * */

/*
 * CACHE: http://stackoverflow.com/questions/829126/how-to-implement-a-php-html-cache
 */

$app->get('/', function (Silex\Application $app, Request $req) {
	$subRequest = Request::create('/pp/★', 'GET');
	return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
});

/* ================================================================================================ */
/* ========================                POSTS                =================================== */
/* ================================================================================================ */

/**
 * Accepts `id`s in the following forms:
 *   - `2000-12-24-10-01-52` for exact match
 *   - `2000-12-24` for all the posts, dated Dec 24, 2000
 *   - `2000-12-24+2000-12-23-20` for union of posts for Dec 24, 2000 and Dec 23, 2000 (written at 8:00PM)
 *   - **[NYI]** `2000-12-24—2000-12-26` for all the posts, written from Dec 24 to Dec 26, 2000, inclusive (note mdash between dates)
 *
 * Accepts `offset` and `length` of the output (defaults to [0,9999])
 */
$app->get('/p/{id}/{len}/{offset}', function (Silex\Application $app, Request $req, $id, $len, $offset) {

	if(false !== ($curr = \array_search($file = $id, $files = Cache::instance()->files()))) {
		$text = \file_get_contents(p___($file));
		$title = (\preg_match('/\A(.*)/mxu', $text, $m)) ? $m[0] : '';

		$prev = $curr > 0 ? p___($files[$curr - 1], true) : null;
		$next = $curr < count($files) - 1 ? p___($files[$curr + 1], true) : null;
		$html = '<article class="alone">' . yo($text) . '</article>';
	} else {
		$files = array();
		foreach(\explode('+', $id) as $file) {
			$files = \array_merge($files, \glob(p___($file)));
			if(\count($files) >= $offset + $len) {
				break;
			}
		}

		$prev = $offset > 0 ? $files[$offset - 1] : null;
		$next = \count($files) > $offset + $len ? $files[$offset + $len] : null;
		$html = '<article class="one-of">' .
			\implode(
				'</article><article class="one-of">',
				\array_map('yo', \array_map('file_get_contents', \array_slice($files, $offset, $len)))
			) . '</article>';
		$title = (\preg_match('/<h1>(.*?)<\/h1>/imxsu', $html, $m)) ? \implode(' | ', $m) : ''; // FIXME NOT TESTED
	}


	return (new JsonResponse([
		'html' => $html, 'title' => $title, 'prev' => $prev, 'next' => $next
	]))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
})
->assert('id', $app['fname_regex'])
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

$app->get('/pa/{len}/{offset}', function (Silex\Application $app, Request $req, $len, $offset) {
	$files = Cache::instance()->files();
	$o_minus_l = $offset - $len;
	$o_plus_l = $offset + $len;

	return (new JsonResponse(
		[
			'html' => '<article>' . \implode(
				'</article><article>',
				\array_map('yo', \array_map('file_get_contents', \array_map('p___', \array_slice($files, $offset, $len))))
				) . '</article>',
			'prev' => $offset >= $len ? "/pa/{$len}/{$o_minus_l}" : null,
			'next' => \count($files) > $offset + $len ? "/pa/{$len}/{$o_plus_l}" : null
		]
	))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
})
->value('offset', 0)
->value('len', 9999) // FIXME the app would not return more than this value posts at once
;

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/pc', function (Silex\Application $app) {
	return $app->redirect(p___(Cache::instance()->files()[0], true));
});


/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

/** Retrieves all the tags mapped to addresses as JSON */
$app->get('/tj', function () {
    $r = new JsonResponse(Cache::instance()->tags());
	$r->setEncodingOptions(JSON_UNESCAPED_UNICODE);
	return $r;
});

/** Retrieves the one random post for the tag specified (directly) */
$app->get('/tr/{tag}', function (Silex\Application $app, $tag) {
	$tg = Cache::instance()->tags($app->escape($tag));
	if(!isset($tg))
		$app->abort(404, "Tag {$app->escape($tag)} does not exist.");
	return (new JsonResponse(
		[
			'html' => yo(\file_get_contents(p___($tg[\array_rand($tg)]))),
			'prev' => "/tr/{$tag}",
			'next' => "/tr/{$tag}"
		]
	))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
});

/** Retrieves the content for the tag specified (directly) */
$app->get('/t1/{tag}', function (Silex\Application $app, $tag) {
	$tg = Cache::instance()->tags($app->escape($tag));
	if(!isset($tg))
		$app->abort(404, "Tag {$app->escape($tag)} does not exist.");

	return (new JsonResponse(
		[
			'html' => '<article>' . \implode(
				'</article><article>',
				\array_map('yo', \array_map('file_get_contents', \array_map('p___', $tg)))
				) . '</article>',
			'prev' => null,
			'next' => null
		]
	))->setEncodingOptions(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
});

/** Retrieves the content for the tag specified by forwarding to `/p/A+B+C` notation */
$app->get('/t2/{tag}', function (Silex\Application $app, $tag) {
	return $app->handle(
		Request::create(p___(\implode('+', Cache::instance()->tags($app->escape($tag)), 'GET'), true), HttpKernelInterface::SUB_REQUEST)
	);
});

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/t3/{tag}', function (Silex\Application $app, $tag) {
	return $app->redirect(p___(\implode('+', Cache::instance()->tags($app->escape($tag))), true));
});


/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/s3/{kw}', function (Silex\Application $app, Request $req, $kw) {
	return $app->redirect(p___(\implode('+', Cache::instance()->search($app->escape($kw))), true));
});


/* ================================================================================================ */
/* ========================                 RUN                 =================================== */
/* ================================================================================================ */

$app->run();

