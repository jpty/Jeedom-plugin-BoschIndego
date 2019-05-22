<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* Voir description :
 * https://github.com/zazaz-de/iot-device-bosch-indego-controller/blob/master/PROTOCOL.md
 */

/* * ***************************Includes********************************* */
require_once __DIR__  .'/../../../../core/php/core.inc.php';

class BoschIndego extends eqLogic {

  public function initParams(&$params,$username='',$password='',$setAlmSn=1) {
    // log::add(__CLASS__,'debug',__FUNCTION__);
    if($username == '' ) {
      $params['username'] = config::byKey('username', __CLASS__);
    }
    else $params['username'] = $username;
    if($password == '' ) {
      $params['password'] = config::byKey('password', __CLASS__);
    }
    else $params['password'] = $password;
    $params['api'] = 'https://api.indego.iot.bosch-si.com/api/v1/';
    $params['contextId'] = '';
    $params['userId'] = '';
    if($setAlmSn == 1) $params['almSn'] = $this->getConfiguration('almSn');
    else $params['almSn'] = '';
  }

  function writeData($filename,$data) {
    $ret = file_put_contents($filename,$data);
    if($ret === FALSE)
      log::add(__CLASS__, 'debug',"Unable to write file : $filename");
  }

  public function cronBoschIndego() {
    // log::add(__CLASS__,'debug', __FUNCTION__);
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      $eqLogic->initParams($params);
      $eqLogic->getInformation($params);
      $eqLogic->refreshWidget();
    }
  }

  public function cronNextMow() {
    log::add(__CLASS__,'debug', __FUNCTION__);
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      $eqLogic->cronSetEnable(1); // Demarrage cron de surveillance
      break;
    }
  }

  public function cronSetEnable($enable) {
    log::add(__CLASS__,'debug', __FUNCTION__ .' ' .($enable)?"On":"Off");
    $cron = cron::byClassAndFunction('BoschIndego', 'cronBoschIndego');
    if (is_object($cron)) {
      $cron->setEnable($enable);
      $cron->save();
      if ( $enable ) $this->CheckAndUpdateCmd('state',262);
      return($this->cronGetEnable());
    }
    else log::add(__CLASS__,'error',__FUNCTION__ .' cronBoschIndego not found');
    return(-1);
  }

  public function cronGetEnable() {
    $cron = cron::byClassAndFunction('BoschIndego', 'cronBoschIndego');
    if (is_object($cron)) {
      $status = $cron->getEnable(0);
    }
    else {
      log::add(__CLASS__,'error',__FUNCTION__ .' cronBoschIndego not found');
      $status = -1;
    }
    $this->CheckAndUpdateCmd('cronState',$status);
    return($status);
  }

  public function getInformation($params) {
    $retVal = $this->checkAuthentication($params);
    if($retVal['httpCode'] != 200)
      throw new Exception(__('Erreur d\'authentification. Impossible d\'exécuter '.__FUNCTION__, __FILE__));
    $url = $params['api'] ."alms/" .$params['almSn'] ."/state";
    log::add(__CLASS__,'debug', __FUNCTION__ .' URL=' .$url);
    $curl    = curl_init();
    $headers = array('Content-type: application/json','x-im-context-id: ' .$params['contextId']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    if ( $curlHttpCode == 200 ) {
// $this->writeData(__DIR__ ."/indego_dataGetInformation-" .$params['almSn'] .".json",$result);
      $dataJsonState = json_decode($result);
        //
      $cmd = $this->getCmd('info', 'state');
      if (is_object($cmd)) $prevState = $cmd->execCmd();
      else $prevState = 0;
      $state =  $dataJsonState->state;
      if( $state == 64513) $state = 258;
      $this->CheckAndUpdateCmd('state',$state);
        //
      $mowed =  $dataJsonState->mowed;
      $this->CheckAndUpdateCmd('mowed',$mowed);
        //
      $mowmode =  $dataJsonState->mowmode;
      $this->CheckAndUpdateCmd('mowmode',$mowmode);
        //
      // $statusDate =  date('d-m-Y H:i:s');
      setlocale(LC_TIME,"fr_FR.utf8");
      $statusDate = strftime("%A %e %b %H:%M:%S", time());
      $this->CheckAndUpdateCmd('statusDate',$statusDate);
        //
      $totalOperate =  $dataJsonState->runtime->total->operate;
      $this->CheckAndUpdateCmd('totalOperate',$totalOperate);
        //
      $totalCharge =  $dataJsonState->runtime->total->charge;
      $this->CheckAndUpdateCmd('totalCharge',$totalCharge);
        //
      $sessionOperate =  $dataJsonState->runtime->session->operate;
      $this->CheckAndUpdateCmd('sessionOperate',$sessionOperate);
        //
      $sessionCharge =  $dataJsonState->runtime->session->charge;
      $this->CheckAndUpdateCmd('sessionCharge',$sessionCharge);
        //
      $svg_xPos =  $dataJsonState->svg_xPos;
      $this->CheckAndUpdateCmd('svg_xPos',$svg_xPos);
        //
      $svg_yPos =  $dataJsonState->svg_yPos;
      $this->CheckAndUpdateCmd('svg_yPos',$svg_yPos);

        // test carte a mettre à jour
      $cmd = $this->getCmd('info', 'map');
      $prevMap = '';
      if (is_object($cmd)) $prevMap = $cmd->execCmd();
      $Umap =  $dataJsonState->map_update_available;
      if ( $Umap || $prevMap == '')
        $this->getMap($params);

      $this->getAlerts($params);
        // Arret du cron si tondeuse sur station
      $cronState = $this->cronGetEnable();
      if($cronState == 1 && $state == 258 && $prevState == 258) {
        $this->cronSetEnable(0); // Arret cron
        $this->getMap($params);  // Recup carte
        // Recup date prochaine tonte
        $this->getNextMowingDatetime($params);
        log::add(__CLASS__,'debug', "Arret cron");
      }
    }
    return($retVal);
  }

  public function getNextMowingDatetime($params) {
    log::add('BoschIndego','debug',__FUNCTION__ .' Sn:' .$params['almSn']);
    $cmd = $this->getCmd('info', 'mowmode');
    if (is_object($cmd)) $mowmode = $cmd->execCmd();
    else $mowmode = 0;
// log::add('BoschIndego','debug',__FUNCTION__ .' Mowmode:' .$mowmode);
    if ( $mowmode == 1 ) { // mode manu demandé
      $mowNext = "Mode manuel";
      $retVal = array('httpCode'=> 200, 'data'=> $mowNext);
      $this->CheckAndUpdateCmd('mowNext',$mowNext);
      $this->CheckAndUpdateCmd('mowNextTS',0);
      $this->cronNextMowDelete();
      return($retVal);
    }
    $cmd = $this->getCmd('info', 'mowNextTS');
    if (is_object($cmd)) $mowNextTS = $cmd->execCmd();
    else $mowNextTS = 0;
    setlocale(LC_TIME,"fr_FR.utf8");
    $url = $params['api'] ."alms/" .$params['almSn'] ."/predictive/nextcutting?last=YYYY-MM-DDTHH:MM:SS%2BHH:MM";
    $curl    = curl_init();
    $headers = array('Content-type: application/json','x-im-context-id: ' .$params['contextId']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    $mowNext = "Mode manuel"; $dateTS = 0;
    if ( $curlHttpCode == 200 ) {
// $this->writeData(__DIR__ ."/indego_dataNextMowingDatetime-" .$params['almSn'] .".json",$result);
      $dataJson = json_decode($result);
      if ( $dataJson !== null ) {
        $dateTS = date_create_from_format("Y-m-d\TH:i:sP", $dataJson->mow_next);
        if ( $dateTS != false ) {
          $mowNext = strftime("%A %e %b %H:%M", $dateTS->getTimestamp());
          $dateTS = $dateTS->getTimestamp();
        }
        else
          log::add(__CLASS__, 'debug', __FUNCTION__ .' DateFormat: ' .$dataJson->mow_next);
      }
      if($dateTS == 0) $this->cronNextMowDelete();
      else $this->cronNextMowSet($dateTS);
    }
    else log::add(__CLASS__, 'error', __FUNCTION__ .' Sn:' .$params['almSn'] .' HTTP_CODE: ' .$curlHttpCode);
    $this->CheckAndUpdateCmd('mowNext',$mowNext);
    $this->CheckAndUpdateCmd('mowNextTS',$dateTS);
    return($retVal);
  }

  public function cronNextMowSet($TS) {
    log::add('BoschIndego','debug',__FUNCTION__);
    $cron = cron::byClassAndFunction('BoschIndego', 'cronNextMow');
    if (!is_object($cron)) {
      log::add('BoschIndego','debug',__FUNCTION__ .' Creating cronNextMow entry');
      $cron = new cron();
      $cron->setClass('BoschIndego');
      $cron->setFunction('cronNextMow');
      $cron->setEnable(1);
      $cron->setOnce(1);
      $cron->setDeamon(0);
    }
    $cron->setSchedule(date('i',$TS) .' ' .date('H',$TS) .' ' .date('d',$TS) .' ' .date('m',$TS) .' * ' .date('Y',$TS));
    $cron->save();
  }

  public function cronNextMowDelete() {
    log::add('BoschIndego','debug',__FUNCTION__);
    $cron = cron::byClassAndFunction('BoschIndego', 'cronNextMow');
    if (is_object($cron)) {
      log::add('BoschIndego','debug',__FUNCTION__ .' Removing cronNextMow entry');
      $cron->remove();
    }
  }

  public function messageAlert($alert) {
    log::add(__CLASS__,'debug', __FUNCTION__);
    // $tz = date_default_timezone_get();
    // date_default_timezone_set( "UTC" );
    // $seconds = timezone_offset_get( timezone_open($tz), new DateTime() );
    // date_default_timezone_set($tz);
    $ts = strtotime($alert->date); // + $seconds;
    setlocale(LC_TIME,"fr_FR.utf8");
    $date = strftime("%A %e %b %H:%M:%S", $ts);
    $headline = $alert->headline;
    $error_code = $alert->error_code;
    $message = $alert->message;
    // return($date .' ' .$headline .' ' .$error_code .' ' .$message);
    $uid = $alert->alert_id;
    // $msg = "<div style=\"background-color:#2982b9;color:#ffffff;margin-top:2px;border-radius:5px;margin-right:5px;margin-left:5px;\"><i class=\"fas fa-times pull-left cursor removeEvent\" data-uid=\"$uid\" style=\"margin-top:12px;margin-left:2px;\"></i><span style=\"font-weight:bold;\">$date<br/>$headline Code: $error_code<br/></span>";
    $msg = "<div style=\"background-color:#2982b9;color:#ffffff;margin-top:2px;border-radius:5px;margin-right:5px;margin-left:5px;\"><span style=\"font-weight:bold;\">$date<br/>$headline Code: $error_code<br/></span>";
    $msg .= "<span style=\"font-size:0.8em;font-weight:normal;line-height:0.8em;\">" .$message ."</span>";
    $msg .= "</div>";
    return($msg);
  }

  public function getAlerts($params) {
    log::add(__CLASS__,'debug', __FUNCTION__);
    $url = $params['api'] ."alerts";
    $curl    = curl_init();
    $headers = array('x-im-context-id: ' .$params['contextId']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    if ( $curlHttpCode == 200 ) {
// $this->writeData(__DIR__ ."/indego_dataAlerts.json",$result);
      $alerts = '';
      $dataJson = json_decode($result);
      if ( $dataJson !== null ) {
        foreach ( $dataJson as $alert ) {
          if( $alert->alm_sn == $params['almSn'] ) {
            $alerts .= $this->messageAlert($alert);
            break;
          }
        }
      }
      if($alerts == '') $alerts = "<div style=\"background-color:#2982b9;color:#ffffff;margin-top:2px;border-radius:5px;margin-right:5px;margin-left:5px;\"><span style=\"font-weight:bold;\">Pas d'alerte</span></div>";
      $this->CheckAndUpdateCmd('alerts',$alerts);
    }
    return($retVal);
  }

  public function getMap($params) {
    log::add(__CLASS__,'debug', __FUNCTION__ ." " .$params['almSn']);
    $url = $params['api'] ."alms/" .$params['almSn'] ."/map";
    $curl    = curl_init();
    $headers = array('x-im-context-id: ' .$params['contextId']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    if ( $curlHttpCode == 200 ) {
// $this->writeData(__DIR__ ."/indego_dataMap-" .date('dmHi') ."-" .$params['almSn'] .".svg",$map);
        //
      $map = $result;
      $cmd = $this->getCmd('info', 'svg_xPos');
      if (is_object($cmd)) $xpos = $cmd->execCmd();
      else $xpos = 0;
      $cmd = $this->getCmd('info', 'svg_yPos');
      if (is_object($cmd)) $ypos = $cmd->execCmd();
      else $ypos = 0;
        // carte sur une seule ligne de texte. Sinon erreur javascript
      $map = str_replace("\n","",$map);
        // ajout de la position de la tondeuse sur la carte
      $map = str_replace("</svg>","<circle cx=\"$xpos\" cy=\"$ypos\" r=\"14\" stroke=\"black\" stroke_width=\"3\" fill=\"green\" /></svg>",$map);
      $this->CheckAndUpdateCmd('map',$map);
    }
    return($retVal);
  }

  public function authenticate(&$params) {
    $urlA =$params['api'] .'authenticate'; 
    $requestBody = array(
          'device' => '',
          'os_type' => 'Android',
          'os_version' => '4.0',
          'dvc_manuf' => 'unknown',
          'dvc_type' => 'unknown',
      );
    $requestBody = json_encode($requestBody);
    $requestHeader = array(
      'Authorization: Basic ' .base64_encode($params['username'] .':' .$params['password']),
      'Content-Type: application/json'
    );    
    $curl = curl_init($urlA);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeader);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    if ( $curlHttpCode == 200 ) {
// $this->writeData(__DIR__ ."/indego_dataTokenAuthenticate.json",$result);
        // MAJ des paramètre authentification
      $json_data = json_decode($result);
      $params['contextId'] = $json_data->contextId;
      $params['userId'] = $json_data->userId;
      $params['almSn'] = $json_data->alm_sn; 
    }
    return($retVal);
  }

  public function checkAuthentication(&$params) {
    $urlCA =$params['api'] .'authenticate/check'; 
    $requestHeader = array(
      'Authorization: Basic '.base64_encode($params['username'].':' .$params['password']),
      'x-im-context-id: ' .$params['contextId']
      );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $urlCA);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeader);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
    if ( $curlHttpCode == 200 ) {
//$this->writeData(__DIR__ ."/indego_dataTokenCheckAuth".$params['almSn'].".json",$result);
        // MAJ des paramètre authentification
      $json_data = json_decode($result);
      $params['contextId'] = $json_data->contextId;
      $params['userId'] = $json_data->userId;
      // $params['almSn'] = $json_data->alm_sn; 
    }
    return($retVal);
  }

  public function deauthenticate() {
  }

  public function doAction($action,$params) {
    log::add(__CLASS__,'debug', __FUNCTION__);
    $action = strtolower($action);
    $available_actions = array("mow", "pause", "returntodock");
    if(in_array($action, $available_actions)) {
      $retVal = $this->checkAuthentication($params);
      if($retVal['httpCode'] != 200)
        throw new Exception(__('Erreur d\'authentification. Impossible d\'exécuter la commande : ' .$action, __FILE__));
      $data      = array("state" => $action);
      $data_json = json_encode($data);
      $url       = $params['api'] ."alms/" .$params['almSn'] ."/state";
      $headers   = array('Content-type: application/json','x-im-context-id: ' .$params['contextId']);
          
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
      curl_setopt($curl, CURLOPT_TIMEOUT, (($action=='mow')?30:15));
      $result = curl_exec($curl);
      $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
      $retVal = array('httpCode'=> $curlHttpCode, 'data'=> $result);
        //
      $actionHttpCode = (($curlHttpCode == 200) ? "200-OK" : "$curlHttpCode-ERROR");
      $this->CheckAndUpdateCmd('actionHttpCode',$actionHttpCode);
        //
      setlocale(LC_TIME,"fr_FR.utf8");
      $actionDate = strftime("%A %e %b %H:%M:%S", time());
      $this->CheckAndUpdateCmd('actionDate',$actionDate);
        //
      $actionLast = $action;
      $this->CheckAndUpdateCmd('actionLast',$actionLast);
    
      /*
        $date = date('d-m-Y H:i:s');
        $json  = "{\n  \"httpCode\" : \"";
        $json .= (($curlHttpCode == 200) ? "200-OK" : "$curlHttpCode-ERROR");
        $json .= "\",\n  \"date\" : \"$date\"\n}";
$this->writeData(__DIR__ ."/indego_datadoAction.json",$json);
      */
      return($retVal);
    }
    else {
      $msg = "Unsupported action : $action. Only ".implode(", ",$available_actions) ." are supported";
      log::add(__CLASS__,'debug', $msg);
    }
  }

  public function disableCalendar() {
  }

  public function enableCalendar() {
  }

    /*     * *********************Méthodes d'instance************************* */

  public function preInsert() {
  }

  public function postInsert() {
  }

  public function preSave() {
  }

  public function creationCmd($logicId,$id,$name) {
    $cmd = BoschIndegoCmd::byEqLogicIdAndLogicalId($logicId,$id);
    if (!is_object($cmd)) {
      $cmd = new BoschIndegoCmd();
      $cmd->setName(__($name, __FILE__));
      $cmd->setEqLogic_id($logicId);
      $cmd->setEqType(__CLASS__);
      $cmd->setLogicalId($id);
      $cmd->setType('info');
      $cmd->setSubType('string');
      $cmd->setIsVisible('1');
      $cmd->save();
      // $cmd->event(0);
    }
    return($cmd);
  }

  public function postSave() {
    if (is_object($this)) {
      $logicId = $this->getId();
      $cmdLogicalId = 'statusDate';
      $order = 100;
        //
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Date état');
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'state'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Etat');
        $cmd->setTemplate("dashboard",'BoschIndegoStateV3');
        $cmd->setIsHistorized(1);
        $cmd->setConfiguration("historizeMode","none");
        $cmd->setConfiguration("repeatEventManagement","always");
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'mowed'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Avancement tonte');
        $cmd->setUnite('%');
        $cmd->setTemplate("dashboard",'badge' );
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'mowmode'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Mode tonte');
        $cmd->setTemplate("dashboard",'line' );
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->setIsVisible('0');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'mowNext'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Prochaine tonte');
        $cmd->setTemplate("dashboard",'line' );
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'mownextTS'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'mowNextTimestamp');
        $cmd->setTemplate("dashboard",'line' );
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->setIsVisible('0');
        $cmd->save();
      }
        //
      foreach (array("mow", "pause", "returntodock", "refresh", "cronSetEnableOn", "cronSetEnableOff") as $actionId) {
        $actionCmd = $this->getCmd('action', $actionId);
        $order++;
        if (!is_object($actionCmd)) {
          $cmd = new BoschIndegoCmd();
          $cmd->setEventOnly(0);
          if($actionId == 'mow') {
            $cmd->setDisplay("forceReturnLineBefore","1");
            $cmd->setDisplay("icon","<i class=\"fas fa-play\" style=\"font-size : 24;\"></i>");
            $cmd->setName('Tondre');
          }
          else if($actionId == 'pause') {
            $cmd->setDisplay("icon","<i class=\"fas fa-pause\" style=\"font-size : 24;\"></i>");
            $cmd->setName('Pause');
          }
          else if($actionId == 'returntodock') {
            $cmd->setDisplay("icon","<i class=\"fas maison-house109\" style=\"font-size : 24;\"></i>");
            $cmd->setName('Retour station');
          }
          else if($actionId == 'refresh') {
            $cmd->setName('Rafraichir');
          }
          else {
            $cmd->setName($actionId);
            $cmd->setIsVisible('0');
          }
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($logicId);
          $cmd->setEqType(__CLASS__);
          $cmd->setLogicalId($actionId);
          $cmd->setType('action');
          $cmd->setSubType('other');
          $cmd->save();
        }
      }
        //
      $cmdLogicalId = 'actionDate'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Date dernière action');
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'actionLast'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Dernière action');
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'actionHttpCode'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Retour dernière action');
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'totalOperate'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Durée fonctionnement');
        $cmd->setDisplay("forceReturnLineBefore","1");
        $cmd->setUnite('min');
        $cmd->setTemplate("dashboard",'BoschIndegoDureeV3');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'totalCharge'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Durée totale charge');
        $cmd->setUnite('min');
        $cmd->setTemplate("dashboard",'BoschIndegoDureeV3');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'sessionOperate'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Durée dernière tonte');
        $cmd->setUnite('min');
        $cmd->setTemplate("dashboard",'BoschIndegoDureeV3');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'sessionCharge'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Durée charge session');
        $cmd->setTemplate("dashboard",'BoschIndegoDureeV3');
        $cmd->setUnite('min');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'map'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Carte pelouse');
        $cmd->setDisplay("forceReturnLineBefore","1");
        $cmd->setTemplate("dashboard",'BoschIndegoSvgV3');
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'svg_xPos'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Svg_xPos');
        $cmd->setTemplate("dashboard",'line');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->setIsVisible('0');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'svg_yPos'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Svg_yPos');
        $cmd->setTemplate("dashboard",'line');
        $cmd->setOrder($order);
        $cmd->setSubType('numeric');
        $cmd->setIsVisible('0');
        $cmd->save();
      }
        //
      $cmdLogicalId = 'alerts'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Dernier message');
        $cmd->setDisplay("forceReturnLineBefore","1");
        $cmd->setDisplay("showNameOndashboard","0");
        $cmd->setOrder($order);
        $cmd->save();
      }
        //
      $cmdLogicalId = 'cronState'; $order++;
      $cmd = $this->getCmd('info', $cmdLogicalId);
      if (!is_object($cmd)) {
        $cmd = $this->creationCmd($logicId,$cmdLogicalId,'Daemon surveillance');
        // $cmd->setTemplate("dashboard",'line');
        $cmd->setOrder(1); // $order);
        $cmd->setSubType('binary');
        $cmd->setIsVisible('1');
        $cmd->save();
      }
    }
  }

  public function preUpdate() {
  }

  public function postUpdate() {
  }

  public function preRemove() {
  }

  public function postRemove() {
  }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
  public function toHtml($_version = 'dashboard') {
  }
     */

    /*
     * Non obligatoire permet de déclencher une action après modification de variable de configuration
  public static function postConfig_<Variable>() {
  }
     */

    /*
     * Non obligatoire permet de déclencher une action avant modification de variable de configuration
  public static function preConfig_<Variable>() {
  }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class BoschIndegoCmd extends cmd {
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
   * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
    return true;
    }
   */

  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
      throw new Exception(__('Equipement desactivé impossible d\'exécuter la commande : ' .$this->getHumanName(), __FILE__));
    }
    // log::add('BoschIndego', 'debug', __METHOD__ .'(' .json_encode($_options) .') Type: ' .$this->getType() .' logicalId: ' .$this->getLogicalId());
    $username = $eqLogic->getConfiguration('username');
    $password = $eqLogic->getConfiguration('password');
    $eqLogic->initParams($params,$username,$password,1);
    switch ($this->getLogicalId()) {
      case "cronSetEnableOn":
        log::add('BoschIndego', 'debug', "Action " .$this->getLogicalId());
        $eqLogic->cronSetEnable(1);
        break;
      case "cronSetEnableOff":
        log::add('BoschIndego', 'debug', "Action " .$this->getLogicalId());
        $eqLogic->cronSetEnable(0);
        break;
      case "mow":
        log::add('BoschIndego', 'debug', "Action " .$this->getLogicalId());
        $eqLogic->doAction('mow',$params);
        $eqLogic->cronSetEnable(1);
        break;
      case "pause":
        log::add('BoschIndego', 'debug', "Action " .$this->getLogicalId());
        $eqLogic->doAction('pause',$params);
        break;
      case "returntodock":
        log::add('BoschIndego', 'debug', "Action " .$this->getLogicalId());
        $eqLogic->doAction('returnToDock',$params);
        break;
      case "refresh":
        $retVal = $eqLogic->getInformation($params);
        if($retVal['httpCode'] == 200) {
          $ret2 = $eqLogic->getNextMowingDatetime($params); // Recup date prochaine tonte
          if($ret2['httpCode'] != 200)
            log::add(__CLASS__, 'error', 'getNextMowingDatetime  Sn:' .$params['almSn'] .' HTTP_CODE:' .$ret2['httpCode']);
        }
        else log::add(__CLASS__, 'error', 'Action:refresh  Sn:' .$params['almSn'] .' HTTP_CODE:' .$retVal['httpCode']);
        break;
      default:
        log::add('BoschIndego', 'error', "Unknown action " .$this->getLogicalId());
    }  
  }

  /*     * **********************Getteur Setteur*************************** */

}
