<?php

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../3rparty/ConnectedDrive.php';
//include_file('3rdparty', 'ConnectedDrive', 'php', 'BMWConnectedDrive');

define("TYPE_ELECTRIC", "electric");
define("TYPE_HYBRID", "hybrid");
define("TYPE_THERMAL", "thermal");

class BMWConnectedDrive extends eqLogic {

  /*************** Attributs ***************/


  /************* Static methods ************/
  public static function cron30($_eqLogic_id = null)
  {
      // Récupère la liste des équipements
      if ($_eqLogic_id == null)
      {
          $eqLogics = self::byType('BMWConnectedDrive', true);
      }
      else
      {
          $eqLogics = array(self::byId($_eqLogic_id));
      }
      // Met à jour l'ensemble des équipements
      foreach ($eqLogics as $BMWObj)
      {
          $BMWObj->refreshCarInfos();
      }
  }

  /**************** Methods ****************/
  public function getConnection(){

    $bmwVin = $this->getConfiguration("bmw_vin");
    $bmwUsername = $this->getConfiguration("bmw_username");
    $bmwPassword = $this->getConfiguration("bmw_password");

    if($bmwVin == "") { //si le paramètre est vide ou n’existe pas
      log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre VIN de votre véhicule');
			throw new Exception(__('500 - BMW VIN missing', __FILE__));
		}

    if($bmwUsername == "") { //si le paramètre est vide ou n’existe pas
      log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre username de votre compte BMW Connected Drive');
			throw new Exception(__('500 - BMW Username missing', __FILE__));
		}

    if($bmwPassword == "") { //si le paramètre est vide ou n’existe pas
      log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre password de votre compte BMW Connected Drive');
			throw new Exception(__('500 - BMW Password missing', __FILE__));
		}

    log::add('BMWConnectedDrive', 'debug', 'Connection car vin:'.$bmwVin.' with username:'.$bmwUsername);
    $bmwConnection = new ConnectedDrive($bmwVin, $bmwUsername, $bmwPassword);

    return $bmwConnection;
  }

  public function refreshCarInfos() {

    $bmwConnection= $this->getConnection();
    $response = $bmwConnection->getInfo();
    $bmwCarInfo = $response->body;

    log::add('BMWConnectedDrive', 'debug', "car->getInfo : ['.$result->httpCode.'] ".serialize($bmwCarInfo));

    // On récupère les informations de BMWConnectedDrive
    $this->checkAndUpdateCmd('beRemainingRangeElectric', $bmwCarInfo->attributesMap->beRemainingRangeElectric);
    $this->checkAndUpdateCmd('chargingLevelHv', $bmwCarInfo->attributesMap->chargingLevelHv);
    $this->checkAndUpdateCmd('chargingStatus', $bmwCarInfo->attributesMap->charging_status);
    $this->checkAndUpdateCmd('connectorStatus', $bmwCarInfo->attributesMap->connectorStatus);
    $this->checkAndUpdateCmd('doorLockState', $bmwCarInfo->attributesMap->door_lock_state);
    $this->checkAndUpdateCmd('mileage', $bmwCarInfo->attributesMap->mileage);
    $this->checkAndUpdateCmd('unitOfLength', $bmwCarInfo->attributesMap->unitOfLength);
    $this->checkAndUpdateCmd('lightsParking', $bmwCarInfo->attributesMap->lights_parking);
    $this->checkAndUpdateCmd('doorDriverRear', $bmwCarInfo->attributesMap->door_driver_rear);
    $this->checkAndUpdateCmd('doorDriverFront', $bmwCarInfo->attributesMap->door_driver_front);
    $this->checkAndUpdateCmd('doorPassengerRear', $bmwCarInfo->attributesMap->door_passenger_rear);
    $this->checkAndUpdateCmd('doorPassengerFront', $bmwCarInfo->attributesMap->door_passenger_front);
    $this->checkAndUpdateCmd('windowDriverFront', $bmwCarInfo->attributesMap->window_driver_front);
    $this->checkAndUpdateCmd('windowDriverRear', $bmwCarInfo->attributesMap->window_driver_rear);
    $this->checkAndUpdateCmd('windowPassengerFront', $bmwCarInfo->attributesMap->window_passenger_front);
    $this->checkAndUpdateCmd('windowPassengerRear', $bmwCarInfo->attributesMap->window_passenger_rear);
    $this->checkAndUpdateCmd('trunk_state', $bmwCarInfo->attributesMap->trunk_state);
    $this->checkAndUpdateCmd('beRemainingRangeFuelKm', $bmwCarInfo->attributesMap->beRemainingRangeFuelKm);
    $this->checkAndUpdateCmd('remaining_fuel', $bmwCarInfo->attributesMap->remaining_fuel);
    $this->checkAndUpdateCmd('gps_lat', $bmwCarInfo->attributesMap->gps_lat);
    $this->checkAndUpdateCmd('gps_lng', $bmwCarInfo->attributesMap->gps_lng);
    $this->checkAndUpdateCmd('hood_state', $bmwCarInfo->attributesMap->hood_state);
    $this->checkAndUpdateCmd('lastUpdate', date('d/m/Y H:i:s'));

    $messages = $bmwCarInfo->vehicleMessages->cbsMessages;
    $table_messages = array();
    foreach ($messages as $message) {
      $table_messages[] = array( "title" => $message->text, "description" => $message->description, "date" => $message->date);
    }

    $this->checkAndUpdateCmd('vehicleMessages', json_encode($table_messages));

    log::add('BMWConnectedDrive', 'debug', 'End of car info refresh : ' . $response->httpCode);

    return $bmwCarInfo;
  }

