<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

if(!defined('IN_ES')) die('You cannot load this page directly.'); 

require_once(ES_COREPATH.'inc/log.class.php');
require_once(ES_COREPATH.'inc/transliteration.class.php');

class FileUtils {

  static public function isFileInPath($filename, $path) {
    $dirname = dirname($filename);
    return substr($dirname,0,strlen($path)) == $path;
  }
  
  static public function checkFileName($filename) {
    if (strpos($filename,'..') !== false || strpos($filename, ES_ROOTPATH) !== 0) {
      Log::error('Invalid filename %s!', $filename);
      die("Invalid path $filename!");
    }
  }
  
  static public function setFileAttributes($filename) {
    self::checkFileName($filename);
    if (defined('ES_FILE_MOD')) @chmod($filename, ES_FILE_MOD);
    if (defined('ES_FILE_OWNER')) @chown($filename, ES_FILE_OWNER);
  }
  
  static public function deleteFile($filename) {
    self::checkFileName($filename);
    return unlink($filename);
  }
  
  static public function setDirAttributes($dirname) {
    self::checkFileName($dirname);
    if (defined('ES_DIR_MOD')) @chmod($dirname, ES_DIR_MOD); elseif (defined('ES_FILE_MOD')) @chmod($dirname, ES_FILE_MOD);
    if (defined('ES_DIR_OWNER')) @chown($dirname, ES_DIR_OWNER); elseif (defined('ES_FILE_OWNER')) @chown($dirname, ES_FILE_OWNER);
  }
  
  static public function createDir($dirname) {
    self::checkFileName($dirname);
    if (file_exists($dirname)) {
      return is_dir($dirname);
    } else if (self::createDir(dirname($dirname))) {
      if (mkdir($dirname)) {
        self::setFileAttributes($dirname);
        return true;
      }
    }
    return false;
  }
  
  static public function deleteDir($dirname, $recursive=false) {
    self::checkFileName($dirname);
    if (file_exists($dirname) && is_dir($dirname)) {
      if ($recursive) {
        $dir = opendir($dirname);
        while (($name = readdir($dir)) !== false) {
          if (!is_dir($dirname.'/'.$name)) {
            unlink($dirname.'/'.$name);
          } else if ($name != '.' && $name != '..') {
            self::deleteDir($dirname.'/'.$name);
          }
        }
        closedir($dir);
      }
      return rmdir($dirname);    
    } elseif (file_exists($dirname)) {
      return false;
    } else {
      return true;
    }
  }
  
}


class XmlFile {

  private $new = false;
  private $filename = null;
  protected $root = null;
	
  public function __construct($filename, $rootElement='<root></root>') {
    $this->filename = $filename;
    if ($filename && file_exists($filename)) {
      $this->root = simplexml_load_file($filename, 'ExtendedXMLElement', LIBXML_NOCDATA);
    } else {
      if (!$rootElement) $rootElement = '<root></root>';
      if (substr($rootElement,0,5) != '<?xml') {
        $rootElement = '<?xml version="1.0" encoding="UTF-8"?>'.$rootElement;
      }
      $this->root = @new ExtendedXMLElement($rootElement);
      $this->new = true;
		}
	}
  
  public function isNew() {
    return $this->new;
  }
  
  public function get($name, $attrs=null) {
    return call_user_func_array(array($this->root,'get'), func_get_args());
  }
  
  public function getAll($name, $attrs=null) {
    return $this->root->getAll($name, $attrs);
  }
  
  public function add($name, $attrs=null, $value=null) {
    return $this->root->add($name, $attrs, $value);
  }
  
  public function set($name, $attrs=null, $value=null) {
    return $this->root->set($name, $attrs, $value);
  }
  
  public function remove($name, $attrs=null) {
    return $this->root->remove($name, $attrs);
  }
  
  public function save() {
    if ($this->saveAs($this->filename)) {
      $this->new = false;
      return true;
    } else {
      return false;
    }
  }  
  
