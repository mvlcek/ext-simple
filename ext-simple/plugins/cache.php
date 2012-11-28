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
  'Creates a cache of all page fields except content and provides functions to access it.',
  null,
  null 
);

# backend hooks
addListener('save-page', 'Cache::create');
addListener('delete-page', 'Cache::create');

# frontend hooks
addListener('before-template', 'cache_before_template');

class Cache extends XmlFile {
  
  private static $cache = null;
  
  private function __construct() {
    parent::__construct(ES_SETTINGSPATH.'cache.xml');
  }

  public static function getField($slug, $name, $language=null) {
    if (!self::$cache) {
      self::$cache = new Cache();
      if (!self::$cache) self::$cache = self::create();
    }
    return (string) self::$cache->root->xpath("/page[@name=$slug]/$name");
  }
  
  public static function create() {
    
  }
  
}


function cache_before_template() {
  if (!function_exists('get_page_field')) {
    function get_page_field($slug, $name) {
      return Cache::getField($slug, $name, getLanguage());
    }
  }
}