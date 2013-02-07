<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');
require_once(ES_ADMINPATH.'inc/plugins.php');

/**
 * A page contains the following standard fields:
 * 
 * - General properties:
 *   - type: type of page: e.g. built in "normal", "link"
 *   - visibility: visibility of page, e.g. "private"
 *   - template: the template to use when displaying this page
 *   
 * - Text properties, may exist in multiple variants (attribute variant="..."):
 *   - title: the title of the page (text)
 *   - description: a pure-text description of the page for the HTML header (text)
 *   - tags: comma separated tags and keywords
 *   - content: the HTML content - no PHP code (HTML)
 *   - menuText: the text to display in the menu
 * 
 * - Protocol and publication properties:
 *   - createdAt: time at which the page was first saved
 *   - createdBy: user who created the page
 *   - modifiedAt: time at which the page was last modified  
 *   - modifiedBy: user who made the last modification
 *   - publishFrom: time at which to publish the page (empty = immediately)
 *   - publishUntil: time after which the page is not shown anymore (empty = show indefinitely)
 */
 
class Page extends XmlSlugFile {
  
  const TYPE_NORMAL = 'normal';
  const TYPE_LINK = 'link';
  const VISIBILITY_PUBLIC = 'public';
  const VISIBILITY_PRIVATE = 'private';
  const MENU_VISIBLE = 'visible';
  
  private static $cache = array();
  
  private $fieldCache = array();

  public static function existsPage($slug) {
    return self::exists(ES_PAGESPATH, $slug);
  }

  public static function getPage($slug) {
    $fileSlug = self::getFileSlug($slug);
    if (array_key_exists($fileSlug)) {
      return self::$cache[$fileSlug];
    } else {
      return self::$cache[$fileSlug] = new Page($slug);
    }
  }

  public function __construct($slug) {
    parent::__construct(ES_PAGESPATH, $slug, '<page></page>');
  }
  
  public function isPublic() {
    return self::VISIBILITY_PUBLIC == (string) $this->visibility;
  }
  
  public function isPrivate() {
    return self::VISIBILITY_PRIVATE == (string) $this->visibility;
  }
  
  public function isPublished() {
    $now = time();
    $from = $this->getTime('publishFrom');
    $until = $this->getTime('publishUntil');
    return $from <= $now && $now < $until;
  }

  public function getFilteredString($name, $variants=null) {
    $key = $name.':'.join(':', $variants);
    if (array_key_exists($key, $this->fieldCache)) {
      return $this->fieldCache[$key];
    } else {
      $value = $this->getString($name, $variants);
      $value = execFilter('filter-content', array($value, $name));
      $this->fieldCache[$key] = $value;
      return $value;
    }
  }
  
  public static function getFieldTypes($objType) {
    // those field types are independent of the type of page ($objType)
    return array(
      'type' => self::FIELDTYPE_ENUM,
      'visibility' => self::FIELDTYPE_ENUM,
      'template' => self::FIELDTYPE_REF,
      'parent' => self::FIELDTYPE_REF,
      'previous' => self::FIELDTYPE_REF,
      'menuState' => self::FIELDTYPE_ENUM,
      'title' => self::FIELDTYPE_TEXT,
      'description' => self::FIELDTYPE_TEXT,
      'tags' => self::FIELDTYPE_LIST,
      'content' => self::FIELDTYPE_HTML,
      'menuText' => self::FIELDTYPE_TEXT,
      'createdAt' => self::FIELDTYPE_DATE,
      'createdBy' => self::FIELDTYPE_USER,
      'modifiedAt' => self::FIELDTYPE_DATE,
      'modifiedBy' => self::FIELDTYPE_USER,
      'publishFrom' => self::FIELDTYPE_DATE,
      'publishUntil' => self::FIELDTYPE_DATE
    );
  }

}


addListener('get-fieldtypes-pages', 'Page::getFieldTypes');