  public function saveAs($filename) {
    return $this->root->asXML($filename) === TRUE;
  }
  
}


class XmlSlugFile extends XmlFile {

  public function __construct($slug, $type='pages', $rootElement='<root></root>') {
    parent::__construct(ES_DATAPATH.$type.'/'.self::getFileSlug($slug).'.xml', $rootElement);
    if ($this->isNew()) $this->root->addChild('slug', $slug);
  }
  
  public function getSlug() {
    return (string) $this->root->slug;
  }
  
  public function setSlug($slug) {
    $this->root->slug = $slug;
  }

  public function getString($name, $variants=null) {
    $params = array($name);
    if (count($variants) > 0) foreach ($variants as $variant) $params[] = array('variant'=>$variant);
    $node = call_user_func_array(array($this->root,'get'), $params);
    return $node ? $node->asString() : null;
  }
  
  public function getTime($name, $variants=null) {
    $params = array($name);
    if (count($variants) > 0) foreach ($variants as $variant) $params[] = array('variant'=>$variant);
    $node = call_user_func_array(array($this->root,'get'), $params);
    return $node ? $node->asTime() : null;
  }
  
  public function getInteger($name, $variants=null) {
    $params = array($name);
    if (count($variants) > 0) foreach ($variants as $variant) $params[] = array('variant'=>$variant);
    $node = call_user_func_array(array($this->root,'get'), $params);
    return $node ? $node->asInteger() : null;
  }
  
  public function getFloat($name, $variants=null) {
    $params = array($name);
    if (count($variants) > 0) foreach ($variants as $variant) $params[] = array('variant'=>$variant);
    $node = call_user_func_array(array($this->root,'get'), $params);
    return $node ? $node->asFloat() : null;
  }
  
  public function setValue($name, $value, $variant=null) {
    if ($name == 'slug') throw new Exception('Use setSlug() to set the slug!');
    return $this->root->set($name, $variant ? array('variant'=>$variant) : null, $value);
  }
  
  # ===== static methods =====
  
  public static function existsSlugFile($slug, $type='pages') {
    return file_exists(ES_DATAPATH.$type.'/'.self::getFileSlug($slug).'.xml');    
  }
  
  public static function deleteSlugFile($slug, $type='pages') {
    return FileUtils::deleteFile(ES_DATAPATH.$type.'/'.self::getFileSlug($slug).'.xml', true);
  }
  
  public static function getFileSlug($slug) {
    return Transliteration::get($slug);
  }
  
  public static function listFileSlugs($type='pages') {
    $slugs = array();
    $dir = opendir(ES_DATAPATH.$type);
    if ($dir) while (($filename = readdir($dir)) !== false) {
      if (substr($filename,-4) == '.xml') $slugs[] = substr($filename,0,-4);
    }
    closedir($dir);
    return $slugs;
  }
  
}


class ExtendedXMLElement extends SimpleXMLElement{   

  /**
   * @return string the value of the attribute
   */
  public function attribute($name) {
    return @$this[$name];
  }

  /**
   * @return string the value of the element as string
   */
  public function asString() {
    return (string) $this;
  }
  
  /**
   * @return int the value of the element as timestamp (integer)
   */
  public function asTime() {
    $value = (string) $this;
    return is_numeric($value) ? (int) $value : strtotime($value);
  }
  
  /**
   * @return int the value of the element as integer
   */
  public function asInteger($default=0) {
    try {
      return (int) ((string) $this);
    } catch (Exception $e) {
      return $default;
    }
  }
  
  /**
   * @return float the value of the element as float
   */
  public function asFloat($default=0.0) {
    try {
      return (float) ((string) $this);
    } catch (Exception $e) {
      return $default;
    }
  }

