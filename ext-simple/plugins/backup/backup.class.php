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
  
  const ACTION_CREATE = 'create'; # Parameter [filename]  
  const ACTION_SAVE   = 'update'; # Parameter [filename, newFilename]
  const ACTION_DELETE = 'delete'; # Parameter [filename]
  
  private static $actionLog = null;
  
  private $currentAction = null;
  
  public static function getActionLog() {
    if (!self::$actionLog) self::$actionLog = new TimeMachine();
    return self::$actionLog;
  }
  
  private function __construct() {
    parent::__construct(ES_BACKUPSPATH.'action-log.xml', '<actions></actions>');
  }

  public function addAction($action, $params, $typeKey, $titleKey, $restoreSuccessKey=null, $restoreFailureKey=null) {
    $actions = $this->getAll('action');
    $id = 0;
    foreach ($actions as $action) {
      if ((int) $action->attribute('id') >= $id) $id = (int) $action->attribute('id')+1;
    }
    $this->currentAction = $this->add('action', array('id'=>$id, 'action'=>$action, 'type'=>$typeKey, 'time'=>time()));
    $this->currentAction->add('titleKey', null, $titleKey);
    $this->currentAction->add('restoreSuccessKey', null, $restoreSuccessKey);
    $this->currentAction->add('restoreFailureKey', null, $restoreFailureKey);
    if ($params) {
      if (!is_array($params)) $params = array($params);
      foreach ($params as $param) $this->currentAction->add('param', null, (string) $param);
    }
    $success = execWhile('backup-'.$action, $params, null);
    if ($success === null) {
      $success = $this->processDefaultAction($id, null, $action, $params);
    }
    if ($success) {
      return $this->save();
    }
    return false;
  }
  
  public function addSubAction($action, $params) {
    if (!$this->currentAction) return false;
    $id = $this->currentAction->attribute('id');
    $subActions = $this->currentAction->getAll('subAction');
    $step = count($subActions)+1;
    $subAction = $this->currentAction->add('subAction', array('step'=>$step, 'action'=>$action));
    if ($params) {
      if (!is_array($params)) $params = array($params);
      foreach ($params as $param) $subAction->add('param', null, (string) $param);
    }
    $success = execWhile('backup-'.$action, $params, null);
    if ($success === null) {
      $success = $this->processDefaultAction($id, $step, $action, $params);
    }
    if ($success) {
      return $this->save();
    }
    return false;
  }

  public function processDefaultAction($id, $step, $action, $params) {
    switch ($action) {
      case 'create':
        return true;
      case 'update':
      case 'delete': 
        $filename = $params[0];
        if (file_exists($filename)) {
          $backupFilename = ES_BACKUPSPATH.$id.'-'.($step ? $step.'-' : '').basename($filename);
          return rename($filename, $backupFilename);
        }
    }
  }
  
  public function undo($id) {
    $undoAction = $this->get('action', array('id'=>$id));
    if (!$undoAction) return false;
    
  }

  private function backup($type, $filename, $params=null, $backupMessageKey=null, $restoreMessageKey=null) {
  }

  
  
  public static function backup($filename, $params=null, $backupMessageKey=null, $restoreMessageKey=null) {
    $result = $actionLog->backup($filename, $params, $backupMessageKey, $restoreMessageKey);
    execAction();
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