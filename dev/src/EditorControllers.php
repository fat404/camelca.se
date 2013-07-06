<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$editor = array(
  'title' => '',
  'slug' => '',
  'publish_date' => time(),
  'content' => '',
  'preview' => '',
  'type' => '',
  'main_menu' => '',
  'seq' => '',
  'status' => ''
);

$app->get('/getpass/{pass}', function ($pass) use ($app) {
  return $app['twig']->render('getpass.html', array(
    'hash' => password_hash($pass, PASSWORD_BCRYPT)
  ));
});

$app->get('/login', function () use ($app) {
  return $app['twig']->render('login.html');
});

$app->post('/login', function (Request $request) use ($app) {
  $username = $request->get('username');
  $password = $request->get('password');

  if ($username === $app['conf']['username'] && password_verify($password, $app['conf']['bcrypt'])) {
    $app['session']->set('user', array(
      'username' => $username,
      'logged_in' => time()
    ));

    return $app->redirect('/editor');
  }

  return $app['twig']->render('login.html', array('message' => 'Incorrect login.'));
});

$app->get('/logout', function () use ($app) {
  $app['session']->clear();

  return $app->redirect('/login');
});

$app->get('/editor', function() use ($app) {
  if (!$app['session']->get('user')) {
    return $app->redirect('/login');
  }

  if (time() - $app['session']->get('user')['logged_in'] > ($app['conf']['session_timeout'] * 24 * 60 * 60)) {
    return $app->redirect('/login');
  }

  global $editor;

  return $app['twig']->render('editor.html', $editor);
});

$app->post('/editor', function(Request $request) use ($app) {
  if (!$app['session']->get('user')) {
    return $app->redirect('/login');
  }

  if (time() - $app['session']->get('user')['logged_in'] > ($app['conf']['session_timeout'] * 24 * 60 * 60)) {
    return $app->redirect('/login');
  }

  global $editor;

  $content = $request->get('content');
  $slug = trim(slugify($request->get('title')));
  $action = $request->get('action');

  $editor['title'] = $request->get('title');
  $editor['slug'] = $slug;
  $editor['content'] = $request->get('content');
  $editor['preview'] = markToHtml($content);
  $editor['publish_date'] = $request->get('publish_date');
  $editor['type'] = $request->get('type');
  $editor['main_menu'] = $request->get('main_menu');
  $editor['seq'] = $request->get('seq');
  $editor['status'] = $request->get('status');

  if ($action != 'Preview') {
    $status = 0;

    if ($action == 'Publish') {
      $editor['status'] = 'published';
    } elseif ($action == 'Unpublish') {
      $editor['status'] = 'unpublished';
    } else {
      if (!$editor['status']) {
        $editor['status'] = 'draft';
      }
    }
    
    if (!getPost($app, $slug)) {
      $status = insertIntoPosts($app, $editor);
    } else {
      $status = updatePost($app, $editor);
    }

    if ($editor['status'] == 'published') {
      $is_post = $editor['type'] == 'post' ? 'post/' : '';

      $editor['message'] = 'Published: <a href="/'.$is_post.$slug.'" target="_blank">/'.$slug.'</a>';
    } elseif ($status) {
      $editor['message'] = 'Page saved';
    }
  }

  // $editor['message'] = var_dump(getPost($app, 'test'));

  return $app['twig']->render('editor.html', $editor);
});
