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
function yo($s) {
	return Parser::yo($s);
}

$app = new Silex\Application();

$app['debug'] = true;

$app['fname_regex'] = '^(\d{4}(\-\d{2}){0,5}\+?)+$';

function prepend_p($s) { return 'p/' . $s; }

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

$app->get('/', function (Silex\Application $app) {
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
 */
$app->get('/p/{id}', function (Silex\Application $app, $id) {
	$output = [];
	foreach(\explode('+', $id) as $file) {
		$output = \array_merge($output, \array_map('yo', \array_map('file_get_contents', \glob("p/{$file}*"))));
	}

	return new Response(implode('<hr class="separator">', $output));
})->assert('id', $app['fname_regex']);

$app->get('/pj/{id}', function (Silex\Application $app, $id) {
    if (!file_exists("p/{$id}")) {
        $app->abort(404, "Post {$id} does not exist.");
    }

    $output = file_get_contents("p/{$id}");
    $r = new JsonResponse(['html' => \Mudasobwa\Markright\Parser::yo($output)]);
	$r->setEncodingOptions(JSON_UNESCAPED_UNICODE);
	return $r;
})->assert('id', $app['fname_regex']);

/* ================================================================================================ */
/* ========================                TAGS                 =================================== */
/* ================================================================================================ */

/** Retrieves all the tags mapped to addresses as JSON */
$app->get('/tj', function () {
    $r = new JsonResponse(Cache::instance()->tags());
	$r->setEncodingOptions(JSON_UNESCAPED_UNICODE);
	return $r;
});

/** Retrieves the content for the tag specified (directly) */
$app->get('/t1/{tag}', function (Silex\Application $app, $tag) {
	$tags = Cache::instance()->tags();
	if(!isset($tags[$tag]))
		$app->abort(404, "Tag {$tag} does not exist.");

	$output = \array_map('yo', \array_map('file_get_contents', \array_map('prepend_p', $tags[$tag])));
	return new Response(\implode('<hr class="separator">', $output));
});

/** Retrieves the content for the tag specified by forwarding to `/p/A+B+C` notation */
$app->get('/t2/{tag}', function (Silex\Application $app, $tag) {
	$subRequest = Request::create('/p/' . \implode('+', Cache::instance()->tags()[$tag]), 'GET');
	return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
});

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/t3/{tag}', function (Silex\Application $app, $tag) {
	return $app->redirect('/p/' . \implode('+', Cache::instance()->tags()[$tag]));
});


/* ================================================================================================ */
/* ========================               SEARCH                =================================== */
/* ================================================================================================ */

/** Retrieves the content for the tag specified by redirecting to `/p/A+B+C` notation */
$app->get('/s3/{kw}', function (Silex\Application $app, $kw) {
	return $app->redirect('/p/' . \implode('+', Cache::instance()->search($kw)));
});


/* ================================================================================================ */
/* ========================                 RUN                 =================================== */
/* ================================================================================================ */

$app->run();

