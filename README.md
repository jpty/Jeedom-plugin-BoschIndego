# Jeedom-plugin-BoschIndego

## Remerciements
- A flethielleux https://www.jeedom.com/forum/memberlist.php?mode=viewprofile&u=1461 pour les scripts et le widget qui m'ont servi de base pour ce plugin.
- A zazaz-de pour la description de l'API Bosch indego disponible ici: https://github.com/zazaz-de/iot-device-bosch-indego-controller/blob/master/PROTOCOL.md

## Installation
- Depuis mon github, téléchargez un zip du plugin.
- Dans Jeedom après avoir activé les sources de type fichier pour les mises à jour, ajoutez un plugin avec le type de source Fichier. L'ID logique du plugin doit être renseigné à BoschIndego. Puis cliquez sur le bouton Envoyer un plugin et sélectionnez le zip téléchargé précédemment.
![Alt text](https://github.com/jpty/Jeedom-plugin-BoschIndego/blob/master/InstallPluginBoschIndego.PNG)
- Cliquez sur Enregistrer Le plugin est maintenant installé.
- Activez le plugin
- Dans la configuration du plugin, renseignez le nom d'utilisateur et le mot de passe de connexion au site Bosch. Sauvegardez et cliquez sur le bouton Tester connexion. Copiez le numéro de série qui apparait sous Connexion OK. Fermez la fenetre de test de connexion, et collez le numéro de série dans le champ Numéros de série et sauvegardez la configuration du plugin.
- Le plugin apparait dans le menu Plugins sous Objets connectés.
- Ajoutez un équipement, renseignez les différents champs et sélectionnez le numéro de série. Sauvegardez. L'équipement est maintenant opérationnel et doit apparaitre sur votre tableau de bord.

## Utilisation
Lancez une tonte. Un daemon de surveillance de l'avancement de la tonte se lance et s'arretera 2 minutes après le retour de la tondeuse sur la station.

