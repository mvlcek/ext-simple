<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_COREPATH.'inc/file.class.php');
require_once(ES_COREPATH.'inc/plugins.class.php');

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

  public function __construct($slug) {
    parent::__construct($slug, 'pages', '<page></page>');
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
      $value = Plugins::filterContentPlaceholders($value);
      $value = Plugins::execFilter('filter-content', array($value, $name));
      $this->fieldCache[$key] = $value;
      return $value;
    }
  }
  
  # ===== static functions =====
  
  public static function existsPage($slug) {
    return self::existsSlugFile($slug, 'pages');
  }

  public static function getPage($slug) {
    $fileSlug = self::getFileSlug($slug);
    if (array_key_exists($fileSlug)) {
      return self::$cache[$fileSlug];
    } else {
      return self::$cache[$fileSlug] = new Page($slug);
    }
  }
  
  public static function deletePage($slug) {
    return self::deleteSlugFile($slug, 'pages');
  }

}
