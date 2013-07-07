<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/Configuration.php';
require_once __DIR__.'/../src/HelperMethods.php'; // Helper Methods
require_once __DIR__.'/../src/DatabaseMethods.php'; // Database Methods

$app = new Silex\Application();


/*
 * Configuration
 */
$app['debug'] = true;
$app['conf'] = $configuration;

date_default_timezone_set('Asia/Kuala_Lumpur');


/*
 * Service Providers
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver' => 'pdo_mysql',
    'dbname' => $app['conf']['dbname'],
    'user' => $app['conf']['dbuser'],
    'password' => $app['conf']['dbpass']
  ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\SessionServiceProvider());


/*
 * Controllers
 */
$data = array(
  'main_menu' => getMainMenu($app)
);

require_once __DIR__.'/../src/EditorControllers.php';

require_once __DIR__.'/../src/PostControllers.php';
require_once __DIR__.'/../src/SearchControllers.php';
require_once __DIR__.'/../src/PageControllers.php';

require_once __DIR__.'/../src/ErrorControllers.php';


$app->run();