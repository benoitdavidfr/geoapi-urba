<?php
/*PhpDoc:
name: mongouri.inc.php
title: mongouri.inc.php - définition de la variable mongouri utilisée pour la connexion à MongoDB
doc: |
  cette variable dépend du serveur sur lequel s'exécute le script:
   - sur localhost, les scripts sont exécutés dans un container Docker et mongouri référence le serveur
     dans un autre container exposé en 172.17.0.2
   - sur un serveur sur internet, le serveur doit être protégé et l'URI contient le login/mot de passe à utiliser
journal: |
  13/1/2017:
    transfert dans mongouri_secret.inc.php des URI avec login/mot de passe
*/
// Sur le Mac: adresse locale sans login
$mongouri = 'mongodb://172.17.0.2:27017';

// Sur internet, l'URI contient un login/mdp stockés dans un fichier mongouri_secret.inc.php dans le meme repertoire
// Si ce fichier existe il contient la définition de $mongouri avec le login/mot de passe
if (is_file(__DIR__.'/mongouri_secret.inc.php'))
  require_once __DIR__.'/mongouri_secret.inc.php';
