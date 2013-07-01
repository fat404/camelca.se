<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Configuration
$posts_per_page = 10;

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

function getPostNav($app, $publish_date, $current_id) {
  $prev_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id < ?) or (publish_date < timestamp(?) and id > ?))and type = 'post' order by publish_date desc, id desc limit 1";
  $prev_post = $app['db']->fetchAssoc($prev_sql, array($publish_date, $current_id, $publish_date, $current_id));

  if ($current_id == $prev_post['id']) {
    $prev_post['slug'] = 0;
  }

  $next_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id > ?) or (publish_date > timestamp(?) and id < ?))and type = 'post' order by publish_date, id limit 1";
  $next_post = $app['db']->fetchAssoc($next_sql, array($publish_date, $current_id, $publish_date, $current_id));

  if ($current_id == $next_post['id']) {
    $next_post['slug'] = 0;
  }

  $post_nav = array(
    'prev' => $prev_post['slug'],
    'prev_title' => $prev_post['title'],
    'next' => $next_post['slug'],
    'next_title' => $next_post['title']
  );

  return $post_nav;
}

function getPosts($app, $current = 0, $total = 0) {
  global $posts_per_page;
  $sql = '';
  $offset = 0;

  if ($current < 0) {
    $current = 1;
  }

  if ($current > 0 && $current < $total) {
    $offset = ($total - $current) * $posts_per_page;
  }

  $sql = "select * from posts where status = 'published' and type = 'post' order by publish_date desc, id desc limit $offset, $posts_per_page";

  $pages = $app['db']->fetchAll($sql);

  return $pages;
}

function getPostsNav($app, $current = 0) {
  global $posts_per_page;
  $prev = 0;
  $next = 0;

  $sql = "select count(*) from posts where status = 'published' and type = 'post'";
  $total = $app['db']->fetchColumn($sql);

  $total = (int) ceil($total / $posts_per_page);

  if ($current == 0 || $current > $total) {
    $current = $total;
  }

  if ($current < 0) {
    $current = 1;
  }

  if ($current > 1) {
    $prev = $current - 1;
  }

  if ($current + 1 <= $total) {
    $next = $current + 1;
  }

  if (!$prev && !$next) {
    $total = 0;
  }

  $posts_nav = array(
    'total' => $total,
    'current' => $current,
    'prev' => $prev,
    'next' => $next
  );

  return $posts_nav;
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
    'type' => 'page',
    'post_nav' => 0
  );

  $data['posts'] = getPosts($app);
  $data['post_nav'] = getPostsNav($app);
  if ($data['post_nav']['total']) {
    $data['page']['post_nav'] = 1;
  }

  return $app['twig']->render('index.html', $data);
});

// archive pagination
$app->get('/archive/{page_num}', function($page_num) use ($app) {
  global $data;

  $data['page'] = array(
    'title' => '',
    'slug' => 'home',
    'type' => 'page',
    'post_nav' => 0
  );

  $data['post_nav'] = getPostsNav($app, $page_num);
  $data['page']['post_nav'] = $data['post_nav']['total'];
  
  $data['posts'] = getPosts($app, $page_num, $data['post_nav']['total']);

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
  $data['post_nav'] = getPostNav($app, $data['page']['publish_date'], $data['page']['id']);

  if ($data['post_nav']['prev'] || $data['post_nav']['next']) {
    $data['page']['post_nav'] = 1;
  }

  return $app['twig']->render('post.html', $data);
});
// 
$app['debug'] = true;

$app->run();