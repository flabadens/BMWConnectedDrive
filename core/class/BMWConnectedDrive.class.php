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
    public static function cron30($eqLogicId = null)
    {
        // Récupère la liste des équipements
        $equipments = ($eqLogicId == null) ? self::byType('BMWConnectedDrive', true) : [self::byId($eqLogicId)];

        // Met à jour l'ensemble des équipements
        foreach ($equipments as $bmwEquipment) {
            $bmwEquipment->refreshCarInfos();
        }
    }

    /**************** Methods ****************/
    public function getConnection()
    {
        $bmwVin = $this->getConfiguration("bmw_vin");
        $bmwUsername = $this->getConfiguration("bmw_username");
        $bmwPassword = $this->getConfiguration("bmw_password");

        //si le paramètre est vide ou n’existe pas
        if (empty($bmwVin)) {
            log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre VIN de votre véhicule');
            throw new Exception(__('500 - BMW VIN missing', __FILE__));
        }

        //si le paramètre est vide ou n’existe pas
        if (empty($bmwUsername)) {
            log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre username de votre compte BMW Connected Drive');
            throw new Exception(__('500 - BMW Username missing', __FILE__));
        }

        //si le paramètre est vide ou n’existe pas
        if (empty($bmwPassword)) {
            log::add('BMWConnectedDrive', 'debug', 'Vous devez remplir le paramètre password de votre compte BMW Connected Drive');
            throw new Exception(__('500 - BMW Password missing', __FILE__));
        }

        log::add('BMWConnectedDrive', 'debug', 'Connection car vin:'.$bmwVin.' with username:'.$bmwUsername);
        return new ConnectedDrive($bmwVin, $bmwUsername, $bmwPassword);
    }

    public function refreshCarInfos()
    {
        $bmwConnection= $this->getConnection();
        $response = $bmwConnection->getInfo();
        $bmwCarInfo = $response->body;

        log::add('BMWConnectedDrive', 'debug', "car->getInfo : ['.$response->httpCode.'] ".serialize($bmwCarInfo));

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
        $tableMessages = [];
        foreach ($messages as $message) {
            $tableMessages[] = [
                "title" => $message->text,
                "description" => $message->description,
                "date" => $message->date
            ];
        }

        $this->checkAndUpdateCmd('vehicleMessages', json_encode($tableMessages));

        log::add('BMWConnectedDrive', 'debug', 'End of car info refresh : ' . $response->httpCode);

        return $bmwCarInfo;
    }

    public function refreshCarNavigationInfo()
    {
        $bmwCarNavigationInfo = $this->getConnection()->getNavigationInfo();
        log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwCarNavigationInfo->body));
        return $bmwCarNavigationInfo;
    }

    public function refreshCarEfficiency()
    {
        $bmwCarEfficiency= $this->getConnection()->getEfficiency();
        log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwCarEfficiency->body));
        return $bmwCarEfficiency;
    }

    public function getRemoteServicesStatus()
    {
        $bmwRemoteServicesStatus= $this->getConnection()->getRemoteServicesStatus();
        log::add('BMWConnectedDrive', 'debug', "car->getInfo".serialize($bmwRemoteServicesStatus->body));
        return $bmwRemoteServicesStatus;
    }

    public function doHornBlow()
    {
        $result = $this->getConnection()->doHornBlow();
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
    }

    public function doLightFlash()
    {
        $result = $this->getConnection()->doLightFlash();
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
    }

    public function doDoorLock()
    {
        $result = $this->getConnection()->doDoorLock();
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
    }

    public function doDoorUnlock()
    {
        $result = $this->getConnection()->doDoorUnlock();
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
    }

    public function doClimateNow()
    {
        $result = $this->getConnection()->doClimateNow();
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.'] '.$result->body->remoteServiceEvent->remoteServiceType.' - '.$result->body->remoteServiceEvent->remoteServiceStatus.' - '.$result->body->remoteServiceEvent->lastUpdate);
    }

    public function doMessageToBMW($title, $message)
    {
        log::add('BMWConnectedDrive', 'debug', 'Titre message : '.$title.' - Corps message : '.$message);
        $result = $this->getConnection()->doSendMessage($title, $message);
        log::add('BMWConnectedDrive', 'debug', 'End of car event : ['.$result->httpCode.']');
    }


    public function postSave()
    {
        // add info : Etat de la charge
        $this->saveInfo('charginStatus', 'Etat de la charge', 'info', 'string');

        // add of info : Etat de la prise
        $this->saveInfo('connectorStatus', 'Etat de la prise', 'info', 'string');

        // add of info : Serrure
        $this->saveInfo('doorLockState', 'Verrouillage', 'info', 'string');

        // add of info : Eclairage
        $this->saveInfo('lightsParking', 'Eclairage', 'info', 'string');

        // add of info : Kilométrage
        $this->saveInfo('mileage', 'Kilométrage', 'info', 'numeric');

        // add of info : Unité de distance
        $this->saveInfo('unitOfLength', 'Unité de distance', 'info', 'string');

        // add of info : Messages du véhicule
        $this->saveInfo('vehicleMessages', 'Messages', 'info', 'string', ['dashboard', 'bmw_message_mmi']);

        // add of info : Km restant
        $this->saveInfo('beRemainingRangeElectric', 'Km restant (électrique)', 'info', 'numeric');

        // add of info : % restant
        $this->saveInfo('chargingLevelHv', 'Pourcentage restant', 'info', 'numeric');

        // add of info : Dernière mise à jour
        $this->saveInfo('lastUpdate', 'Dernière mise à jour', 'info', 'string');

        // add of info : Porte Conducteur Arrière
        $this->saveInfo('doorDriverRear', 'Porte Conducteur Arrière', 'info', 'string');

        // add of info : Porte Passager Arrière
        $this->saveInfo('doorPassengerRear', 'Porte Passager Arrière', 'info', 'string');

        // add of info : Porte Conducteur Avant
        $this->saveInfo('doorDriverFront', 'Porte Conducteur Avant', 'info', 'string');

        // add of info : Porte Passager Avant
        $this->saveInfo('doorPassengerFront', 'Porte Passager Avant', 'info', 'string');

        // add of info : Fenetre Conducteur Avant
        $this->saveInfo('windowDriverFront', 'Fenêtre Conducteur Avant', 'info', 'string');

        // add of info : Fenetre Passager Avant
        $this->saveInfo('windowPassengerFront', 'Fenêtre Passager Avant', 'info', 'string');

        // add of info : Fenetre Conducteur Arrière
        $this->saveInfo('windowDriverRear', 'Fenêtre Conducteur Arrière', 'info', 'string');

        // add of info : Fenetre Passager Arrière
        $this->saveInfo('windowPassengerRear', 'Fenêtre Passager Arrière', 'info', 'string');

        // add of info : Coffre
        $this->saveInfo('trunk_state', 'Coffre', 'info', 'string');

        // add of info : Fenetre Passager Avant
        $this->saveInfo('beRemainingRangeFuelKm', 'Km restant (thermique)', 'info', 'string');

        // add of info : Fenetre Passager Avant
        $this->saveInfo('remaining_fuel', 'Carburant restant', 'info', 'string');

        // add of info : Latitude GPS
        $this->saveInfo('gps_lat', 'GPS Latitude', 'info', 'string');

        // add of info : Longitude GPS
        $this->saveInfo('gps_lng', 'GPS Longitude', 'info', 'string');

        // add of info : Capot Moteur
        $this->saveInfo('hood_state', 'Capot Moteur', 'info', 'string');

        // add of cmd : Rafraichir
        $this->saveInfo('refresh', 'Rafraichir', 'action', 'other');

        // add of cmd : Climatisation
        $this->saveInfo('climateNow', 'Climatiser', 'action', 'other');

        // add of cmd : Verrouillage
        $this->saveInfo('doorLock', 'Verrouiller', 'action', 'other');

        // add of cmd : Déverrouillage
        $this->saveInfo('doorUnlock', 'Déverrouiller', 'action', 'other');

        // add of cmd : Feux
        $this->saveInfo('lightFlash', 'Appel de phares', 'action', 'other');

        // add of cmd : Déverrouillage
        $this->saveInfo('hornBlow', 'Klaxonner', 'action', 'other');

        // add of cmd : MessageInfo
        $this->saveInfo('messageToBMW', 'Message à ma BMW', 'action', 'message');
    }

    private function saveInfo($commandName, $commandDescription, $type, $subType, $templateInfo = [])
    {
        $cmd = $this->getCmd(null, $commandName);
        if (!is_object($cmd)) {
            $cmd = new BMWConnectedDriveCmd();
            $cmd->setName(__($commandDescription, __FILE__));
        }
        if (!empty($templateInfo)) {
            $cmd->setTemplate($templateInfo[0], $templateInfo[1]);
        }
        $cmd->setEqLogic_id($this->getId());
        $cmd->setLogicalId($commandName);
        $cmd->setType($type);
        $cmd->setSubType($subType);
        $cmd->save();
    }
}

class BMWConnectedDriveCmd extends cmd {

    /*************** Attributs ***************/

    /************* Static methods ************/

    /**************** Methods ****************/


    public function execute($options = [])
    {
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
                    $eqLogic->doMessageToBMW($options['title'], $options['message']);
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
