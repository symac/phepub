<?php


$app['config'] = parse_ini_file(__DIR__.'/../params.ini', true);

$app['debug'] = $app['config']['global']['debug'];

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => $app['config']['database']['host'],
        'dbname' => $app['config']['database']['dbname'],
        'user' => $app['config']['database']['user'],
        'password' => $app['config']['database']['password'],
				'charset' => 'utf8',
    )
));
$app['cache.dir'] = __DIR__ . '/../cache';

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
    'twig.options'    => array(
        'cache' => __DIR__ . '/../cache/twig',
    ),
));


$app['dao.book'] = function ($app) { return new Phepub\DAO\BookDAO($app['db']); };
$app['dao.lesson'] = function ($app) { return new Phepub\DAO\LessonDAO($app['db']); };
