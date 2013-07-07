<?php

use \Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;

function markToHtml($markdown) {
  $html = MarkdownExtra::defaultTransform($markdown);

  return $html;
}

function slugify($text) { 
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

  // trim
  $text = trim($text, '-');

  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // lowercase
  $text = strtolower($text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  if (empty($text))
  {
    return 'n-a';
  }

  return $text;
}

function getKeywordsFromQuery($query, $column) {
  $keywords = explode(' ', $query);

  $keywords = array_map(function($keyword) {
    return '\'%' . $keyword . '%\'';
  }, $keywords);

  $partial = implode(" OR $column like ", $keywords);

  return $partial;
}

$validateSession = function (Request $request, $app) {
  $relog = 1;

  if (!$app['session']->get('user')) {
  } else {
    $user = $app['session']->get('user');
    $logged_in = $user['logged_in'];
    $timeout = $app['conf']['session_timeout'] * 24 * 60 * 60;

    if (time() - $logged_in < $timeout) {
      $relog = 0;
    }
  }

  if ($relog) {
    return $app->redirect('/login');
  }
};