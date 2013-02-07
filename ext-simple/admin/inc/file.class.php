<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

if(!defined('IN_ES')) die('You cannot load this page directly.'); 

require_once(ES_ADMINPATH.'inc/log.class.php');
require_once(ES_ADMINPATH.'inc/plugins.php');
require_once(ES_ADMINPATH.'inc/settings.class.php');

# TODO: use TimeMachine class instead of manually making backups
class DataFile {

  static public function isFileInDataPath($filename) {
    $dirname = dirname($filename);
    return substr($dirname,0,strlen(ES_DATAPATH)) == ES_DATAPATH;
  }
  
  static public function isFileInBackupPath($filename) {
    $dirname = dirname($filename);
    return substr($dirname,0,strlen(ES_BACKUPSPATH)) == ES_BACKUPSPATH;
  }

  static public function checkFileName($filename) {
    if (strpos($filename,'..') !== false) {
      Log::error('Invalid filename %s!', $filename);
      die("Invalid filename $filename!");
    }
  }
  
  static public function getBackupFileName($filename) {
    if (!substr($filename,0,strlen(ES_DATAPATH)) == ES_DATAPATH) return false;
    return ES_BACKUPSPATH.substr($filename,strlen(ES_DATAPATH));
  }
  
  static public function getDataFileName($backupFilename) {
    if (!substr($backupFilename,0,strlen(ES_BACKUPSPATH)) == ES_BACKUPSPATH) return false;
    return ES_DATAPATH.substr($backupFilename,strlen(ES_BACKUPSPATH));
  }
  
  /* on success returns the backup filename */
  static public function backupFile($filename) {
    self::checkFileName($filename);
    if (!($backupFilename = self::getBackupFileName($filename))) return false;
    if (copy($filename, $backupFilename)) return $backupFilename;
    return false;
  }
  
  /* on success returns true or the backup filename */
  static public function deleteFile($filename, $backup=true) {
    self::checkFileName($filename);
    if (!self::isFileInDataPath($filename)) return false;
    if ($backup && !($backupFilename = self::backup($filename))) return false;
    if (unlink($filename)) return $backup ? $backupFilename : true;
    return false;
  }
  
  /* on success returns true or the backup filename */
  static public function restoreFile($backupFilename) {
    self::checkFileName($backupFilename);
    if (!($filename = self::getDataFileName($backupFilename))) return false;
    if (!file_exists($backupFilename)) return false;
    if (!file_exists($filename)) {
      return rename($backupFilename, $filename);
    } else if (rename($filename, $backupFilename.'.tmp')) {
      // we created a temporary backup of the original file...
      if (rename($backupFilename, $filename)) {
        // ... and rename it to the backup file
        if (rename($backupFilename.'.tmp', $backupFilename)) {
          return $backupFilename;
        } else {
          @unlink($backupFilename.'.tmp');
          return true;
        }
      } else {
        // ... and rename back
        @rename($backupFilename.'.tmp', $filename);
        return false;
      }
    } else {
      @unlink($filename);
      // backup of existing file failed, just overwrite:
      return rename($backupFilename, $filename);
    }
  }
  
  static public function setFileAttributes($filename) {
    self::checkName($filename);
    if (defined('ES_FILE_MOD')) chmod($filename, ES_FILE_MOD);
    if (defined('ES_FILE_OWNER')) chown($filename, ES_FILE_OWNER);
  }
  
}


class DataDir {
  
  static public function setAttributes($dirname) {
    if (defined('ES_DIR_MOD')) chmod($dirname, ES_DIR_MOD);
    if (defined('ES_DIR_OWNER')) chown($dirname, ES_DIR_OWNER);
  }
  
  static public function checkDirName($dirname) {
    if (strpos($dirname,'..') !== false) {
      Log::error('Invalid directory name %s!', $dirname);
      die("Invalid directory name $dirname!");
    }
  }
  
  static public function createDir($dirname) {
    self::checkDirName($dirname);
    if (file_exists($dirname)) {
      return is_dir($dirname);
    } else {
      self::createDir(dirname($dirname));
      if (mkdir($dirname)) {
        self::setFileAttributes($dirname);
        return true;
      } else {
        return false;
      }
    } 
  }
  
  static public function deleteDir($dirname, $recursive=false) {
    self::checkDirName($dirname);
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
    }
  }
  
}


class XmlFile extends DataFile {

  public $root = null;
  public $new = false;
	
  public function __construct($filename, $rootElement='<root></root>') {
    if ($filename && file_exists($filename)) {
      $this->root = simplexml_load_file($filename, 'SimpleXMLExtended', LIBXML_NOCDATA);
    } else {
      if (!$rootElement) $rootElement = '<root></root>';
      if (substr($rootElement,0,5) != '<?xml') {
        $rootElement = '<?xml version="1.0" encoding="UTF-8"?>'.$rootElement;
      }
      $this->root = @new SimpleXMLExtended($rootElement);
      $this->new = true;
		}
	}
  
