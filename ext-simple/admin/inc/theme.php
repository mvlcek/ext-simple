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
  put_field('title', $html);
}

function put_content() {
  execAction('before-content');
  $content = get_field('content');
  $content = execFilter('filter-content', array($content));
  echo $content;
  execAction('after-content');
}

function put_footer($type=null, $options=null) {
  execAction('before-footer');
  $done = execWhile('put-footer', array($type, $options), false);
  if (!$done) {
    // empty for now
  }
  execAction('after-footer');
}

function put_field($name, $html=false) {
  $value = get_field($name);
  if ($value !== null) {
    echo $html ? $value : htmlspecialchars($value);
  }
}

function put_date_field($name, $format) {
  
}

function put_page_field($slug, $name, $html=false) {
  
}

function put_page_date_field($slug, $name, $format) {
  
}

function get_slug() {
  
}

function get_field($name) {
  
}

function get_date_field_as_timestamp($name) {
  
}

function get_page_field($slug, $name) {
  
}

function get_page_date_field_as_timestamp($slug, $name) {
  
}