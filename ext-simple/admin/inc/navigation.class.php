<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');
require_once(ES_ADMINPATH.'inc/page.class.php');
 
class Navigation extends XmlFile {

  private static $cache = null;  

  public static function getCache() {
    if (!self::$cache) self::$cache = new Navigation();
    return self::$cache;
  }

  public static function existsCache() {
    return file_exists(ES_CACHEPATH.'navigation.xml');
  }

  public function __construct() {
    parent::__construct(ES_CACHEPATH.'navigation.xml', '<pages></pages>');
    if ($this->isNew()) {
      $this->create();
      $this->save();
    }
  }
  
  public function create() {
    # remove all pages
    $items = $this->items;
    for ($i=count($items)-1; $i>=0; $i--) unset($items[$i]);
    # parse all pages ...
    $dir = opendir(ES_PAGESPATH);
    if ($dir) while (($filename = readdir($dir)) !== false) {
      if (substr($filename,-4) == '.xml') {
        $slug = substr($filename,0,-4);
        $this->addPage($slug);
      }
    }
    closedir($dir);
  }
  
  public function removePage($slug) {
    $pages = $this->root->pages;
    for ($i=count($pages)-1; $i>=0; $i--) {
      if ($pages[$i]['slug'] == $slug) unset($pages[$i]);
    }
  }
  
  public function updatePage($slug) {
    # remove item
    $this->remove($slug);
    # add item, if it exists
    $this->addPage($slug);
  }
  
  public function save() {
    return $this->saveTo(ES_CACHEPATH.'navigation.xml', false);
  }
  
  private function addPage($slug) {
    $page = new Page($slug);
    if (!$page->isNew()) {
      $pageNode = $this->root->addChild('page');
      $pageNode->addAttribute('slug', $page->getSlug());
      $propNames = array('slug', 'menuState', 'parent', 'previous', 
        'title', 'menuText', 'tags', 'publishFrom', 'publishTo');
      foreach ($page->root->children() as $prop) {
        if (in_array($prop->getName(), $propNames)) {
          $pageProp = $pageNode->addChild($prop->getName(), (string) $prop);
          foreach ($prop->attributes() as $attrName => $attrValue) {
            $pageProp[$attrName] = $attrValue;
          }
        }
      }
    }
  }
  
  public static function onPageSave($page) {
    if (self::existsCache()) {
      $cache = self::getCache();
      $cache->updatePage($page->getSlug());
      $cache->save();
    } else {
      self::getCache();
    }
  }
  
  public static function onPageDelete($slug) {
    if (self::existsCache()) {
      $cache = self::getCache();
      $cache->removePage($slug);
      $cache->save();
    } else {
      self::getCache();
    }
  }
   
}

addListener('save-page', 'Navigation::onPageSave');
addListener('delete-page', 'Navigation::onPageDelete');
