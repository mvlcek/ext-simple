<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/page.class.php');
require_once(ES_ADMINPATH.'inc/plugins.php');

function put_header($type=null, $options=null) {
  execAction('before-header');
  $done = execWhile('put-header', array($type, $options), false);
  if (!$done) {
    // TODO
  }
  execAction('after-header');
}

function put_navigation($slug=null, $minlevel=0, $maxlevel=99, $type=null, $options=null) {
  if (!$slug) $slug = get_slug();
  execAction('before-navigation');
  $args = array($slug, $minlevel, $maxlevel, $type, $options);
  $done = execWhile('put-navigation', $args, false);
  if (!$done) {
    // TODO
  }
  execAction('after-navigation');
}

function put_title() {
  return put_field('title', false);
}

function put_content() {
  execAction('before-content');
  $content = get_field('content');
  $content = execFilter('filter-content', array($content));
  echo $content;
  execAction('after-content');
  return $content !== null && $content !== '';
}

function put_footer($type=null, $options=null) {
  execAction('before-footer');
  $done = execWhile('put-footer', array($type, $options), false);
  if (!$done) {
    // empty for now
  }
  execAction('after-footer');
}

function put_slug() {
  echo htmlspecialchars(get_slug());
}

function put_field($name, $html=false, $default=null) {
  $value = get_field($name);
  if ($value !== null && $value !== '') {
    echo $html ? $value : htmlspecialchars($value);
    return true;
  } else {
    if ($default !== null) echo $default;
    return false;
  }
}

function put_date_field($name, $format=null, $default=null) {
  return put_date(get_field($name), $format, $default);
}

function put_page_field($slug, $name, $html=false) {
  $value = get_page_field($slug, $name);
  if ($value !== null) {
    echo $html ? $value : htmlspecialchars($value);
  }
  return $value !== null && $value !== '';
}

function put_page_date_field($slug, $name, $format, $default=null) {
  put_date(get_page_field($slug, $name), $format, $default);
}

function put_component($name) {
  
}


function get_slug() {
  $page = getPage();
  return $page ? $page->getSlug() : null;
}

function get_field($name) {
  $page = getPage();
  return $page ? $page->getField($name, getLanguage()) : null;
}

function get_field_as_timestamp($name) {
  $value = get_field($name);
  return !is_numeric($value) ? strtotime($value): (int) $value;  
}

if (!function_exists('get_page_field')) {
  function get_page_field($slug, $name) {
    $page = new Page($slug, true);
    return $page ? $page->getField($name, getLanguage()) : null;
  }
}

function get_page_field_as_timestamp($slug, $name) {
  $value = get_page_field($slug, $name);
  return !is_numeric($value) ? strtotime($value): (int) $value;  
}

function get_component($name) {
  
}