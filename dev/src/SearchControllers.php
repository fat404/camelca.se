<?php

use Symfony\Component\HttpFoundation\Request;

// search result page
$app->get('/search', function(Request $request) use ($app) {
  global $data;

  $query = $request->get('s');
  $page_num = 0;

  $data['page'] = array(
    'title' => "Search result(s) for '$query'",
    'slug' => 'home',
    'type' => 'page',
    'post_nav' => 0,
    'query' => $query,
  );

  $data['post_nav'] = getPostsNav($app, $page_num, $query);
  $data['page']['post_nav'] = $data['post_nav']['total'];
  $data['page']['total_results'] = $data['post_nav']['raw_total'];
  
  $data['posts'] = getPosts($app, $page_num, $data['post_nav']['total'], $query);

  return $app['twig']->render('search-result.html', $data);
});

// search result pagination
$app->get('/search/{query}', function($query, $page_num = 0) use ($app) {
  global $data;

  $data['page'] = array(
    'title' => "Search result(s) for '$query'",
    'slug' => 'home',
    'type' => 'page',
    'post_nav' => 0,
    'query' => $query,
  );

  $data['post_nav'] = getPostsNav($app, $page_num, $query);
  $data['page']['post_nav'] = $data['post_nav']['total'];
  $data['page']['total_results'] = $data['post_nav']['raw_total'];
  
  $data['posts'] = getPosts($app, $page_num, $data['post_nav']['total'], $query);

  return $app['twig']->render('search-result.html', $data);
});

$app->get('/search/{query}/{page_num}', function($query, $page_num) use ($app) {
  global $data;

  $data['page'] = array(
    'title' => "Search result(s) for '$query'",
    'slug' => 'home',
    'type' => 'page',
    'post_nav' => 0,
    'query' => $query,
  );

  $data['post_nav'] = getPostsNav($app, $page_num, $query);
  $data['page']['post_nav'] = $data['post_nav']['total'];
  $data['page']['total_results'] = $data['post_nav']['raw_total'];
  
  $data['posts'] = getPosts($app, $page_num, $data['post_nav']['total'], $query);

  return $app['twig']->render('search-result.html', $data);
});
