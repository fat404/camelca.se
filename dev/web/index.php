<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../src/DatabaseMethods.php'; // Database Methods
require_once __DIR__.'/../src/HelperMethods.php'; // Database Methods

$app = new Silex\Application();


/*
 * Configuration
 */
$app['conf'] = array(
  'posts_per_page' => 10,
  'search_results_per_page' => 50,
  'username' => 'admin',
  'bcrypt' => '$2y$10$BQ1qC4S0YmOChM0SVwoYE.X6UrY00ngxR1W4vBFr69MEnoTCSYeGK',
  'session_timeout' => 14, //days
  'dbname' => 'camelcase',
  'dbuser' => 'root',
  'dbpass' => ''
);

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

// $app['debug'] = true;
require_once __DIR__.'/../src/ErrorControllers.php';


$app->run();