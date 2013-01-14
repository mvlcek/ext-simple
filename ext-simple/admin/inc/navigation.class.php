<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');

class Navigation extends XmlFile {

  private static $cache = array();
  
  private $items = array();

  public function __construct($filename) {
    if (array_key_exists($filename, self::$cache)) {
      $this->root = self::$cache[$filename];
    } else {
      parent::__construct($filename, '<items></items>');
      self::$cache[$filename] = $this->root;
    }
    foreach ($this->root->item as $item) {
      $this->items[$item['slug']] = $item;
    }
  }
  
  public function create($path, $objTypeAttr) {
    $objClass = self::getObjectClassFromPath($path);
    # remove all items
    $items = $this->items;
    for ($i=count($items)-1; $i>=0; $i--) unset($items[$i]);
    $this->items = array();
    # get slugs in path ...
    $slugs = self::listSlugs($path);
    # ... and index them
    foreach ($slugs as $slug) {
      $file = new XmlFile($path.$slug.'.xml');
      if (!$file->isNew()) {
        $this->addItem($objClass, $slug, $file, $objTypeAttr);
      }
    } 
  }
  
  public function remove($path, $slug) {
    if (isset($items[$slug])) {
      $items = $this->items;
      for ($i=count($items)-1; $i>=0; $i--) {
        if ($items[$i]['slug'] == $slug) unset($items[$i]);
      }
      unset($this->items[$slug]);
    }
  }
  
  public function update($path, $slug, $objTypeAttr) {
    # remove item
    $this->remove($path, $slug);
    # add item, if it exists
    $file = new XmlFile($path.$slug.'.xml');
    if (!$file->isNew()) {
      $objClass = self::getObjectClassFromPath($path);
      $this->addItem($objClass, $slug, $file, $objTypeAttr);
    }
  }
  
  private function addItem($objClass, $slug, $file, $objTypeAttr) {
    $itemNode = $this->root->addChild('item');
    $itemNode->addAttribute('slug', $slug);
    $objType = $objTypeAttr ? (string) $file->root->$objTypeAttr : null;
    foreach ($file->root->children() as $node) {
      $fieldType = self::getFieldType($objClass, $objType, $node->getName());
      if ($fieldType != self::FIELDTYPE_HTML) {
        $fieldNode = $itemNode->addChild($node->getName(), (string) $node);
        if ($node['variant']) $fieldNode->addAttribute('variant', $node['variant']);
      }
    }
    $this->items[$slug] = $itemNode;
  }
  
}
