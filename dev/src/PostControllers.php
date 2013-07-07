<?php
$app->get('/', function() use ($app) {
  global $data;

  $data['posts'] = getPosts($app);
  $data['post_nav'] = getPostsNav($app);

  if (!$data['posts']) {
    $app->abort(500);
  }

  return $app['twig']->render('index.html', $data);
});

$app->get('/archive/{page_num}', function($page_num) use ($app) {
  global $data;

  $data['post_nav'] = getPostsNav($app, $page_num);
  $data['posts'] = getPosts($app, $page_num, $data['post_nav']['total']);

  if (!$data['posts']) {
    $app->abort(500);
  }

  return $app['twig']->render('index.html', $data);
});

// post
$app->get('/post/{post_slug}', function($post_slug) use ($app) {
  global $data;

  $data['page'] = getPublishedPost($app, $post_slug, 'post');
  $data['post_nav'] = getPostNav($app, $data['page']);

  if (!$data['page']) {
    $app->abort(404);
  }

  return $app['twig']->render('post.html', $data);
});
