<?php
/*PhpDoc:
name: api2.php
title: api2.php - script exécuté pour l'API
doc: |
  l'URL http://urba.geoapi.fr est redirigée vers http://geoapi.fr/urba/api.php
journal: |
  12/1/2018:
    utilisation de MongoDB sur MacBook/OVH
  6/1/2018:
    création
*/
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/mongouri.inc.php';

/*
echo "api.php<br>\n";
if (isset($_SERVER['PATH_INFO']))
  echo "_SERVER[PATH_INFO]=$_SERVER[PATH_INFO]<br>\n";
else
  echo "_SERVER[PATH_INFO] indéfini<br>\n";
echo "<pre>_SERVER="; print_r($_SERVER); echo "</pre>\n";
*/

// /
// affichage de la doc soit en HTML soit en JSON
if (!isset($_SERVER['PATH_INFO']) or ($_SERVER['PATH_INFO']=='/')) {
  if (!isset($_SERVER['HTTP_ACCEPT'])) {
    echo "<pre>_SERVER="; print_r($_SERVER); echo "</pre>\n"; die();
  }
  $http_accepts = explode(',',$_SERVER['HTTP_ACCEPT']);
  //echo "<pre>http_accepts="; print_r($http_accepts); echo "</pre>\n"; die();
  foreach ($http_accepts as $http_accept)
    if (in_array($http_accept,['text/html','application/json']))
      break;
  if ($http_accept=='application/json') {
    header('Content-type: application/json; charset="utf8"');
    echo file_get_contents('https://api.swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0');
    die();
  }
  header('HTTP/1.1 301 Moved Permanently');
  header("Location: https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr/0.1.0");
  die();
}

// /terms
if (preg_match('!^/terms$!', $_SERVER['PATH_INFO'])) {
  header('Content-type: text/html; charset="utf8"');
  echo file_get_contents(__DIR__.'/terms.html');
  die();
}

$uriAutorites = "http://urba.geoapi.fr/autorites/";

