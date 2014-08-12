<?php

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../vendor/mudasobwa/markright/src/Parser.php';

use Symfony\Component\HttpFoundation\Response,
	\Symfony\Component\HttpFoundation\JsonResponse;

$app = new Silex\Application();

$app['debug'] = true;
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

$app->get('/', function (Silex\Application $app) {
	$subRequest = Request::create('/pp/★', 'GET');
	return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);;
});

$app->get('/p/{id}', function (Silex\Application $app, $id) {
    if (!file_exists("p/{$id}")) {
        $app->abort(404, "Post $id does not exist.");
    }

    $output = file_get_contents("p/{$id}");
    return new Response(\Mudasobwa\Markright\Parser::yo($output));
})->assert('id', '\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}');

$app->get('/pj/{id}', function (Silex\Application $app, $id) {
    if (!file_exists("p/{$id}")) {
        $app->abort(404, "Post $id does not exist.");
    }

    $output = file_get_contents("p/{$id}");
    $r = new JsonResponse(['html' => \Mudasobwa\Markright\Parser::yo($output)]);
	$r->setEncodingOptions(JSON_UNESCAPED_UNICODE);
	return $r;
})->assert('id', '\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2}');

$app->run();
