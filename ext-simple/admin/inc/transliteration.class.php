<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

if(!defined('IN_ES')) die('You cannot load this page directly.'); 

require_once(ES_ADMINPATH.'inc/settings.class.php');

class Transliteration {
  
  private static $translit = null;

  public static function get($text) {
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
    $result = '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
      $len = mb_strlen($text);
      for ($i=0; $i<$len; $i++) {
        $c = mb_substr($text, $i, $i+1);
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',$c) !== false) {
          $result .= $c;
        } else if (isset($translit[$c])) {
          $result .= $translit[$c];
        } else if ($result != '' && substr($result,-1) != '-') {
          $result .= '-';
        }
      }
    } else {
      $len = strlen($text);
      for ($i=0; $i<$len; $i++) {
        $c = substr($text, $i, $i+1);
        if (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',$c) !== false) {
          $result .= $c;
        } else if (isset($translit[$c])) {
          $result .= $translit[$c];
        } else if ($result != '' && substr($result,-1) != '-') {
          $result .= '-';
        }
      }
    }
    return $result;
  }
  
}