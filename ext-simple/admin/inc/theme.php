<?php

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

function put_title($html=false) {
  return put_field('title', $html);
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
  if ($format) {
    $fmt = getString('DATE_FORMAT_'.$format);
    if (!$fmt) $fmt = $format;
  } else {
    $fmt = getString('DATE_FORMAT');
    if (!$fmt) $fmt = '%Y-%m-%d %H:%M:%S';
  }
  $date = get_field_as_timestamp($name);
  if ($date) {
    echo strftime($format, $date);
    return true;
  } else {
    if ($default !== null) echo $default;
    return false;
  }
}

function put_page_field($slug, $name, $html=false) {
  $value = get_page_field($slug, $name);
  if ($value !== null) {
    echo $html ? $value : htmlspecialchars($value);
  }
  return $value !== null && $value !== '';
}

function put_page_date_field($slug, $name, $format) {
  if ($format) {
    $fmt = getString('DATE_FORMAT_'.$format);
    if (!$fmt) $fmt = $format;
  } else {
    $fmt = getString('DATE_FORMAT');
    if (!$fmt) $fmt = '%Y-%m-%d %H:%M:%S';
  }
  $date = get_page_field_as_timestamp($slug, $name);
  if ($date) echo strftime($format, $date);
  return (bool) $date;
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

function get_component($name) {
  
}