// /autorites
if (preg_match('!^/autorites$!', $_SERVER['PATH_INFO'])) {
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $autorites = [];
  foreach ($baseurba->autorite->find() as $doc) {
    $doc = json_decode(json_encode($doc), true);
    $autorites[] = [
      'id'=> $doc['_id'],
      'libelle'=> $doc['libelle'],
      'nature'=> $doc['nature'],
      'departement'=> $doc['departement'],
      'communes'=> $doc['communes'],
      'uri'=> $uriAutorites.$doc['_id'],
    ];
  }  
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($autorites, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /autorites/{id}
if (preg_match('!^/autorites/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $id = $matches[1];
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $doc = $baseurba->autorite->findOne(['_id'=> $id]);
  if (!$doc) {
    header("HTTP/1.0 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucune autorité ne correspond a l'identifiant $id\n";
    die();
  }
  $autorite = [
    'id'=> $doc['_id'],
    'libelle'=> $doc['libelle'],
    'nature'=> $doc['nature'],
    'departement'=> $doc['departement'],
    'communes'=> $doc['communes'],
    'uri'=> $uriAutorites.$doc['_id'],
    'docUrba' => [],
  ];
  foreach ($baseurba->du->find(['idAutorite'=> $id]) as $doc) {
    $doc = json_decode(json_encode($doc), true);
    $autorite['docUrba'][] = [
      'idurba'=> $doc['_id'],
      'idAutorite'=> $doc['idAutorite'],
      'libelleAutorite'=> $autorite['libelle'],
      'departement'=> $autorite['departement'],
      'nature'=> $doc['nature'],
      'etat'=> 'approuvé',
      'approbation'=> $doc['approbation'],
      'communes'=> $doc['communes'],
      'uri'=> 'http://urba.geoapi.fr/docurba/'.$doc['_id'],
    ];
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($autorite, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /docurba/{idurba}
if (preg_match('!^/docurba/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idurba = $matches[1];
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $doc = $baseurba->du->findOne(['_id'=> $idurba]);
  if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucun document d'urbanisme ne correspond a l'identifiant $idurba\n";
    die();
  }
  $doc = json_decode(json_encode($doc), true);
  $autorite = $baseurba->autorite->findOne(['_id'=> $doc['idAutorite']]);
  if (!$doc) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucune autorité ne correspond a l'identifiant $doc[idAutorite]\n";
    die();
  }
  $autorite = json_decode(json_encode($autorite), true);
  $docUrba = [
    'idurba'=> $doc['_id'],
    'idAutorite'=> $doc['idAutorite'],
    'libelleAutorite'=> $autorite['libelle'],
    'departement'=> $autorite['departement'],
    'nature'=> $doc['nature'],
    'etat'=> 'approuvé',
    'approbation'=> $doc['approbation'],
    'communes'=> $doc['communes'],
    'uri'=> 'http://urba.geoapi.fr/docurba/'.$doc['_id'],
    'piecesEcrites'=> [],
  ];
  foreach ($doc['piecesEcrites'] as $pieceEcrite) {
    $docUrba['piecesEcrites'][] = "http://urba.geoapi.fr/docurba/$doc[_id]/Pieces_ecrites/$pieceEcrite[0]";
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($docUrba, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /docurba/{idurba}/Pieces_ecrites/{path}
if (preg_match('!^/docurba/([^/]+)/Pieces_ecrites/(.+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idurba = $matches[1];
  $shortpath = $matches[2];
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $doc = $baseurba->du->findOne(['_id'=> $idurba]);
  if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucun document d'urbanisme ne correspond a l'identifiant $idurba\n";
    die();
  }
  $doc = json_decode(json_encode($doc), true);
  $longpath = null;
  foreach ($doc['piecesEcrites'] as $pieceEcrite) {
    if ($pieceEcrite[0]==$shortpath)
      $longpath = $pieceEcrite[1];
  }
  if (!$longpath) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucune pièce écrite ne correspond à $shortpath\n";
    die();
  }
  header('Content-type: application/pdf; charset="utf8"');
  $pathinzip = "zip://build/zips/$idurba.zip#$longpath";
  /*$fzip = fopen($pathinzip, 'r');
  if ($fzip === FALSE)
    die("Erreur d'ouverture de $pathinzip");
  while ($buff = fread($fzip, 4*1024))
    echo $buff;
  fclose($fzip);*/
  if (readfile($pathinzip) === FALSE) {
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur d'ouverture de $pathinzip");
  }
  die();
}

// /docurba/{idurba}/{classeCnig}
if (preg_match('!^/docurba/([^/]+)/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idurba = $matches[1];
  if (!in_array(strtoupper($matches[2]), [
     'ZONE_URBA', 'SECTEUR_CC', 'PRESCRIPTION_PCT', 'PRESCRIPTION_LIN', 'PRESCRIPTION_SURF',
     'INFO_PCT', 'INFO_LIN', 'INFO_SURF', 'HABILLAGE_PCT', 'HABILLAGE_LIN', 'HABILLAGE_SURF', 'HABILLAGE_TXT'
  ])) {
    header("HTTP/1.1 400 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: la classe $matches[2] est inconnue\n";
    die();
  }
  $classeCnig = strtolower($matches[2]);
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $first = true;
  foreach ($baseurba->$classeCnig->find(['IDURBA'=> $idurba]) as $doc) {
    unset($doc['_id']);
    unset($doc['IDURBA']);
    if ($first) {
      header('Content-type: application/json; charset="utf8"');
      echo "[\n";
      $first = false;
    }
    else
      echo ",\n";
    echo json_encode($doc, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  }
  if ($first) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucun objet géographique ne correspond à l'identifiant $idurba et à la classe $classeCnig\n";
    die();
  } else {
    echo "\n]\n";
    die();
  }
}

header('HTTP/1.1 400 Bad Request');
echo "Unknown query\n";
die();
