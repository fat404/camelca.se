<?php

use Symfony\Component\HttpFoundation\Response;

$app->error(function (\Exception $e, $code) use ($app) {
  global $data;

  if ($app['debug']) {
    return;
  }

  switch ($code) {
    case 404:
      $data['title'] = 'Page Not Found';
      $data['message'] = 'Please use the search function to find what you are looking for.';
      break;
    
    default:
      $data['title'] = 'Server Error';
      $data['message'] = 'We are sorry, but something went terribly wrong.';
      break;
  }

  return new Response($app['twig']->render('404.html', $data, $code));
});
