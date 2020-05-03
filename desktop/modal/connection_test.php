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

include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}


log::add('BMWConnectedDrive', 'debug', 'Test connection ' . $_GET["eqLogicId"]);

$plugin = plugin::byId('BMWConnectedDrive');
sendVarToJS('eqType', $plugin->getId());
$eqLogic = eqLogic::byId($_GET["eqLogicId"]);

echo '<h1>GetInfo</h1>';
echo '<pre>';
var_dump($eqLogic->refreshCarInfos());
echo '</pre>';
echo '<hr/>';
echo '<h1>GetNavigationInfo</h1>';
echo '<pre>';
var_dump($eqLogic->refreshCarNavigationInfo());
echo '</pre>';
echo '<hr/>';
echo '<h1>GetEfficiency</h1>';
echo '<pre>';
var_dump($eqLogic->refreshCarEfficiency());
echo '</pre>';
?>
