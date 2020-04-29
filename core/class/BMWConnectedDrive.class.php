<?php

require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';
require_once dirname(__FILE__).'/../../3rparty/ConnectedDrive.php';

class BMWConnectedDrive extends eqLogic {

  /*************** Attributs ***************/
  /*private $bmwVin;
  private $bmwUsername;
  private $bmwPassword;
  private $bmwToken;
  private $bmwExpires;

  public static function __construct (){
    $this->bmwVin = '';
    $this->bmwUsername='';
    $this->bmwPassword='';
    $this->bmwToken='';
    $this->bmwExpires=0;
  }*/

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
  public function refreshCarInfos() {
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

    log::add('BMWConnectedDrive', 'debug', 'Refresh car vin:'.$bmwVin.' with username:'.$bmwUsername);
    $bmwConnection = new \net\bluewalk\connecteddrive\ConnectedDrive($bmwVin, $bmwUsername, $bmwPassword);

    $bmwCarInfo = $bmwConnection->getInfo();

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
    $this->checkAndUpdateCmd('windowPassengerFront', $bmwCarInfo->attributesMap->window_passenger_front);
    $this->checkAndUpdateCmd('chargingStatus', $bmwCarInfo->attributesMap->charging_status);
    $this->checkAndUpdateCmd('lastUpdate', date('d/m/Y H:i:s'));

    log::add('BMWConnectedDrive', 'debug', 'End of car refresh vin:'.$bmwVin.' with username:'.$bmwUsername);
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
     $info->setName(__('Serrure', __FILE__));
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

    /* add of info : Km restant */
    $info = $this->getCmd(null, 'beRemainingRangeElectric');
    if (!is_object($info)) {
     $info = new BMWConnectedDriveCmd();
     $info->setName(__('Km restant', __FILE__));
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

    /* add of cmd : Refresh */
    $refresh = $this->getCmd(null, 'refresh');
    if (!is_object($refresh)) {
     $refresh = new BMWConnectedDriveCmd();
     $refresh->setName(__('Rafraichir', __FILE__));
    }
    $refresh->setEqLogic_id($this->getId());
    $refresh->setLogicalId('refresh');
    $refresh->setType('action');
    $refresh->setSubType('other');
    $refresh->save();
  }
}

class BMWConnectedDriveCmd extends cmd {

  /*************** Attributs ***************/

  /************* Static methods ************/

  /**************** Methods ****************/


  public function execute($_options = array()) {
    log::add('BMWConnectedDrive', 'debug', 'Exécution d\'une commande sur ' . $this->getLogicalId());
    $eqLogic = $this->getEqLogic();

		switch ($this->getLogicalId()) {
			case 'refresh':
			$eqLogic->refreshCarInfos();
			break;
		}
	}

  /********** Getters and setters **********/

}
