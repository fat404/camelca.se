<?php
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

// post
$app->get('/post/{post_slug}', function($post_slug) use ($app) {
  global $data;

  $data['page'] = getPublishedPost($app, $post_slug, 'post');
  $data['post_nav'] = getPostNav($app, $data['page']['publish_date'], $data['page']['id']);

  if ($data['post_nav']['prev'] || $data['post_nav']['next']) {
    $data['page']['post_nav'] = 1;
  }

  return $app['twig']->render('post.html', $data);
});
