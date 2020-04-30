---
layout: default
lang: fr_FR
---

Description
===

Plugin permettant de récupérer les informations des voitures BMW équipées des services ConnectedDrive.

Voitures testées :
-I3

Si vous êtes propriétaires d'un autre véhicule et souhaitez tester le fonctionnement, n'hésitez pas à me contacter.

Vous pouvez retrouver d'autres tutoriels sur mon blog : [www.ma-maison-intelligente.fr](http://www.ma-maison-intelligente.fr)

Installation
===

#Installation avec Github
Depuis mon [GitHub](https://github.com/flabadens/BMWConnectedDrive), cliquer sur cloner/téléchargez puis sélectionner le fichier .zip.
Dans Jeedom, il vous faudra peut être activer les sources de type fichier dans la configuration. Vous pourrez ensuite ajoutez un plugin avec le type de source Fichier. L’ID logique du plugin doit être renseigné exactement BMWConnecteDrive. Puis cliquez sur le bouton "Envoyer un plugin" et sélectionnez le zip téléchargé précédemment.
Cliquez sur "Enregistrer". Le plugin est maintenant installé.


Configuration
===

Après installation du plugin, il vous faudra l'activer.
Il apparaitra ensuite dans le menu Plugins > Objets connectés.

Vous pourrez ensuite ajouter un véhicule.
Il vous faudra renseigner 3 paramètres particuliers nécessairesà la connection avec le service BMW ConnectedDrive:
- VIN (Vehicle Identification Number) : Les 7 derniers caractères du numéro d'identification sont disponibles sur le site BMW ConnectedDrive. Le numéro complet que vous aurez besoin se trouve sur le certificat d'immatriculation (carte grise, champ E).
- Username : identifiant pour accéder au site BMW ConnectedDrive
- Password : Mot de passe pour accéder au site BMW ConnectedDrive
Vous pouvez une fois l'équipement sauvegardé, tester la connexion avec BMW ConnectedDrive. Vous aurez un retour brut des données potentiellement récpérable que nous pourrons utiliser pour tester les véhicules BMW.

Commandes disponibles pour le moment
===

# Informations #
## Global ##
- Verrouillage (doorLockState)
- Eclairage (lightsParking)
- Kilométrage (mileage)
- Unité de distance (unitOfLength)
- Porte Conducteur Arrière (doorDriverRear)
- Porte Passager Arrière (doorPassengerRear)
- Porte Conducteur Avant (doorDriverFront)
- Porte Passager Avant (doorPassengerFront)
- Fenêtre Conducteur Avant (windowDriverFront)
- Fenêtre Passager Avant (windowPassengerFront)
- Dernière mise à jour (lastUpdate)

## Electrique / Hybride ##
- Etat de la charge (chargingStatus)
- Etat de la prise (connectorStatus)
- Km restant (électrique) (beRemainingRangeElectric)
- Pourcentage restant (chargingLevelHv)

## Thermique ##
- Km restant (thermique) (beRemainingRangeFuelKm)
- Carburant restant (remaining_fuel)

# Commandes #
- Rafraichir (refresh)

Fonctionnalités à venir
===
- Ajout d'un widget
- Ajout des messages
- Ajout des mesures d'Efficience
- Ajout d'intéraction avec la voiture


Changelog
===

Support
===
N'hésitez pas à me contacter sur mon blog [www.ma-maison-intelligente.fr](http://www.ma-maison-intelligente.fr) ou sur [ma page facebook](https://www.facebook.com/mamaisonintelligentefr/).
