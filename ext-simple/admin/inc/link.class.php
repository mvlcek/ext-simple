<?php

class Link {
  
  const WILDCARD_PATTERN = '/%([^%]*)\$(\w+)(|[^%\w][^%]*)%/'; 
  
  public static function parse($urlFormat, $link, $placeholderPatterns=null) {
    if ($placeholderPatterns === null) {
      $placeholderPatterns = array('slug'=>'\w+', 'parent'=>'\w+', 'variant'=>'\w+'); 
    }
    if (preg_match_all(self::WILDCARD_PATTERN, $urlFormat, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      // get the place holders
      $placeholderNames = array();
      foreach ($matches as $match) {
        $placeHolderNames[] = $match[2][0];
      }
      // get the patterns for the place holders
      $placeholderInfo = execForInfo('get-link-patterns', array($placeholderNames));
      $placeholderPatterns = array_merge($placeholderPatterns, $placeholderInfo);
      // create a pattern to parse the link
      $pattern = '"^';
      $pos = 0;
      foreach ($matches as $match) {
        $placeholderName = $match[2][0];
        $pos1 = $match[0][1];
        if ($pos1 > $pos) $pattern .= substr($urlFormat, $pos, $pos1-$pos);
        $pattern .= '(?:';
        if ($match[1][0]) $pattern .= $match[1][0];
        $pattern .= '(';
        $pattern .= $placeholderPatterns[$placeholderName];
        $pattern .= ')';
        if ($match[3][0]) $pattern .= $match[3][0];
        $pattern .= ')?';
        $pos = $pos1 + strlen($match[0][0]);
      }
      if ($pos < strlen($urlFormat)) $pattern .= substr($urlFormat, $pos);
      $pattern .= '$"';
      $result = array();
      if (preg_match($pattern, $link, $match)) {
        for ($i=0; $i<count($match); $i++) {
          if ($match[$i]) $result[$placeholderNames[$i]] = $match[$i]; 
        }
      }
      return $result;
    } 
    return array();
  }
  
  public static function format($urlFormat, $placeholderValues=array()) {
    if (preg_match_all(self::WILDCARD_PATTERN, $urlFormat, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
      // get the place holders
      $placeholderNames = array();
      foreach ($matches as $match) {
        $placeHolderNames[] = $match[2][0];
      }
      // get the values for the place holders
      $placeholderInfo = execForInfo('get-link-values', array($placeholderNames));
      $placeholderValues = array_merge($placeholderValues, $placeholderInfo);
      // format link
      $pos = 0;
      $link = '';
      foreach ($matches as $match) {
        $placeholderName = $match[2][0];
        $pos1 = $match[0][1];
        if ($pos1 > $pos) $link .= substr($urlFormat, $pos, $pos1-$pos);
        if ($placeholderValues[$placeholderName]) {
          if ($match[1][0]) $link .= $match[1][0];
          $link .= $placeholderValues[$placeholderName];
          if ($match[2][0]) $link .= $match[2][0];
        }
      }
      if ($pos < strlen($urlFormat)) $link .= substr($urlFormat, $pos);
      return $link;
    } 
    return $urlFormat;    
  }
  
}