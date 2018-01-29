<?php
/*PhpDoc:
name: mkaut.php
title: mkaut.php - génération des autorités dans la base urba à partir d'Admin-Express
doc: |
  Nécessite le chargement préalable de la base MongoDB AdminExpress, voir ~/html/admingeo/adminexpress
  1295 autorités déduites des DU
  48 communes absentes d'Admin-Express 2017-12
  EPCI non traités à ce stade
  1238 autorités créées
journal: |
  12/1/2018:
    première version aboutie: 1238 autorités créées
  11/1/2018:
    première version
*/
require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../mongouri.inc.php';

$mgdbclient = new MongoDB\Client($mongouri);
$baseurba = $mgdbclient->urba;
$baseurba->autorite->drop();
$adminexp = $mgdbclient->adminexp;

$autorites = [];
foreach ($baseurba->du->find() as $doc) {
  $doc = json_decode(json_encode($doc), true);
  if (!isset($autorites[$doc['idAutorite']]))
    $autorites[$doc['idAutorite']] = ['_id'=> $doc['idAutorite']];
}
echo count($autorites)," autorités déduites des DU\n";
//print_r($autorites);
foreach ($autorites as $idAutorite => $autorite) {
  if (strlen($idAutorite)==5) {
    $doc = $adminexp->c_gjs->findOne(['_id'=> 'C'.$idAutorite]);
    if (!$doc) {
      echo "Erreur: $idAutorite inconnu dans c_gjs\n";
      continue;
    }
    //print_r($doc->properties);
    $autorite['libelle'] = $doc->properties->NOM_COM;
    $autorite['nature'] = 'commune';
    $autorite['departement'] = $doc->properties->INSEE_DEP;
    $autorite['communes'] = [ $idAutorite ];
    $baseurba->autorite->insertOne($autorite);
  }
  /*elseif (strlen($idAutorite)==9) {
    $doc = $adminexp->e_gjs->findOne(['_id'=> 'E'.$idAutorite]);
    if (!$doc) {
      echo "Erreur: $idAutorite inconnu dans e_gjs\n";
      continue;
    }
    print_r($doc->properties);
    $autorite['libelle'] = $doc->properties->NOM_EPCI;
    $autorite['nature'] = 'EPCI';
    $baseurba->autorite->insertOne($autorite);
  }
  else {
    echo "Erreur: $idAutorite inconnu dans c_gjs et e_gjs\n";
    echo "len=",strlen($idAutorite),"\n";
  }*/
}
