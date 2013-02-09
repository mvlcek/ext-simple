<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/plugins.php');

/**
 * The Link class provides function to construct fancy URLS from parameters
 * and parse URLS to extract the parameters.
 * 
 * The idea is that there is one rule in the .htaccess file (see below) which will 
 * be never changed, regardless of the plugins added or the fancy URL changed.
 * A required parameter in the URL look like this (slug is the name of the parameter): 
 *   {$slug}
 * An optional parameter has characters before/after the name: 
 *   {$parent/} {;$page}
 * 
 * A full URL pattern with language and optional page number could look like this:
 *   /{$language}/{$parent/}{$slug}{;$page}
 * 
 * The rewrite rule in the .htaccess file looks like this:
 *   RewriteCond %{REQUEST_FILENAME} -f
 *   RewriteRule ^ - [L]
 *   RewriteRule ^(.*)$ index.php/$1 [L]
 * 
 * The part to be parsed is then $_SERVER['REQUEST_URI']
 */
class Link {
  
  const ITEM_SPEC_PATTERN    = '"\{([^\}]*)\$(\w+)(|[^\}\w][^\}]*)\}"'; 
  const DEFAULT_ITEM_PATTERN = '\w[\w-]*';
  
  /**
   * Parses an URI based on a pattern
   * 
   * @since 1.0
   * @param string $urlSpec      the URI pattern, e.g. /{$parent/}{$slug}
   * @param string $link         the URI, in most cases $_SERVER['REQUEST_URI']
   * @param array  $itemPatterns patterns for the parameters, if not set and not
   *                              returned by listeners get-link-pattern, defaults
   *                              to Link::DEFAULT_ITEM_PATTERN
   */
  public static function parse($urlSpec, $link, $itemPatterns=array()) {
    if (preg_match_all(self::ITEM_SPEC_PATTERN, $urlSpec, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      // get the place holders
      $itemNames = array();
      foreach ($matches as $match) {
        $itemNames[] = $match[2][0];
      }
      // get the patterns for the items
      $itemInfo = execForInfo('get-link-patterns', array($itemNames));
      $itemPatterns = array_merge($itemPatterns, $itemInfo);
      // create a pattern to parse the link
      $pattern = '"^';
      $pos = 0;
      foreach ($matches as $match) {
        $itemName = $match[2][0];
        $pos1 = $match[0][1];
        if ($pos1 > $pos) $pattern .= substr($urlSpec, $pos, $pos1-$pos);
        $pattern .= '(?:';
        if ($match[1][0]) $pattern .= $match[1][0];
        $pattern .= '(';
        $pattern .= isset($itemPatterns[$itemName]) ? $itemPatterns[$itemName] : self::DEFAULT_ITEM_PATTERN;
        $pattern .= ')';
        if ($match[3][0]) $pattern .= $match[3][0];
        $pattern .= ')';
        if ($match[1][0] || $match[3][0]) $pattern .= '?';
        $pos = $pos1 + strlen($match[0][0]);
      }
      if ($pos < strlen($urlSpec)) $pattern .= substr($urlSpec, $pos);
      $pattern .= '$"';
      $result = array();
      if (preg_match($pattern, $link, $match)) {
        for ($i=1; $i<count($match); $i++) {
          if ($match[$i]) $result[$itemNames[$i-1]] = $match[$i]; 
        }
        return $result;
      }
      return null;
    } 
    // quite unlikely: pattern with no parameters
    return array();
  }
  
  /**
   * Format an URL based on a pattern
   * 
   * @since 1.0
   * @param string $urlSpec    the URI pattern, e.g. /{$parent/}{$slug}
   * @param array  $itemValues the values for the items, e.g.: array('parent'=>'product','slug'=>'bike')
   *                            further values will be requested using listener get-link-values
   */
  public static function format($urlSpec, $itemValues=array()) {
    if (preg_match_all(self::ITEM_SPEC_PATTERN, $urlSpec, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      // get the place holders
      $itemNames = array();
      foreach ($matches as $match) {
        $itemNames[] = $match[2][0];
      }
      // get the values for the place holders
      $itemInfo = execForInfo('get-link-values', array($itemNames));
      $itemValues = array_merge($itemValues, $itemInfo);
      // format link
      $pos = 0;
      $link = '';
      foreach ($matches as $match) {
        $itemName = $match[2][0];
        $pos1 = $match[0][1];
        if ($pos1 > $pos) $link .= substr($urlSpec, $pos, $pos1-$pos);
        if (isset($itemValues[$itemName]) && $itemValues[$itemName]) {
          if ($match[1][0]) $link .= $match[1][0];
          $link .= $itemValues[$itemName];
          if ($match[3][0]) $link .= $match[3][0];
        } else if (!$match[1][0] && !$match[3][0]) {
          // this is not an optional parameter!
          return null;
        }
        $pos = $pos1 + strlen($match[0][0]);
      }
      if ($pos < strlen($urlSpec)) $link .= substr($urlSpec, $pos);
      return $link;
    } 
    // quite unlikely: pattern with no parameters
    return $urlSpec;    
  }
  
}