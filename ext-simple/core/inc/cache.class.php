<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');


abstract class AbstractSlugFileCache extends XmlSlugFile {
  
  protected $cacheFile;
  protected $slugFileDir;
  
  public function __construct($cacheFile, $slugFileDir, $doCreate=true, $doSave=true) {
    $this->cacheFile = $cacheFile;
    $this->slugFileDir = $slugFileDir.(substr($slugFileDir,-1) != '/' ? '/' : '');
    parent::__construct($this->cacheFile, '<items></items>');
    if ($this->isNew()) {
      if ($doCreate) $this->create();
      if ($doSave) $this->save();
    }
  }
  
  public function create() {
    # remove all items
    $items = $this->root->items;
    for ($i=count($items)-1; $i>=0; $i--) unset($items[$i]);
    # add all slug files in directory ...
    $dir = opendir($this->slugFileDir);
    if ($dir) while (($filename = readdir($dir)) !== false) {
      if (substr($filename,-4) == '.xml') {
        $this->addSlugFile(substr($filename,-4));
      }
    }
    closedir($dir);
  }
  
  public function recreate() {
    $this->create();
  }
  
  public function removeItem($slug) {
    $items = $this->root->items;
    for ($i=count($items)-1; $i>=0; $i--) {
      if ($items[$i]['slug'] == $slug) unset($items[$i]);
    }
  }
  
  public function updateItem($slug) {
    # remove item
    $this->removeItem($slug);
    # add item, if it exists
    $this->addSlugFile($slug);
  }
  
  public function save() {
    return $this->saveTo($this->cacheFile, false);
  }
  
  abstract protected function isCacheable($item, $name);
  
  protected function addSlugFile($slug, $item=null) {
    if (!$item) $item = new XmlSlugFile($this->slugFileDir, $slug);
    if (!$item->isNew()) {
      $itemNode = $this->root->addChild('item');
      $itemNode->addAttribute('slug', $item->getSlug());
      foreach ($item->root->children() as $prop) {
        if ($this->isCacheable($item, $prop->getName())) {
          $propNode = $itemNode->addChild($prop->getName(), (string) $prop);
          foreach ($prop->attributes() as $attrName => $attrValue) {
            $propNode[$attrName] = $attrValue;
          }
        }
      }
    }
  }
  
  public static function onItemSave($cache, $item) {
    if (!$cache->isNew()) {
      $cache->removeItem($item->getSlug());
      $cache->addSlugFile($item->getSlug(), $item);
    }
    $cache->save();
  }
  
  public static function onItemDelete($cache, $slug) {
    if (!$cache->isNew()) $cache->removeItem($slug);
    $cache->save();
  }
  
}
