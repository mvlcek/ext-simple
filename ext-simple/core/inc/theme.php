<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_COREPATH.'inc/page.class.php');
require_once(ES_COREPATH.'inc/plugins.class.php');
require_once(ES_COREPATH.'inc/navigation.class.php');
require_once(ES_COREPATH.'inc/component.class.php');

function put_header($type=null, $options=null) {
  Plugins::execAction('before-header');
  $done = Plugins::execWhile('put-header', array($type, $options), false);
  if (!$done) {
    $page = Common::getPage();
    if ($page) {
      $description = $page->getField('description', Common::getVariant(), null);
      if (!$description) {
        $description = strip_tags($page->getField('content', Common::getVariant(), null));
        if (function_exists('mb_substr')) {
          $description = trim(mb_substr($description, 0, 160));
        } else {
          $description = trim(substr($description, 0, 160));
        }
      }
      if ($description) {
        $description = preg_replace('/\r\n|\r|\n|\t/', " ", $description);
        echo '<meta name="description" content="'.htmlspecialchars($description).'" />'."\n";
      }
      $keywords = array();
      $tags = preg_split('/\s*,\s*/', $page->getField('tags'));
      foreach ($tags as $tag) {
        if (!substr(trim($tag),0,1) == '_') $keywords[] = $tag;
      }
      if ($keywords) {
        echo '<meta name="keywords" content="'.htmlspecialchars(join(', ', $keywords)).'" />'."\n";
      }
      // TODO: canonical URL
      //echo '<link rel="canonical" href="'. get_page_url(true) .'" />'."\n";
    }    
    echo '<meta name="generator" content="ExtSimple" />'."\n";
    put_css();
    put_js();
  }
  Plugins::execAction('after-header');
}

function put_navigation($slug=null, $minlevel=0, $maxlevel=0, $type=null, $options=null) {
  if (!$slug) $slug = get_slug();
  Plugins::execAction('before-navigation');
  $args = array($slug, $minlevel, $maxlevel, $type, $options);
  $done = execWhile('put-navigation', $args, false);
  if (!$done) {
    // TODO
  }
  Plugins::execAction('after-navigation');
}

function put_title() {
  return put_field('title', false);
}

function put_content() {
  Plugins::execAction('before-content');
  $content = get_field('content');
  $content = execFilter('filter-content', array($content));
  echo $content;
  Plugins::execAction('after-content');
  return $content !== null && $content !== '';
}

function put_footer($type=null, $options=null) {
  Plugins::execAction('before-footer');
  $done = execWhile('put-footer', array($type, $options), false);
  if (!$done) {
    // empty for now
  }
  Plugins::execAction('after-footer');
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
  $component = 1;
  // TODO
}


function get_slug() {
  $page = Common::getPage();
  return $page ? $page->getSlug() : null;
}

function get_field($name) {
  $page = Common::getPage();
  return $page ? $page->get($name, Common::getVariants()) : null;
}

function get_string_field($name) {
  $page = Common::getPage();
  return $page ? $page->getString($name, Common::getVariants()) : null;
}

function get_time_field($name) {
  $page = Common::getPage();
  return $page ? $page->getTime($name, Common::getVariants()) : null;
}

function get_filtered_field($name) {
  $page = Common::getPage();
  return $page ? $page->getFilteredString($name, Common::getVariants()) : null;
}


if (!function_exists('get_page_field')) {
  function get_page_field($slug, $name) {
    $page = new Page($slug, true);
    return $page ? $page->getField($name, Common::getVariants()) : null;
  }
}

function get_page_time_field($slug, $name) {
  $value = get_page_field($slug, $name);
  return !is_numeric($value) ? strtotime($value): (int) $value;  
}

function get_component($name) {
  
}

