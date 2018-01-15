<?php
/*PhpDoc:
name: api2.php
title: api2.php - script exécuté pour l'API
doc: |
  Ce script correspond à la définition https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr
  l'URL http://urba.geoapi.fr est redirigée vers http://vps496729.ovh.net/urba/api2.php
journal: |
  13/1/2018:
    ajout des points d'entrée /sup
  13/1/2018:
    ajout des filtres sur /autorites
  12/1/2018:
    utilisation de MongoDB sur MacBook/OVH
  6/1/2018:
    création
*/
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/mongouri.inc.php';
require_once __DIR__.'/../../spyc/spyc2.inc.php';

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
    echo file_get_contents('https://api.swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr');
    die();
  }
  header('HTTP/1.1 307 Temporary Redirect');
  header("Location: https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr");
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
  $departement = (isset($_GET['departement']) ? $_GET['departement'] : null);
  $epci = (isset($_GET['epci']) ? $_GET['epci'] : null);
  $commune = (isset($_GET['commune']) ? $_GET['commune'] : null);
  
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $autorites = [];
  foreach ($baseurba->autorite->find() as $doc) {
    $doc = json_decode(json_encode($doc), true);
    if ($departement and ($doc['departement']<>$departement))
      continue;
    if ($epci and ($doc['_id']<>$epci))
      continue;
    if ($commune and !in_array($commune, $doc['communes']))
      continue;
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
    header("HTTP/1.1 404 Not Found");
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

// /docurba/{idurba}/metadata
if (preg_match('!^/docurba/([^/]+)/metadata$!', $_SERVER['PATH_INFO'], $matches)) {
  header("HTTP/1.1 501 Not Implemented");
  header('Content-type: text/plain; charset="utf8"');
  echo "Erreur: fonctionnalité non implémentée\n";
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
  $ext = substr($bname, strrpos($shortpath, '.')+1);
  $ext = strtolower($ext);
  $mimeTypes = [
    'txt'=> 'text/plain',
    'pdf'=> 'application/pdf',
    'jpeg'=> 'image/jpeg',
    'jpg'=> 'image/jpeg',
    'doc'=> 'application/msword',
    'docx'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'odt'=> 'application/vnd.oasis.opendocument.text',
    'ods'=> 'application/vnd.oasis.opendocument.spreadsheet',
  ];
  $mimeType = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : $mimeTypes['txt'];
  header("Content-type: $mimeType; charset=\"utf8\"");
  $pathinzip = "zip://build/zips/$idurba.zip#$longpath";
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


// /codesup
if (preg_match('!^/codesup$!', $_SERVER['PATH_INFO'])) {
  $yaml = spycLoad(__DIR__.'/supcat.yaml');
  if (!$yaml) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: fichier supcat.yaml non trouvé\n";
    die();
  }
  $supcats = [];
  foreach ($yaml['contents'] as $codeSup => $content) {
    $supcat = [
      'codeSup'=> $codeSup,
      'libelleSup'=> $content['libelle'],
    ];
    foreach(['decoupage','urlFiche'] as $cle)
      if (isset($content[$cle]))
        $supcat[$cle] = $content[$cle];
    $supcats[] = $supcat;
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($supcats, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /jdsup
if (preg_match('!^/jdsup$!', $_SERVER['PATH_INFO'], $matches)) {
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $codeSups = [];
  foreach ($baseurba->sup->find() as $sup) {
    $sup = json_decode(json_encode($sup), true);
    $codeSups[$sup['codeSup']] = 1;
  }
  $yaml = spycLoad(__DIR__.'/supcat.yaml');
  if (!$yaml) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur: fichier supcat.yaml non trouvé\n");
  }
  $result = [];
  foreach (array_keys($codeSups) as $codeSup) {
    $libelleSup = isset($yaml['contents'][$codeSup]['libelle']) ? $yaml['contents'][$codeSup]['libelle'] : "inconnu";
    $result[] = ['codeSup'=> $codeSup, 'libelleSup'=> $libelleSup ];
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /jdsup/{codeSup}
// Retourne les territoires pour lesquels au moins un jeu de données est exposé pour la catégorie de SUP fournie
if (preg_match('!^/jdsup/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $codeSup = $matches[1];
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $codeTerritoires = [];
  foreach ($baseurba->sup->find(['codeSup'=> $codeSup]) as $sup) {
    $sup = json_decode(json_encode($sup), true);
    $codeTerritoires[$sup['codeTerritoire']] = 1;
  }
  $result = [];
  foreach (array_keys($codeTerritoires) as $codeTerritoire) {
    $result[] = ['codeTerritoire'=> (string)$codeTerritoire];
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /jdsup/{codeSup}/{codeTerritoire}
// Retourne les jeux de données exposés pour une catégorie de SUP et un territoire
if (preg_match('!^/jdsup/([^/]+)/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $codeSup = $matches[1];
  $codeTerritoire = $matches[2];
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $dateRefs = [];
  foreach ($baseurba->sup->find(['codeSup'=> $codeSup, 'codeTerritoire'=> $codeTerritoire]) as $sup) {
    $sup = json_decode(json_encode($sup), true);
    $dateRefs[$sup['dateref']] = 1;
  }
  $result = [];
  foreach (array_keys($dateRefs) as $dateRef) {
    $dateRef = (string)$dateRef;
    $result[] = [
      'codeSup'=> $codeSup,
      'codeTerritoire'=> $codeTerritoire,
      'dateRef'=> $dateRef,
    ];
  }
  if (!$result) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Aucun JD de SUP ne correspond à la catégorie $codeSup et au territoire $codeTerritoire\n";
    die();
  }
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /jdsup/{codeSup}/{codeTerritoire}/{dateRef}
if (preg_match('!^/jdsup/([^/]+)/([^/]+)/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $codeSup = $matches[1];
  $codeTerritoire = $matches[2];
  $dateRef = $matches[3];
  $idsup = "${codeSup}_${codeTerritoire}_${dateRef}";
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $sup = $baseurba->sup->findOne(['_id'=> $idsup]);
  $sup = json_decode(json_encode($sup), true);
  if (!$sup) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Aucun JD de SUP ne correspond à l'identifiant $idsup\n";
    die();
  }
  $result = [
    'codeSup'=> $sup['codeSup'],
    'codeTerritoire'=> $sup['codeTerritoire'],
    'dateRef'=> $sup['dateref'],
    'uri'=> "http://urba.geoapi.fr/jdsup/$codeSup/$codeTerritoire/$dateRef",
    'actes'=> [],
  ];
  foreach ($sup['actes'] as $acte) {
    $result['actes'][] = "http://urba.geoapi.fr/jdsup/$codeSup/$codeTerritoire/$dateRef/Actes/$acte[0]";
  }
  //echo "<pre>"; print_r($sup); print_r($result);
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  die();
}

// /jdsup/{codeSup}/{codeTerritoire}/{dateRef}/metadata
if (preg_match('!^/jdsup/([^/]+)/([^/]+)/([^/]+)/metadata$!', $_SERVER['PATH_INFO'], $matches)) {
  header("HTTP/1.1 501 Not Implemented");
  header('Content-type: text/plain; charset="utf8"');
  echo "Erreur: fonctionnalité non implémentée\n";
  die();
}

// /jdsup/{codeSup}/{codeTerritoire}/{dateRef}/actes/{path}
// Retourne en PDF un des actes associés à une SUP
if (preg_match('!^/jdsup/([^/]+)/([^/]+)/([^/]+)/Actes/(.+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $codeSup = $matches[1];
  $codeTerritoire = $matches[2];
  $dateRef = $matches[3];
  $shortpath = $matches[4];
  $idsup = "${codeSup}_${codeTerritoire}_${dateRef}";
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $doc = $baseurba->sup->findOne(['_id'=> $idsup]);
  if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    echo "Aucun JD de SUP ne correspond à l'identifiant $idsup\n";
    die();
  }
  $doc = json_decode(json_encode($doc), true);
  $longpath = null;
  foreach ($doc['actes'] as $acte) {
    if ($acte[0]==$shortpath)
      $longpath = $acte[1];
  }
  if (!$longpath) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucun acte ne correspond à $shortpath\n";
    die();
  }
  header('Content-type: application/pdf; charset="utf8"');
  $pathinzip = "zip://build/zips/$doc[zipname]#$longpath";
  if (readfile($pathinzip) === FALSE) {
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur d'ouverture de $pathinzip");
  }
  die();
}

// /jdsup/{codeSup}/{codeTerritoire}/{dateRef}/{classeCnig}
// Retourne les objets géographiques {classeCnig} correspondants à une catégorie de SUP, un territoire et une version
if (preg_match('!^/jdsup/([^/]+)/([^/]+)/([^/]+)/((ASSIETTE|GENERATEUR)[^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $codeSup = $matches[1];
  $codeTerritoire = $matches[2];
  $dateRef = $matches[3];
  $classeCnig = $matches[4];
  $collection = 'sup_'.strtolower($classeCnig);
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $idsup = "${codeSup}_${codeTerritoire}_${dateRef}";
  $first = true;
  foreach ($baseurba->$collection->find(['IDSUP'=> $idsup]) as $doc) {
    unset($doc['_id']);
    unset($doc['IDSUP']);
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
    echo "Erreur: aucun objet géographique ne correspond à la classe $classeCnig du JD de SUP $idsup\n";
    die();
  } else {
    echo "\n]\n";
    die();
  }
}




header('HTTP/1.1 400 Bad Request');
echo "Unknown query\n";
die();
