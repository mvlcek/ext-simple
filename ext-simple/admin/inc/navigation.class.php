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
    return in_array(array('slug', 'menuState', 'parent', 'previous', 
        'title', 'menuText', 'tags', 'publishFrom', 'publishTo'), $name);
  }
   
}

addListener('save-page', 'Navigation::onPageSave');
addListener('delete-page', 'Navigation::onPageDelete');
