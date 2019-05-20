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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$BoschIndego = new BoschIndego();
$username = $BoschIndego->getConfiguration('username');
$username = config::byKey('username', 'BoschIndego');
$password = config::byKey('password', 'BoschIndego');
// echo "Username: $username<br/>";
// echo "Password: $password<br/>";
$BoschIndego->initParams($params,$username,$password,0);

$curlHttpCode = $BoschIndego->authenticate($params);
if ( $curlHttpCode == 200 ) {
echo '<div style="width: 100%; padding: 7px 35px 7px 15px; margin-bottom: 5px; max-height: 787px; z-index: 9999;" id="div_alert" class="alert jqAlert alert-success"><span class="displayError">Connexion OK</span></div>';
  // echo "ContextId = ".$params['contextId'] ."<br/>";
  // echo "UserId = " .$params['userId'] ."<br/>";
  echo "Numéro de série = " .$params['almSn'] ."<br/>";
  // echo '<h4>Contenu du fichier "'.__DIR__ .'/../../core/class/indego_dataTokenAuthenticate.json" </h4>';
  // echo file_get_contents(__DIR__ ."/../../core/class/indego_dataTokenAuthenticate.json");
  return($params['almSn']);
}
else {
echo '<div style="width: 100%; padding: 7px 35px 7px 15px; margin-bottom: 5px; max-height: 787px; z-index: 9999;" id="div_alert" class="alert jqAlert alert-danger"><span class="displayError">Echec de la connexion au site ['.$params['api'] .'authenticate].<br/>Vérifiez le nom d\'utilisateur et le mot de passe.<br/> HTTP_CODE: ' .$curlHttpCode .'</span></div>';
}
