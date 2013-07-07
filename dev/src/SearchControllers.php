<?php

use Symfony\Component\HttpFoundation\Request;

// search result page
$app->get('/search', function(Request $request) use ($app) {
  global $data;

  $data['query'] = $request->get('s');

  $data['post_nav'] = getPostsNav($app, 0, $data['query']);

  $total = 0;
  if ($data['post_nav']) {
    $total = $data['post_nav']['total'];
  }
  
  $data['posts'] = getPosts($app, 0, $total, $data['query']);
  $data['total_results'] = countSearchTotal($app, $data['query']);

  return $app['twig']->render('search-result.html', $data);
});

// search result pagination
$app->get('/search/{query}', function($query) use ($app) {
  global $data;

  $data['query'] = $query;

  $data['post_nav'] = getPostsNav($app, 0, $data['query']);

  $total = 0;
  if ($data['post_nav']) {
    $total = $data['post_nav']['total'];
  }
  
  $data['posts'] = getPosts($app, 0, $total, $data['query']);
  $data['total_results'] = countSearchTotal($app, $data['query']);

  return $app['twig']->render('search-result.html', $data);
});

$app->get('/search/{query}/{page_num}', function($query, $page_num) use ($app) {
  global $data;

  $data['query'] = $query;

  $data['post_nav'] = getPostsNav($app, $page_num, $data['query']);

  $total = 0;
  if ($data['post_nav']) {
    $total = $data['post_nav']['total'];
  }
  
  $data['posts'] = getPosts($app, $page_num, $total, $data['query']);
  $data['total_results'] = countSearchTotal($app, $data['query']);

  return $app['twig']->render('search-result.html', $data);
});
