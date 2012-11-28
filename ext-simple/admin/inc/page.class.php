<?php

class Page extends XmlFile {

  private static $cache = array();

  private $slug;

  public function __construct($slug, $cache=false) {
    if (array_key_exists($slug, self::$cache)) {
      $this->root = self::$cache[$slug];
    } else {
      parent::__construct(ES_PAGESPATH.$slug.'.xml');
      if ($cache) self::$cache[$slug] = $this->root;
    }
    $this->slug = $slug;
  }
  
  public function getSlug() {
    return $this->slug;
  }
  
  public function getField($name, $language=null) {
    if (isset($this->root->$name)) {
      return (string) $this->root->$name; 
    } else {
      $value = $language ? (string) $this->root->xpath("/language[@name=$language]/$name") : null;
      if (!$value) {
        $defLanguage = getDefaultLanguage();
        $value = (string) $this->root->xpath("/language[@name=$defLanguage]/$name");
      }
      return $value;
    }
  }
  
  public function savePage() {
    return $this->save(ES_PAGESPATH.$this->slug.'.xml', true);
  }
  
  public static function deletePage($slug) {
    return self::delete(ES_PAGESPATH.$slug.'.xml', true);
  }

  public static function getSlugs() {
    
  }
  
}
