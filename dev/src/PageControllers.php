<?php

$app->get('/{page_slug}', function($page_slug) use ($app) {
  global $data;

  $data['page'] = getPublishedPost($app, $page_slug, 'page');

  if (!$data['page']) {
    $app->abort(404);
  }

  return $app['twig']->render('page.html', $data);
});
