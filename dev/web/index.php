<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Service Providers
use \Michelf\MarkdownExtra;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver' => 'pdo_mysql',
    'dbname' => 'camelcase'
  ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../views',
));

// Database Methods
function getMainMenu ($app) {
  $sql = "select title, slug from posts where status = 'published' and main_menu = 1 order by seq";
  $main_menu = $app['db']->fetchAll($sql);

  return $main_menu;
}

function getPage($app, $slug) {
  $sql = "select * from posts where status = 'published' and type = 'page' and slug = ?";
  $page = $app['db']->fetchAssoc($sql, array($slug));
  $page['content'] = markToHtml($page['content']);

  return $page;
}

function getPost($app, $slug) {
  $sql = "select * from posts where status = 'published' and type = 'post' and slug = ?";
  $post = $app['db']->fetchAssoc($sql, array($slug));
  $post['content'] = markToHtml($post['content']);

  return $post;
}

function getPosts($app) {
  $sql = "select * from posts where status = 'published' and type = 'post' order by publish_date desc";
  $pages = $app['db']->fetchAll($sql);

  return $pages;
}

function markToHtml($markdown) {
  $html = MarkdownExtra::defaultTransform($markdown);

  return $html;
}

$data = array(
  'main_menu' => getMainMenu($app)
);

// homepage
$app->get('/', function() use ($app) {
  global $data;

  $data['page'] = array(
    'title' => '',
    'slug' => 'home',
    'type' => 'page'
  );

  $data['posts'] = getPosts($app);

  return $app['twig']->render('index.html', $data);
});

// reserved page
$app->get('/{page_slug}', function($page_slug) use ($app) {
  global $data;

  $data['page'] = getPage($app, $page_slug);

  return $app['twig']->render('page.html', $data);
});

// post
$app->get('/post/{post_slug}', function($post_slug) use ($app) {
  global $data;

  $data['page'] = getPost($app, $post_slug);

  return $app['twig']->render('post.html', $data);
});
// 
$app['debug'] = true;

$app->run();