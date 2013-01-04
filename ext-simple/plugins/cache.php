<?php

# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
  $thisfile, 
  'Cache',  
  '1.0',    
  'Martin Vlcek',
  'http://mvlcek.bplaced.net', 
  'Creates a cache of all page fields except content and provides functions to access it.'
);

# backend hooks
addListener('save-page', 'Cache::updatePage');
addListener('delete-page', 'Cache::deletePage');

# frontend hooks
addListener('before-template', 'cache_before_template');

class CacheFunctions {
  
  public static function updatePage($slug) {
    require_once(ES_PLUGINSPATH.'cache/cache.class.php');
    $cache = new Cache(ES_SETTINGSPATH.'cache.xml');
    if ($cache->isNew()) {
      $cache->create(ES_PAGESPATH);
    } else {
      $cache->update(ES_PAGESPATH, $slug, 'type');
    }
    $cache->save(ES_PLUGINSPATH.'cache/cache.class.php');
  }
  
  public static function deletePage($slug) {
    self::updatePage($slug);
  }
  
}

function cache_before_template() {
  if (!function_exists('get_page_field')) {
    function get_page_field($slug, $name) {
      return Cache::getField($slug, $name, getLanguage());
    }
  }
}