  /**
   * Get the first child element with the given name and the specified attributes
   * You can specify multiple attributes alternatives and will get the best matching
   * element.
   * 
   * Examples:
   *   $node->get('content') returns the first element with name 'content'
   *   $node->get('content', array('lang'=>'en')) returns the first element with name
   *     'content', which has the attribute 'lang' set to 'en'
   *   $node->get('content', array('lang'=>'de'), array('lang'=>'en')) returns the first
   *     element with name 'content' and attribute 'name' equal to 'de', but if this does
   *     not exists it returns the element with attribute 'name' equal to 'en', or else
   *     null
   * 
   * @param string $name   the name of the child element to return
   * @param array  $attrs  an associative array specifying attribute names as keys and
   *                        their desired values as values; there can be multiple arrays
   * @return ExtendedXMLElement the found element or null
   */
  public function get($name, $attrs=null) {
    $altAttrs = func_get_args();
    array_shift($altAttrs);
    $nodes = $this->$name;
    if (!$nodes || count($nodes) < 1) return null;
    if (count($altAttrs) > 1) {
      $matches = array();
      foreach ($nodes as $node) {
        for ($i=0; $i<count($altAttrs); $i++) {
          if (!isset($matches[$i]) && $node->has($altAttrs[$i])) $matches[$i] = $node;
        }
      }
      return reset($matches);
    } else if (is_array($attrs)) {
      foreach ($nodes as $node) {
        if ($node->has($attrs)) return $node;
      }
    } else {
      return reset($nodes);
    }
    return null;
  }
  
  /**
   * get all child elements with the given name and the specified attributes
   *
   * @param string $name   the name of the child element to return
   * @param array  $attrs  an associative array specifying attribute names as keys and
   *                       their desired values as values; there can be multiple arrays
   * @return array         an array of matching elements   
   */
  public function getAll($name, $attrs=null) {
    $result = array();
    foreach ($this->$name as $node) {
      if (!$attrs || $node->has($attrs)) $result[] = $node;
    }
    return $result;
  }
  
  /**
   * Removes all nodes with the given name and attributes and then adds
   * a new node with the given name and attributes and value. If value 
   * is an array, multiple nodes are added.
   * 
   * @param string $name   the name of the node(s)
   * @param array  $attrs  the attributes of the node(s)
   * @param mixed  $value  the single value or multiple values in an array
   * @return mixed the node or array of nodes added
   */
  public function set($name, $attrs=null, $value=null) {
    $this->remove($name, $attrs);
    return $this->add($name, $attrs, $value);
  }
    
  /**
   * Adds a new node with the given name and attributes and value. If value 
   * is an array, multiple nodes are added.
   * 
   * @param string $name   the name of the node(s)
   * @param array  $attrs  the attributes of the node(s)
   * @param mixed  $value  the single value or multiple values in an array
   * @return mixed the node or array of nodes added
   */
  public function add($name, $attrs=null, $value=null) {
    $values = is_array($value) ? $value : array($value);
    $nodes = array();
    foreach ($values as $val) {
      if ($val === null) {
        $node = $this->addChild($name);
      } else {
        $node = $this->addChild($name, htmlspecialchars($val));
      }
      foreach ($attrs as $key => $attrval) {
        $node[$key] = $attrval;
      }
      $nodes[] = $node;
    }
    return is_array($value) ? $nodes : reset($nodes);
  }
  
  /**
   * Removes all nodes with the given name and attributes.
   * 
   * @param string $name   the name of the node(s)
   * @param array  $attrs  the attributes of the node(s)
   */
  public function remove($name, $attrs=null) {
    foreach ($this->$name as $node) {
      if (!$attrs || $node->has($attrs)) unset($node);
    }
  }
  
  /**
   * Returns true, if the node has all the attributes specified.
   * 
   * @param array $attrs  the keys and values of the attributes to check. 
   *                      an empty array would return true
   */
  public function has($attrs) {
    foreach ($attrs as $key => $value) {
      if (@$this[$key] != $value) return false;
    }
    return true;
  }
  
  /**
   * @return ExtendedXMLElement the parent node
   */
  public function parent() {
    return current($this->xpath('parent::*'));
  }

} 
