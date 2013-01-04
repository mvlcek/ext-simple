<?php

class Cache extends XmlFile {

  private static $cache = array();

  public function __construct($filename) {
    if (array_key_exists($filename, self::$cache)) {
      $this->root = self::$cache[$filename];
    } else {
      parent::__construct($filename, '<items></items>');
      self::$cache[$filename] = $this->root;
    }
  }
  
  public function create($path, $typeAttr) {
    
  }
  
  public function update($path, $slug, $typeAttr) {
    
  }
  
  public function filterPageSlugs($slugs, $name, $op, $value) {
    $params = func_get_args();
    $slugs = array_shift($params);
    while (count($params) >= 3) {
      $name = array_shift($params);
      $op = array_shift($params);
      $value = array_shift($params);
      if ($op == 'sort') {
        
      } else {
        $slugs2 = array();
        foreach ($slugs as $slug) {
          $result = false;
          $field = $name == 'slug' || $name == 'id' ? $slug : get_page_field($slug, $name);
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
          $slugs2[] = $slug;
        }
      }
    }
    return $slugs;
  }
  

}
?>