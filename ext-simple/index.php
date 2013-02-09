<?php
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

header('Content-Type: text/plain; charset=utf8');
print_r($_SERVER);

print_r($_GET);

require_once('esconfig.php');
if (!defined('ES_ADMIN')) define('ES_ADMIN', 'admin');
require_once(dirname(__FILE__).'/'.ES_ADMIN.'/inc/common.php');
require_once(ES_ADMINPATH.'inc/page.class.php');
require_once(ES_ADMINPATH.'inc/link.class.php');

$urlformat = '/%$parent/%%$slug%';
$params = Link::parse($urlformat, $_SERVER['REQUEST_URI']);
print_r($params);
$slug = $params['slug'];

$page = null;
if (Page::existsPage($slug)) {
  $page = Page::getPage($slug);
  $veto = execUntil('veto-page', array($page), 1);
  if ($veto) $page = null;
}
if ($page == null) {
  if (Page::existsPage('404')) {
    $page = Page::getPage('404');
  } else {
    $page = new Page('404');
    $page->root->addChild('title', get_s('PAGE_NOT_FOUND_TITLE'));
    $page->root->addChild('content', get_s('PAGE_NOT_FOUND_CONTENT'));
  }
}
Init::setPage($page);
execAction('init-page');
execAction('before-page');
