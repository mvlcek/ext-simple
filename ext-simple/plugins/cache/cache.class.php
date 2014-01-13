<?php

class Cache extends XmlFile {

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
  
  public function getSlugs() {
    $slugs = array();
    foreach ($this->items as $slug => $item) {
      $slugs[] = $slug;
    }
    return $slugs;
  }
  
  public function getItemString($slug, $name, $variant=null) {
    $item = $this->items[$slug];
    if (!$item) return null;
    $default = null;
    foreach ($item->$name as $field) {
      $fieldVariant = @$field['variant'];
      if ($fieldVariant == $variant) return (string) $field;
      if (!$fieldVariant) $default = (string) $field;
    }
    return $default;
  }

  public function getItemTime($slug, $name, $variant=null) {
    $value = $this->getItemString($slug, $name, $variant);
    return is_numeric($value) ? (int) $value : strtotime($value);
  }
  
  public function filterPageSlugs($slugs, $variant, $name, $op, $value=null) {
    $params = func_get_args();
    $slugs = array_shift($params);
    $variant = array_shift($params);
    while (count($params) >= 3) {
      $name = array_shift($params);
      $op = array_shift($params);
      $value = array_shift($params);
      $filtered = array();
      foreach ($slugs as $slug) {
        $result = false;
        $field = $name == 'slug' || $name == 'id' ? $slug : $this->getItemString($slug, $name, $variant);
        switch ($op) {
          case 'at':
            list($field, $value) = self::getDateParams($field, $value);
          case '==':
          case 'eq': 
            $result = $field == $value; break;
          case '!=':
          case '<>':
          case 'ne': 
            $result = $field != $value; break;
          case 'before':
            list($field, $value) = self::getDateParams($field, $value);
          case '<':
          case 'lt':
            $result = $field < $value; break;
          case '!after':
            list($field, $value) = self::getDateParams($field, $value);
          case '<=':
          case 'le':
            $result = $field <= $value; break;
          case 'after':
            list($field, $value) = self::getDateParams($field, $value);
          case '>':
          case 'gt':
            $result = $field > $value; break;
          case '!before':
            list($field, $value) = self::getDateParams($field, $value);
          case '>=':
          case 'ge':
            $result = $field >= $value; break;
          case 'empty': 
            $result = $field === null || $field === ''; break;
          case '!empty':
          case 'not empty':
            $result = $field !== null && $field !== ''; break;
          default:
            $result = execWhile('filter-slugs', array($field, $op, $value), null);
        }
        if ($result) $filtered[] = $slug;
      }
      $slugs = $filtered;
    }
    return $slugs;
  }
  
  private static function getDateParams($field, $value) {
    
  }

}
?>