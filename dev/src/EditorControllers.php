<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$editor = array(
  'id' => '',
  'title' => '',
  'slug' => '',
  'publish_date' => time(),
  'content' => '',
  'html' => '',
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
  global $editor;

  return $app['twig']->render('editor.html', $editor);
})->before($validateSession);

$app->post('/editor', function(Request $request) use ($app) {
  global $editor;

  $id = $request->get('id');
  $content = $request->get('content');
  $action = $request->get('action');

  $editor = array(
    'id' => $id,
    'title' => $request->get('title'),
    'slug' => trim(slugify($request->get('title'))),
    'content' => $content,
    'html' => markToHtml($content),
    'publish_date' => $request->get('publish_date'),
    'type' => $request->get('type'),
    'main_menu' => $request->get('main_menu'),
    'seq' => $request->get('seq'),
    'status' => $request->get('status')
  );

  $status_code = 0;

  if ($action != 'Preview') {
    if ($action == 'Publish') {
      $editor['status'] = 'published';
    } elseif ($action == 'Unpublish') {
      $editor['status'] = 'unpublished';
    } else {
      if (!$editor['status']) {
        $editor['status'] = 'draft';
      }
    }
    if ($id && getPostById($app, $id)) {
      $status_code = updatePost($app, $id, $editor);
    } else {
      $slug = trim(slugify($request->get('title')));
      if (!getPost($app, $slug)) {
        $status_code = insertIntoPosts($app, $editor);
      } else {
        $status_code = 20;
      }
    }
  }

  $subRequest = Request::create('/editor/yes', 'POST', array(
    'status_code' => $status_code,
    'editor' => $editor
  ));

  return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
})->before($validateSession);

$app->get('/editor/{slug}', function($slug) use ($app) {

  $editor = getPost($app, $slug);

  if (!$editor) {
    return $app->redirect('/editor');
  }

  return $app['twig']->render('editor.html', $editor);
})->before($validateSession);

$app->post('/editor/yes', function(Request $request) use ($app) {
  global $editor;
  $status_code = $request->get('status_code');
  $editor = $request->get('editor');

  if (!$editor) {
    $editor['message'] = 'Error occured';
  } else {
    if ($status_code) {
      if ($editor['id']) {
        $editor = getPostById($app, $editor['id']);
      } else {
        $editor = getPost($app, $editor['slug']);
      }

      if ($status_code != 20) {
        $editor['message'] = 'Page saved. ';
      } elseif ($status_code == 20) {
        $editor['message'] = 'Error: Slug already exists';
      }
    }

    if ($editor['status'] == 'published') {
      $is_post = $editor['type'] == 'post' ? 'post/' : '';
      $editor['message'] .= '<a href="/'.$is_post.$editor['slug'].'" target="_blank">/'.$is_post.$editor['slug'].'</a>';
    }
  }

  return $app['twig']->render('editor.html', $editor);
})->before($validateSession);


$app->post('/editor/delete', function (Request $request) use ($app) {
  global $editor;

  $id = $request->get('id');

  if (!$id) {
    $editor['message'] = "Error: No ID";
    return $app['twig']->render('editor.html', $editor);
  } else {
    $post = getPostById($app, $id);

    if ($post) {
      if ($request->get('action') == 'Yes') {
        $title = $post['title'];
        $slug = $post['slug'];

        $status = deletePost($app, $id);

        if ($status) {
          $editor['message'] = "Post $title ($slug) deleted";
        } else {
          $editor['message'] = "Error: Cannot delete $title (<a href=\"/editor/$slug\">$slug</a>)";
        }

        return $app['twig']->render('editor.html', $editor);
      }

      return $app->redirect('/editor/' . $post['slug']);
    }
  }
  
})->before($validateSession);

$app->get('/editor/delete/{id}', function ($id) use ($app) {
  $post = getPostById($app, $id);

  return $app['twig']->render('delete.html', array(
    'id' => $post['id'],
    'title' => $post['title']
  ));
})->before($validateSession);