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
  
  private static $cache = null;
  
  public static function getCache() {
    if (!self::$cache) self::$cache = new Cache(ES_SETTINGSPATH.'cache.xml');
  }
  
  public static function updatePage($slug) {
    require_once(ES_PLUGINSPATH.'cache/cache.class.php');
    $cache = self::getCache();
    if ($cache->isNew()) {
      $cache->create(ES_PAGESPATH);
    } else {
      $cache->update(ES_PAGESPATH, $slug, 'type');
    }
    $cache->save(ES_SETTINGSPATH.'cache.xml');
  }
  
  public static function deletePage($slug) {
    require_once(ES_PLUGINSPATH.'cache/cache.class.php');
    $cache = self::getCache();
    $cache->remove(ES_PAGESPATH, $slug);
    $cache->save(ES_SETTINGSPATH.'cache.xml');
  }
  
}


if (!function_exists('get_slugs')) {
  function get_slugs() {
    require_once(ES_PLUGINSPATH.'cache/cache.class.php');
    $cache = CacheFunctions::getCache();
    return $cache->getSlugs();    
  }
}

if (!function_exists('get_filtered_slugs'))
  
if (!function_exists('get_page_field')) {
  function get_page_field($slug, $name) {
    require_once(ES_PLUGINSPATH.'cache/cache.class.php');
    $cache = CacheFunctions::getCache();
    return $cache->getItemString($slug, $name, getVariant());
  }
}
