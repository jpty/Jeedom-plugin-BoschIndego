# Jeedom-plugin-BoschIndego

## Remerciements
- A [**fle**](www.jeedom.com/forum/memberlist.php?mode=viewprofile&u=1461) pour les scripts et le widget qui m'ont servi de base pour ce plugin.
- A zazaz-de pour la description de l'API Bosch indego disponible [**ici**](github.com/zazaz-de/iot-device-bosch-indego-controller/blob/master/PROTOCOL.md)

## Installation
- Depuis mon github, téléchargez un zip du plugin.
- Dans Jeedom après avoir activé les sources de type fichier pour les mises à jour, ajoutez un plugin avec le type de source Fichier. L'ID logique du plugin doit être renseigné à BoschIndego. Puis cliquez sur le bouton Envoyer un plugin et sélectionnez le zip téléchargé précédemment.
![Alt text](https://github.com/jpty/Jeedom-plugin-BoschIndego/blob/master/InstallPluginBoschIndego.PNG)
- Cliquez sur Enregistrer Le plugin est maintenant installé.
- Activez le plugin
- Dans la configuration du plugin, renseignez le nom d'utilisateur et le mot de passe de connexion au site Bosch. Sauvegardez et cliquez sur le bouton Tester connexion. Copiez le numéro de série qui apparait sous Connexion OK. Fermez la fenêtre de test de connexion, et collez le numéro de série dans le champ Numéros de série et sauvegardez la configuration du plugin.
- Le plugin apparait dans le menu Plugins sous Objets connectés.
- Ajoutez un équipement, renseignez les différents champs et sélectionnez le numéro de série. Sauvegardez. L'équipement est maintenant opérationnel et doit apparaitre sur votre tableau de bord. Cliquez sur l'icone en haut à droite de la tuile pour provoquer une mise à jour de la tuile. Redimensionnez la tuile.

## Utilisation
Lors de l'exécution d'une tonte, un daemon de surveillance de l'avancement de la tonte se lance et s'arrêtera 2 minutes après le retour de la tondeuse sur la station.

Les actions créées sont mow, pause, returntodock, refresh, crfonSetEnableOn et cronSetEnableOff. Les 2 dernières actions sont pour le daemon de surveillance de la tonte.

## Limitation
La version actuelle ne gère que le mode de tonte Manuel. Il faut provoquer l'action de Tonte avec un scénario, le plugin Agenda ou autre.
