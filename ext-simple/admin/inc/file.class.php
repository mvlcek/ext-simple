<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/log.class.php');
require_once(ES_ADMINPATH.'inc/plugins.php');
require_once(ES_ADMINPATH.'inc/settings.class.php');

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
  
  static public function exists($filename) {
    return file_exists($filename);
  }
  
  static public function getBackupFileName($filename) {
    if (!substr($filename,0,strlen(ES_DATAPATH)) == ES_DATAPATH) return false;
    return ES_BACKUPSPATH.substr($filename,strlen(ES_DATAPATH));
  }
  
  static public function getFileName($backupFilename) {
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
    if (!($filename = self::getFileName($backupFilename))) return false;
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
    $parent = dirname($dirname);
    if ($parent != '.' && !file_exists($parent)) {
      if (!self::createDir($parent)) return false;
    }
    if (mkdir($dirname)) {
      self::setFileAttributes($dirname);
      return true;
    } else {
      return false;
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

  const FIELDTYPE_ENUM  = 'enum';   # enumeration
  const FIELDTYPE_TEXT  = 'text';
  const FIELDTYPE_HTML  = 'html';
  const FIELDTYPE_DATE  = 'date';
  const FIELDTYPE_INT   = 'int';
  const FIELDTYPE_FLOAT = 'float';
  const FIELDTYPE_LIST  = 'list';   # comma separated list
  const FIELDTYPE_REF   = 'ref';    # reference to another object (slug)
  const FIELDTYPE_USER  = 'user';   # user name

  private static $fieldTypes = array();

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
  
  public function getString($name, $variant=null) {
    $default = null;
    foreach ($this->root->$name as $field) {
      $fieldVariant = @$field['variant'];
      if ($fieldVariant == $variant) return (string) $field;
      if (!$fieldVariant) $default = (string) $field;
    }
    return $default;
  }
  
  public function getTime($name, $variant=null) {
    $value = $this->getString($name, $variant);
    return is_numeric($value) ? (int) $value : strtotime($value);
  }
  
}

class XmlSlugFile extends XmlFile {

  private $path = null;

  public function __construct($path, $slug, $rootElement='<root></root>') {
    $filename = getFileSlug($slug).'.xml';
    parent::__construct($path.(substr($path,-1)!='/' ? '/' : '').$filename, $rootElement);
    if ($this->isNew()) $this->root->addChild('slug', $slug);
    $this->path = $path;
  }
  
  public function getSlug() {
    return (string) $this->root->slug;
  }
  
  public function save() {
    $filename = getFileSlug($this->getSlug()).'.xml';
    $this->saveTo($this->path.$filename, true);
  }
  
  public static function getFileSlug($slug) {
    $translit = Settings::get('trans', null);
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
          'Х=KH,Ц=C,Ч=CH,Ш=SH,Щ=SHCH,Ъ=",Ы=Y,Ь=\',Э=EH,Ю=JU,Я=JA,';
          'а=a,б=b,в=v,г=g,д=d,е=e,ё=jo,ж=zh,з=z,и=i,й=jj,'.
          'к=k,л=l,м=m,н=n,о=o,п=p,р=r,с=s,т=t,у=u,ф=f,'.
          'х=kh,ц=c,ч=ch,ш=sh,щ=shch,ъ=,ы=y,ь=,э=eh,ю=ju,я=ja';
    }
    $translit = preg_split('/[\s\n\r\t]+/');
    $trans = array();
    foreach ($translit as $item) {
      $pos = strpos($item,'=',1);
      if ($pos !== false) $trans[substr($item,0,$pos)] = substr($item,$pos+1); else $trans[$item] = $item;
    }
    $fileslug = '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      $len = mb_strlen($slug);
      for ($i=0; $i<$len; $i++) {
        $c = mb_substr($slug, $i, $i+1);
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',c) !== false) {
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
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',c) !== false) {
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
  
  public static function getFieldTypes($objClass, $objType) {
    $fullType = $objClass . ($objType ? '-'.$objType : '');
    if (!isset(self::$fieldTypes[$fullType])) {
      self::$fieldTypes[$fullType] = execForInfo('get-fieldtypes-'.$objClass, $objType);
    }
    return self::$fieldTypes[$fullType];
  }
  
  public static function getFieldType($objClass, $objType, $name) {
    $fullType = $objClass . ($objType ? '-'.$objType : '');
    $types = self::getFieldTypes($fullType);
    return @$types[$name];
  }
  
  /**
   * The object class is the part of the path after ES_DATAPATH, e.g. 'pages'
   */
  public static function getObjectClassFromPath($path) {
    if (substr($path,-1) == '/') $path = substr($path,0,-1);
    if (substr($path,0,strlen(ES_DATAPATH)) == ES_DATAPATH) {
      return substr($path, strlen(ES_DATAPATH)+1);
    }
    return $path;
  }
  
}


class SimpleXMLExtended extends SimpleXMLElement{   

  public function addCData($cdata_text){   
    $node= dom_import_simplexml($this);   
    $no = $node->ownerDocument;   
    $node->appendChild($no->createCDATASection($cdata_text));   
  } 

} 