  public function isNew() {
    return $this->new;
  }
  
  /* on success returns true or the backup filename */
  public function saveTo($filename, $backup=true) {
    if (!self::isFileInDataPath($filename)) return false;
    $backupFilename = $backup ? self::backupFile($filename) : true;
    if ($this->root->asXML($filename) === TRUE) {
      $this->new = false;
      self::setFileAttributes($filename);
      return $backupFilename;
    } else {
      return false;
    }
  }
  
  public function setProperty($parentNode, $name, $value, $variant=null) {
    if (!$parentNode) $parentNode = $this->root;
    $nodes = $parentNode->$name;
    if (count($nodes) > 0) {
      # remove already existing nodes with this $variant
      for ($i=count($nodes)-1; $i>=0; $i--) {
        $var = @$nodes[$i]['variant'];
        if ($var == $variant) unset($nodes[$i]);
      }
    }
    if (is_array($value)) {
      # $value is an array -> add multiple nodes
      foreach ($value as $val) {
        $node = $parentNode->addChild($name, $val);
        if ($variant !== null) $node['variant'] = $variant;
      }
    } else {
      # only a singe value -> single node
      $node = $parentNode->addChild($name, $value);
      if ($variant !== null) $node['variant'] = $variant;
    }
  }
  
  public function getPropertyValues($parentNode, $name, $variants=null) {
    if (!is_array($variants)) $variants = array($variants);
    $variantValues = array();
    if (!$parentNode) $parentNode = $this->root;
    if (count($parentNode->$name) > 0) {
      foreach ($parentNode->$name as $node) {
        $variant = @$node['variant'];
        if (!array_key_exists($variant, $variantValues)) $variantValues[$variant] = array();
        $variantValues[$variant][] = (string) $node;
      }
    }
    foreach ($variants as $variant) {
      if (isset($variantValues[$variant])) return $variantValues[$variant];
    }
    return null;
  }
  
  public function getStringProperty($parentNode, $name, $variants=null) {
    $value = $this->getPropertyValues($parentNode, $name, $variants);
    return $value === null ? null : join('', $value);
  }
  
  public function getTimeProperty($parentNode, $name, $variants=null) {
    $value = $this->getStringProperty($parentNode, $name, $variants);
    return is_numeric($value) ? (int) $value : strtotime($value);
  }
  
}

class XmlSlugFile extends XmlFile {

  const FIELDTYPE_ENUM  = 'enum';   # enumeration
  const FIELDTYPE_TEXT  = 'text';
  const FIELDTYPE_HTML  = 'html';
  const FIELDTYPE_DATE  = 'date';
  const FIELDTYPE_INT   = 'int';
  const FIELDTYPE_FLOAT = 'float';
  const FIELDTYPE_LIST  = 'list';   # comma separated list
  const FIELDTYPE_REF   = 'ref';    # reference to another object (slug)
  const FIELDTYPE_USER  = 'user';   # user name

  private static $translit = null;

  protected $path = null;

  public function __construct($path, $slug, $rootElement='<root></root>') {
    if (substr($path,-1) != '/') $path .= '/';
    parent::__construct($path.self::getFileSlug($slug).'.xml', $rootElement);
    if ($this->isNew()) $this->root->addChild('slug', $slug);
    $this->path = $path;
  }
  
  public function getSlug() {
    return (string) $this->root->slug;
  }
  
  public function setSlug($slug) {
    $this->root->slug = $slug;
  }
  
  public function getValues($name, $variants=null) {
    return $this->getPropertyValues($this->root, $name, $variants);
  }
  
  public function getString($name, $variants=null) {
    return $this->getStringProperty($this->root, $name, $variants);
  }
  
  public function getTime($name, $variants=null) {
    return $this->getTimeProperty($this->root, $name, $variants);
  }
  
  public function set($name, $value, $variant=null) {
    if ($name == 'slug') throw new Exception('Use setSlug() to set the slug!');
    return $this->setProperty($this->root, $name, $value, $variant);
  }
  
  public function save() {
    return $this->saveTo($this->path.self::getFileSlug($this->getSlug()).'.xml', true);
  }
  
  public function saveAs($slug) {
    $this->root->slug = $slug;
    return $this->saveTo($this->path.self::getFileSlug($this->getSlug()).'.xml', true);
  }
  
  public static function exists($path, $slug) {
    return file_exists($path.self::getFileSlug($slug).'.xml');    
  }
  
  public static function delete($path, $slug) {
    return self::deleteFile($path.self::getFileSlug($slug).'.xml', true);
  }
  