  public function refreshCarNavigationInfo (){
    $bmwConnection= $this->getConnection();
    $bmwCarNavigationInfo = $bmwConnection->getNavigationInfo();
    log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwCarNavigationInfo->body));
    return $bmwCarNavigationInfo;
  }

  public function refreshCarEfficiency(){
    $bmwConnection= $this->getConnection();
    $bmwCarEfficiency= $bmwConnection->getEfficiency();
    log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwCarEfficiency->body));
    return $bmwCarEfficiency;
  }

  public function getRemoteServicesStatus(){
    $bmwConnection= $this->getConnection();
    $bmwRemoteServicesStatus= $bmwConnection->getRemoteServicesStatus();
    log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwRemoteServicesStatus->body));
    return $bmwRemoteServicesStatus;
  }

  public function doHornBlow(){
    $bmwConnection= $this->getConnection();
    $result = $bmwConnection->doHornBlow();
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
  }

  public function doLightFlash(){
    $bmwConnection= $this->getConnection();
    $result = $bmwConnection->doLightFlash();
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
  }

  public function doDoorLock(){
    $bmwConnection= $this->getConnection();
    $result = $bmwConnection->doDoorLock();
    var_dump($result);
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
  }

  public function doDoorUnlock(){
    $bmwConnection= $this->getConnection();
    $result = $bmwConnection->doDoorUnlock();
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
  }

  public function doClimateNow(){
    $bmwConnection= $this->getConnection();
    $result = $bmwConnection->doClimateNow();
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
  }

  public function doMessageToBMW($title, $message){
    $bmwConnection= $this->getConnection();
    log::add('BMWConnectedDrive', 'debug', 'Titre message : '.$title.' - Corps message : '.$message);
    $result = $bmwConnection->doSendMessage($titre,$message);
    log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.']');
  }


  public function postSave() {

    /* add of info : Etat de la charge */
    $info = $this->getCmd(null, 'chargingStatus');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Etat de la charge', __FILE__));
    }
    $info->setLogicalId('chargingStatus');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Etat de la prise */
    $info = $this->getCmd(null, 'connectorStatus');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Etat de la prise', __FILE__));
    }
    $info->setLogicalId('connectorStatus');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Serrure */
    $info = $this->getCmd(null, 'doorLockState');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Verrouillage', __FILE__));
    }
    $info->setLogicalId('doorLockState');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Eclairage */
    $info = $this->getCmd(null, 'lightsParking');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Eclairage', __FILE__));
    }
    $info->setLogicalId('lightsParking');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Kilométrage */
    $info = $this->getCmd(null, 'mileage');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Kilométrage', __FILE__));
    }
    $info->setLogicalId('mileage');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();

    /* add of info : Unité de distance */
    $info = $this->getCmd(null, 'unitOfLength');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Unité de distance', __FILE__));
    }
    $info->setLogicalId('unitOfLength');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Messages du véhicule */
    $info = $this->getCmd(null, 'vehicleMessages');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Messages', __FILE__));
    }
    $info->setTemplate("dashboard",'bmw_message_mmi');
    $info->setLogicalId('vehicleMessages');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Km restant */
    $info = $this->getCmd(null, 'beRemainingRangeElectric');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Km restant (électrique)', __FILE__));
    }
    $info->setLogicalId('beRemainingRangeElectric');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();

    /* add of info : % restant */
    $info = $this->getCmd(null, 'chargingLevelHv');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Pourcentage restant', __FILE__));
    }
    $info->setLogicalId('chargingLevelHv');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('numeric');
    $info->save();

    /* add of info : Dernière mise à jour */
    $info = $this->getCmd(null, 'lastUpdate');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Dernière mise à jour', __FILE__));
    }
    $info->setLogicalId('lastUpdate');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Porte Conducteur Arrière*/
    $info = $this->getCmd(null, 'doorDriverRear');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Porte Conducteur Arrière', __FILE__));
    }
    $info->setLogicalId('doorDriverRear');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Porte Passager Arrière*/
    $info = $this->getCmd(null, 'doorPassengerRear');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Porte Passager Arrière', __FILE__));
    }
    $info->setLogicalId('doorPassengerRear');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Porte Conducteur Avant */
    $info = $this->getCmd(null, 'doorDriverFront');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Porte Conducteur Avant', __FILE__));
    }
    $info->setLogicalId('doorDriverFront');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Porte Passager Avant*/
    $info = $this->getCmd(null, 'doorPassengerFront');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Porte Passager Avant', __FILE__));
    }
    $info->setLogicalId('doorPassengerFront');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Conducteur Avant*/
    $info = $this->getCmd(null, 'windowDriverFront');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Fenêtre Conducteur Avant', __FILE__));
    }
    $info->setLogicalId('windowDriverFront');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Passager Avant */
    $info = $this->getCmd(null, 'windowPassengerFront');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Fenêtre Passager Avant', __FILE__));
    }
    $info->setLogicalId('windowPassengerFront');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Conducteur Arrière*/
    $info = $this->getCmd(null, 'windowDriverRear');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Fenêtre Conducteur Arrière', __FILE__));
    }
    $info->setLogicalId('windowDriverRear');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Passager Arrière*/
    $info = $this->getCmd(null, 'windowPassengerRear');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Fenêtre Passager Arrière', __FILE__));
    }
    $info->setLogicalId('windowPassengerRear');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Coffre */
    $info = $this->getCmd(null, 'trunk_state');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Coffre', __FILE__));
    }
    $info->setLogicalId('trunk_state');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Passager Avant */
    $info = $this->getCmd(null, 'beRemainingRangeFuelKm');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Km restant (thermique)', __FILE__));
    }
    $info->setLogicalId('beRemainingRangeFuelKm');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Fenetre Passager Avant */
    $info = $this->getCmd(null, 'remaining_fuel');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Carburant restant', __FILE__));
    }
    $info->setLogicalId('remaining_fuel');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Latitude GPS */
    $info = $this->getCmd(null, 'gps_lat');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('GPS Latitude', __FILE__));
    }
    $info->setLogicalId('gps_lat');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Longitude GPS */
    $info = $this->getCmd(null, 'gps_lng');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('GPS Longitude', __FILE__));
    }
    $info->setLogicalId('gps_lng');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of info : Capot Moteur*/
    $info = $this->getCmd(null, 'hood_state');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Capot Moteur', __FILE__));
    }
    $info->setLogicalId('hood_state');
    $info->setEqLogic_id($this->getId());
    $info->setType('info');
    $info->setSubType('string');
    $info->save();

    /* add of cmd : Rafraichir */
    $cmd = $this->getCmd(null, 'refresh');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Rafraichir', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('refresh');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : Climatisation */
    $cmd = $this->getCmd(null, 'climateNow');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Climatiser', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('climateNow');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : Verrouillage */
    $cmd = $this->getCmd(null, 'doorLock');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Verrouiller', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('doorLock');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : Déverrouillage */
    $cmd = $this->getCmd(null, 'doorUnlock');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Déverrouiller', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('doorUnlock');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : Feux */
    $cmd = $this->getCmd(null, 'lightFlash');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Appel de phares', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('lightFlash');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : Déverrouillage */
    $cmd = $this->getCmd(null, 'hornBlow');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Klaxonner', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('hornBlow');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();

    /* add of cmd : MessageInfo */
    $cmd = $this->getCmd(null, 'messageToBMW');
    if (!is_object($cmd)) {
     $cmd = new BMWConnectedDriveCmd();
     $cmd->setName(__('Message à ma BMW', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('messageToBMW');
    $cmd->setType('action');
    $cmd->setSubType('message');
    $cmd->save();
  }


  /*public function toHtml($_version = 'dashboard') {
    log::add('BMWConnectedDrive', 'debug', "Start rendering");

    $replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);

    $type = ($this->getConfiguration("type") !='') ? $this->getConfiguration("type") : TYPE_ELECTRIC;
    log::add('BMWConnectedDrive', 'debug', "Car type".$this->getConfiguration("type"));
    $chargingLevelHv = $this->getCmd(null, 'chargingLevelHv');
    $replace['#chargingLevelHv#'] = is_object($chargingLevelHv) ? $chargingLevelHv->execCmd() : '';
    $replace['#chargingLevelHvid#'] = is_object($chargingLevelHv) ? $chargingLevelHv->getId() : '';


    $html = template_replace($replace, getTemplate('core', $version, $type.'_car_info', 'BMWConnectedDrive'));
    log::add('BMWConnectedDrive', 'debug', "Rendering".$html);
    cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
    return $this->postToHtml($_version, $html);

    }

        /*
        $temperature = $this->getCmd(null, 'temperature');
        $replace['#temperature#'] = is_object($temperature) ? $temperature->execCmd() : '';
        $replace['#tempid#'] = is_object($temperature) ? $temperature->getId() : '';

        $humidity = $this->getCmd(null, 'humidity');
        $replace['#humidity#'] = is_object($humidity) ? $humidity->execCmd() : '';

        $pressure = $this->getCmd(null, 'pressure');
        $replace['#pressure#'] = is_object($pressure) ? $pressure->execCmd() : '';
        $replace['#pressureid#'] = is_object($pressure) ? $pressure->getId() : '';

        $wind_speed = $this->getCmd(null, 'wind_speed');
        $replace['#windspeed#'] = is_object($wind_speed) ? $wind_speed->execCmd() : '';
        $replace['#windid#'] = is_object($wind_speed) ? $wind_speed->getId() : '';

        $sunrise = $this->getCmd(null, 'sunrise');
        $replace['#sunrise#'] = is_object($sunrise) ? $sunrise->execCmd() : '';
        $replace['#sunid#'] = is_object($sunrise) ? $sunrise->getId() : '';
        if (strlen($replace['#sunrise#']) == 3) {
            $replace['#sunrise#'] = substr($replace['#sunrise#'], 0, 1) . ':' . substr($replace['#sunrise#'], 1, 2);
        } else if (strlen($replace['#sunrise#']) == 4) {
            $replace['#sunrise#'] = substr($replace['#sunrise#'], 0, 2) . ':' . substr($replace['#sunrise#'], 2, 2);
        }

        $sunset = $this->getCmd(null, 'sunset');
        $replace['#sunset#'] = is_object($sunset) ? $sunset->execCmd() : '';
        if (strlen($replace['#sunset#']) == 3) {
            $replace['#sunset#'] = substr($replace['#sunset#'], 0, 1) . ':' . substr($replace['#sunset#'], 1, 2);
        } else if (strlen($replace['#sunset#']) == 4) {
            $replace['#sunset#'] = substr($replace['#sunset#'], 0, 2) . ':' . substr($replace['#sunset#'], 2, 2);
        }

        $wind_direction = $this->getCmd(null, 'wind_direction');
        $replace['#wind_direction#'] = is_object($wind_direction) ? $wind_direction->execCmd() : 0;

        $refresh = $this->getCmd(null, 'refresh');
        $replace['#refresh_id#'] = is_object($refresh) ? $refresh->getId() : '';

        $condition = $this->getCmd(null, 'condition_now');
        $sunset_time = is_object($sunset) ? $sunset->execCmd() : null;
        $sunrise_time = is_object($sunrise) ? $sunrise->execCmd() : null;
        if (is_object($condition)) {
            $replace['#icone#'] = self::getIconFromCondition($condition->execCmd(), $sunrise_time, $sunset_time);
            $replace['#condition#'] = $condition->execCmd();
            $replace['#conditionid#'] = $condition->getId();
            $replace['#collectDate#'] = $condition->getCollectDate();
        } else {
            $replace['#icone#'] = '';
            $replace['#condition#'] = '';
            $replace['#collectDate#'] = '';
        }
        if ($this->getConfiguration('modeImage', 0) == 1) {
            $replace['#visibilityIcon#'] = "none";
            $replace['#visibilityImage#'] = "block";
        } else {
            $replace['#visibilityIcon#'] = "block";
            $replace['#visibilityImage#'] = "none";
        }
        $html = template_replace($replace, getTemplate('core', $version, 'current', 'weather'));
        cache::set('widgetHtml' . $_version . $this->getId(), $html, 0);
        return $html;
    }*/
}

class BMWConnectedDriveCmd extends cmd {

  /*************** Attributs ***************/

  /************* Static methods ************/

  /**************** Methods ****************/


  public function execute($_options = array()) {
    log::add('BMWConnectedDrive', 'debug', 'Exécution commande ' . $this->getLogicalId());
    $eqLogic = $this->getEqLogic();

    try {
  		switch ($this->getLogicalId()) {
  			case 'refresh':
    			$eqLogic->refreshCarInfos();
    			break;
        case 'hornBlow':
    			$eqLogic->doHornBlow();
    			break;
        case 'lightFlash':
          $eqLogic->doLightFlash();
          break;
        case 'doorLock':
          $eqLogic->doDoorLock();
          break;
        case 'doorUnlock':
          $eqLogic->doDoorUnlock();
          break;
        case 'climateNow':
          $eqLogic->doClimateNow();
          break;
        case 'messageToBMW':
          $eqLogic->doMessageToBMW($_options['title'], $_options['message']);
          break;
        default:
          throw new \Exception("Commande inconnue", 1);
          break;
  		}
    } catch (Exception $e) {
        echo 'Exception reçue : ',  $e->getMessage(), "\n";
        log::add('BMWConnectedDrive', 'debug', 'Erreur exécution commande ' . $this->getLogicalId() . ' - ' . $e->getMessage());
    }
	}

  /********** Getters and setters **********/

}
