<?php

class Page extends XmlFile {

  private $slug;

  public function __construct($slug) {
    parent::__construct(ES_PAGESPATH.$slug.'.xml');
    $this->slug = $slug;
  }
  
  public function getSlug() {
    return $this->slug;
  }
  
  public function getField($name, $language=null) {
    if (isset($this->root->$name)) {
      return (string) $this->root->$name; 
    } else {
      // TODO
    }
  }
  
  public function savePage() {
    return $this->save(ES_PAGESPATH.$this->slug.'.xml', true);
  }
  
}