  public static function getFileSlug($slug) {
    if (self::$translit === null) {
      $translit = Settings::get('transliteration', null);
      if (!$translit) {
        $translit = 
            # ISO-8859-1:
            'À=A,Á=A,Â=A,Ã=A,Ä=AE,Å=A,Æ=AE,Ç=C,È=E,É=E,Ê=E,Ë=E,Ì=I,Í=I,Î=I,Ï=I,'.
            'Ð=D,Ñ=N,Ò=O,Ó=O,Ô=O,Õ=O,Ö=OE,Ù=U,Ú=U,Û=U,Ü=UE,Ý=Y,ß=b,'.
            'à=a,á=a,â=a,ã=a,ä=ae,å=a,æ=ae,ç=c,è=e,é=e,ê=e,ë=e,ì=i,í=i,î=i,ï=i,'.
            'ð=d,ñ=n,ò=o,ó=o,ô=o,õ=o,ö=oe,ù=u,ú=u,û=u,ü=ue,ý=y,ÿ=y,'.
            # additional characters in Windows-1252
            'Š=s,Œ=OE,Ž=z,š=s,œ=oe,ž=z,Ÿ=Y,'.
            # additional east european characters - Windows-1250:
            'Ś=S,ś=s,ź=z,Ł=L,Ą=A,Ş=S,Ż=Z,ł=l,ą=a,ş=s,Ľ=L,ľ=l,ż=z,Ŕ=R,Ă=A,Ĺ=L,'.
            'Ć=C,Č=C,Ę=E,Ě=E,Ď=D,Ń=N,Ň=N,Ř=R,Ů=U,Ű=U,Ţ=T,ŕ=r,ă=a,ĺ=l,ć=c,č=c,'.
            'ę=e,ě=e,í=i,î=i,ď=d,đ=d,ń=n,ň=n,ő=o,ř=r,ů=u,ű=u,'.
            # russian:
            'А=A,Б=B,В=V,Г=G,Д=D,Е=E,Ё=JO,Ж=ZH,З=Z,И=I,Й=JJ,'.
            'К=K,Л=L,М=M,Н=N,О=O,П=P,Р=R,С=S,Т=T,У=U,Ф=F,'.
            'Х=KH,Ц=C,Ч=CH,Ш=SH,Щ=SHCH,Ъ=,Ы=Y,Ь=,Э=EH,Ю=JU,Я=JA,';
            'а=a,б=b,в=v,г=g,д=d,е=e,ё=jo,ж=zh,з=z,и=i,й=jj,'.
            'к=k,л=l,м=m,н=n,о=o,п=p,р=r,с=s,т=t,у=u,ф=f,'.
            'х=kh,ц=c,ч=ch,ш=sh,щ=shch,ъ=,ы=y,ь=,э=eh,ю=ju,я=ja';
      }
      $translit = preg_split('/[\s\n\r\t]+/', $translit);
      self::$translit = array();
      foreach ($translit as $item) {
        $pos = strpos($item,'=',1);
        if ($pos !== false) {
          self::$translit[substr($item,0,$pos)] = substr($item,$pos+1); 
        } else self::$translit[$item] = $item;
      }
    }
    $fileslug = '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      $len = mb_strlen($slug);
      for ($i=0; $i<$len; $i++) {
        $c = mb_substr($slug, $i, $i+1);
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',$c) !== false) {
          $fileslug .= $c;
        } else if (isset($translit[$c])) {
          $fileslug .= $translit[$c];
        } else if ($fileslug != '' && substr($fileslug,-1) != '-') {
          $fileslug .= '-';
        }
      }
    } else {
      $len = strlen($slug);
      for ($i=0; $i<$len; $i++) {
        $c = substr($slug, $i, $i+1);
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',$c) !== false) {
          $fileslug .= $c;
        } else if (isset($translit[$c])) {
          $fileslug .= $translit[$c];
        } else if ($fileslug != '' && substr($fileslug,-1) != '-') {
          $fileslug .= '-';
        }
      }
    }
    return $fileslug;
  }
  
  public static function listFileSlugs($path) {
    $slugs = array();
    $dir = opendir($path);
    if ($dir) while (($filename = readdir($dir)) !== false) {
      if (substr($filename,-4) == '.xml') $slugs[] = substr($filename,0,-4);
    }
    closedir($dir);
    return $slugs;
  }
  
}


class SimpleXMLExtended extends SimpleXMLElement{   

  public function addCData($cdata_text){   
    $node= dom_import_simplexml($this);   
    $no = $node->ownerDocument;   
    $node->appendChild($no->createCDATASection($cdata_text));   
  } 
  
  public function addChild($name, $value=null, $namespace=null) {
    if ($value == null) {
      parent::addChild($name, null, $namespace); 
    } else {
      parent::addChild($name, htmlspecialchars($value), $namespace);
    }
  }
  
  public function parent() {
    return current($this->xpath('parent::*'));
  }

} 
