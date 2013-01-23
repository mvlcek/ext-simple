<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

if(!defined('IN_ES')) die('You cannot load this page directly.'); 

require_once(ES_ADMINPATH.'inc/file.class.php');

class TimeMachine extends XmlFile {
  
  const ACTION_PREFIX_SAVE   = 'save-';
  const ACTION_PREFIX_DELETE = 'delete-';
  const ACTION_PREFIX_RENAME = 'rename-';
  const ACTION_PREFIX_UNDO   = 'undo-';
  const ACTION_PREFIX_REDO   = 'redo-';
  
  private static $actionLog = null;
  
  private $currentAction = null;
  
  private function __construct() {
    parent::__construct(ES_BACKUPSPATH.'action-log.xml', '<actions></actions>');
  }
  
  private function save() {
    return $this->saveTo(ES_BACKUPSPATH.'action-log.xml');
  }
  
  public static function getActionLog() {
    if (!self::$actionLog) self::$actionLog = new TimeMachine();
    return self::$actionLog;
  }
  
  public static function backup($action, $messageKey, $undoMessageKey, $filename=null, $params=null) {
    $actionLog = self::getActionLog();
    if (!$actionLog->currentAction) {
      $actions = $actionLog->root->action;
      $id = count($actions) > 0 ? $actions[count($actions)-1]['id']+1 : 0;
      $actionLog->currentAction = $actionLog->root->addChild('action');
      $actionLog->currentAction->addAttribute('id', $id);
    } else {
      $id = $actionLog->currentAction['id'];
    }
    $success = true;
    $num = count($actionLog->currentAction->step);
    $step = $actionLog->currentAction->addChild('step');
    $step->addAttribute('num', $num);
    $step->addChild('name', $action);
    $step->addChild('message', $messageKey);
    $step->addChild('undoMessage', $undoMessageKey);
    if ($params) {
      if (!is_array($params)) $params = array($params);
      foreach ($params as $param) $step->addChild('param', (string) $param);
    }
    if ($filename && substr($filename,0,strlen(ES_DATAPATH)) == ES_DATAPATH) {
      $step->addChild('file', substr($filename,strlen(ES_DATAPATH)));
      $backupname = ES_BACKUPSPATH.$id.'-'.$num.'-'.substr($filename,strlen(ES_DATAPATH));
      DataDir::createDir(dirname($backupname));
      $success = rename($filename, $backupname);
    }
    if ($success) {
      execAction('before-action-save', array($actionLog));
      return $actionLog->save();
    }
    return false;
  }
  
  public static function restore($id) {
    $actionLog = self::getActionLog();
    foreach ($actionLog->root->action as $action) {
      if ($action['id'] == $id) {
        foreach ($action->step as $step) {
          $undo = substr($step->name,0,strlen(self::ACTION_UNDO)) != ACTION_UNDO;
          $newName = $undo ? self::ACTION_UNDO.$step->name : 
                             self::ACTION_REDO.substr($step->name,strlen(self::ACTION_UNDO));
          $params = array();
          foreach ($step->param as $param) $params[] = (string) $param;
          if ($step->filename) {
            if (file_exists(ES_DATAPATH.$step->filename)) {
              if (! self::backup($newName, $step->undoMessage, $step->message, $step->filename, $params)) ;
            }
          }
        }
        return;
      }
    }
  }
  
}