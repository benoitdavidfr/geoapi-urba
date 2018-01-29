<?php
/*PhpDoc:
name: mkgest.php
title: mkgest.php - génération des gettionnaires dans la base urba à partir de SirAdmin
doc: |
journal: |
  28/1/2018:
    première version
*/
require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../mongouri.inc.php';
require_once __DIR__.'/../../siradmin/inc.php';

$mgdbclient = new MongoDB\Client($mongouri);
$baseurba = $mgdbclient->urba;
$baseurba->gestionnaire->drop();
$siradmin = new Siradmin($mongouri);

$gestionnaires = [];
foreach ($baseurba->jdsup->find() as $doc) {
  $doc = json_decode(json_encode($doc), true);
  if (!isset($gestionnaires[$doc['idGest']]))
    $gestionnaires[$doc['idGest']] = ['_id'=> $doc['idGest']];
}
echo count($gestionnaires)," gestionnaires déduits des JDSUP\n";
//print_r($gestionnaires);

foreach ($gestionnaires as $idGest => $gestionnaire) {
  try {
    $admin = $siradmin->adminsParSiren($idGest);
    $admin = json_decode(json_encode($admin), true);
    //echo "admin = "; print_r($admin);
    $depcom = $admin['infoSiege']['DEPCOMEN'];
    $dep = substr($depcom, 0, 2);
    if ($dep == '97')
      $dep = substr($depcom, 0, 3);
    if (!isset($admin['idEntreprise']['SIGLE'])) {
      $gestionnaire['libelle'] = $admin['idEntreprise']['NOMEN_LONG'];
      $gestionnaire['nature'] = $admin['carEntreprise']['LIBAPEN'];
    }
    elseif (in_array($admin['idEntreprise']['SIGLE'] ,['DDT','DDTM']))  {
      $gestionnaire['libelle'] = $admin['idEntreprise']['SIGLE']." $dep";
      $gestionnaire['nature'] = 'DDT(M)';
    }
    elseif (in_array($admin['idEntreprise']['SIGLE'] ,['DEAL','DREAL','DRAC'])) {
      $gestionnaire['libelle'] = $admin['idEntreprise']['SIGLE'].' '.$admin['loc']['LIBREG'];
      $gestionnaire['nature'] = $admin['idEntreprise']['SIGLE'];
    }
    else {
      echo "admin = "; print_r($admin);
      die("nature de gestionnaire ".$admin['idEntreprise']['SIGLE']." non prévue\n");
    }
    $gestionnaire['departement'] = $dep;
    $baseurba->gestionnaire->insertOne($gestionnaire);
  }
  catch (Exception $e) {
    echo $e->getMessage(),"\n";
  }
}
echo "Fin normale de mkgest.php\n";