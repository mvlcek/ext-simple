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
 
class GenericNavigation extends XmlFile {
  
  private $name = null;
  private $path = null;
  private $cache = null;
  
  public function __construct($name='navigation', $path=ES_PAGESPATH) {
    parent::__construct(ES_SETTINGSPATH.$name.'.xml');
    $this->cache = new NavigationCache(ES_CACHEPATH.$name.'.xml', $path, true, true);
    $this->name = $name;
    $this->path = $path;
  }
  
  public function getPageNode($slug) {
    $pageNode = $this->root->xpath("//page[@slug='$slug']");
    if (!$pageNode || count($pageNode) < 1) return null;
    return current($pageNode);
  }
  
  public function setPageNode($slug, $parent=null, $after=null, $params=null) {
    $pageNode = getPageNode($slug);
    if (!$pageNode) {
      
    }
  }
  
}
 
 
class Navigation extends XmlFile {

  private static $navigation = null;
  private static $cache = null;  
  
  private function __construct() {
    parent::__construct(ES_SETTINGSPATH.'navigation.xml');
    if (!isset($this->root->salt)) $this->root->salt = sha1(rand().time()); 
  }
  
  public static function getNavigation() {
    
  }
  
  public static function onPageSave($page) {
    // TODO: update navigation
    $this->onItemSave(new NavigationCache(true, false), $page);
  }
  
  public static function onPageDelete($slug) {
    // TODO: update navigation
    $this->onItemDelete(new NavigationCache(true, false), $slug);
  }

}

class NavigationCache extends AbstractSlugFileCache {
  
  private static $instance = null;
  
  public static function getCache() {
    if (!self::$instance) self::$instance = new NavigationCache();
    return self::$instance;
  }

  public function __construct($doCreate=true, $doSave=true) {
    parent::__construct(ES_CACHEPATH.'navigation.xml', ES_PAGESPATH, $doCreate, $doSave);
  }
  
  protected function isCacheable($item, $name) {
    return in_array(array('slug', 'menuState', 'title', 'tags', 'publishFrom', 'publishTo'), $name);
  }
   
}

addListener('save-page', 'Navigation::onPageSave');
addListener('delete-page', 'Navigation::onPageDelete');
