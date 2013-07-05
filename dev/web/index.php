<?php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Configuration
$app['conf'] = array(
  'posts_per_page' => 10,
  'search_results_per_page' => 50,
  'username' => 'admin',
  'bcrypt' => '$2y$10$BQ1qC4S0YmOChM0SVwoYE.X6UrY00ngxR1W4vBFr69MEnoTCSYeGK',
  'session_timeout' => 14 //days
);

date_default_timezone_set('Asia/Kuala_Lumpur');

// Service Providers
use \Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver' => 'pdo_mysql',
    'dbname' => 'camelcase'
  ),
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\SessionServiceProvider());

// Database Methods
function getMainMenu ($app) {
  $sql = "select title, slug from posts where status = 'published' and main_menu = 1 order by seq";
  $main_menu = $app['db']->fetchAll($sql);

  return $main_menu;
}

function getPost($app, $slug) {
  $sql = "select * from posts where slug = ?";
  $post = $app['db']->fetchAssoc($sql, array($slug));

  if ($post) {
    $post['content'] = markToHtml($post['content']);
  }

  return $post;
}

function getPublishedPost($app, $slug, $type = '') {
  if ($type) {
    $type = "and type ='$type'";
  }

  $sql = "select * from posts where status = 'published' $type and slug = ?";
  $post = $app['db']->fetchAssoc($sql, array($slug));

  if ($post) {
    $post['content'] = markToHtml($post['content']);
  }

  return $post;
}

function getPostNav($app, $publish_date, $current_id) {
  $prev_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id < ?) or publish_date < timestamp(?)) and type = 'post' order by publish_date desc, id desc limit 1";
  $prev_post = $app['db']->fetchAssoc($prev_sql, array($publish_date, $current_id, $publish_date));

  if ($current_id == $prev_post['id']) {
    $prev_post['slug'] = 0;
  }

  $next_sql = "select id, title, slug from posts where status = 'published' and ((publish_date = timestamp(?) and id > ?) or publish_date > timestamp(?)) and type = 'post' order by publish_date, id limit 1";
  $next_post = $app['db']->fetchAssoc($next_sql, array($publish_date, $current_id, $publish_date));

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

function getPosts($app, $current = 0, $total = 0, $query = '') {
  $per_page = $query ? $app['conf']['search_results_per_page'] : $app['conf']['posts_per_page'];
  $sql = '';
  $offset = 0;

  if ($current < 0) {
    $current = 1;
  }

  if ($current > 0 && $current < $total) {
    $offset = ($total - $current) * $per_page;
  }

  if (!$query) {
    $sql = "select * from posts where status = 'published' and type = 'post' order by publish_date desc, id desc limit $offset, $per_page";
  } else {
    $keywords = explode(' ', $query);
    $keywords = array_map(function($keyword) {
      return '\'%' . $keyword . '%\'';
    }, $keywords);
    $title_likes = implode(' OR title like ', $keywords);
    $content_likes = implode(' OR content like ', $keywords);

    $sql = "select * from posts where status = 'published' and ((title like $title_likes) or (content like $content_likes)) order by publish_date desc, type desc, id desc limit $offset, $per_page";
  }

  $pages = $app['db']->fetchAll($sql);

  return $pages;
}

function getPostsNav($app, $current = 0, $query = '') {
  $per_page = $query ? $app['conf']['search_results_per_page'] : $app['conf']['posts_per_page'];
  $prev = 0;
  $next = 0;

  if (!$query) {
    $sql = "select count(*) from posts where status = 'published' and type = 'post'";
  } else {
    $sql = "select count(*) from posts where status = 'published' and (title like '%$query%' or content like '%$query%')";
  }
  $total = $app['db']->fetchColumn($sql);

  $raw_total = (int) $total;
  $total = (int) ceil($total / $per_page);

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
    'next' => $next,
    'raw_total' => $raw_total
  );

  return $posts_nav;
}

function insertIntoPosts($app, $data) {
  $status = $app['db']->insert('posts', array(
    'title' => $data['title'],
    'slug' => $data['slug'],
    'content' => $data['content'],
    'type' => $data['type'],
    'main_menu' => $data['main_menu'] ? 1 : 0,
    'seq' => $data['seq'] ? $data['seq'] : 0,
    'status' => $data['status'],
    'publish_date' => date('Y-m-d H:i:s', strtotime($data['publish_date']))
  ));

  return $status;
}

function updatePost($app, $data) {
  $status = $app['db']->update('posts', array(
    'title' => $data['title'],
    'content' => $data['content'],
    'type' => $data['type'],
    'main_menu' => $data['main_menu'] ? 1 : 0,
    'seq' => $data['seq'] ? $data['seq'] : 0,
    'status' => $data['status'],
    'publish_date' => date('Y-m-d H:i:s', strtotime($data['publish_date']))
  ), array(
    'slug' => $data['slug'],
  ));

  return $status;
}

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

$data = array(
  'main_menu' => getMainMenu($app)
);

// editor
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

// reserved page
$app->get('/{page_slug}', function($page_slug) use ($app) {
  global $data;

  $data['page'] = getPublishedPost($app, $page_slug, 'page');

  return $app['twig']->render('page.html', $data);
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


$app['debug'] = true;

// 404 page
$app->error(function (\Exception $e, $code) use ($app) {
  if ($code == 404) {
    return new Response($app['twig']->render('404.html', array(), 404));
  }

  // return new Response('We are sorry, but something went terribly wrong.', $code);
});


$app->run();