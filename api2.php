<?php
/*PhpDoc:
name: api2.php
title: api2.php - script exécuté pour l'API
doc: |
  Ce script correspond à la définition https://swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr
  l'URL http://urba.geoapi.fr est redirigée vers http://vps496729.ovh.net/urba/api2.php
journal: |
  28-29/1/2018:
    modif du chemin du répertoire des zips
    réécriture de la partie JDSUP pour alignement sur les versions 0.4 et 0.5 des specs de l'API
  27/1/2018:
    modif de la structure des pièces écrites
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

// identifie le type MIME demandé parmi ceux transmis
function http_accept(array $mimeTypes): ?string {
  if (!isset($_SERVER['HTTP_ACCEPT']))
    return null;
  $http_accepts = explode(',',$_SERVER['HTTP_ACCEPT']);
  //echo "<pre>http_accepts="; print_r($http_accepts); echo "</pre>\n"; die();
  foreach ($http_accepts as $http_accept)
    if (in_array($http_accept, $mimeTypes))
      return $http_accept;
  return null;
}

function ahref(string $url): string {
  return "<a href='$url'>$url</a>";
}

$info = [
  'title'=> "API d'accès aux documents d'urbanisme et aux servitudes d'utilité publique français",
  'docurl'=> 'http://urba.geoapi.fr/doc',
  'swaggerurl'=> 'http://urba.geoapi.fr/spec',
];

// /
// affichage d'une description succincte soit en HTML soit en JSON
if (!isset($_SERVER['PATH_INFO']) or ($_SERVER['PATH_INFO']=='/')) {
  $http_accept = http_accept(['text/html','application/json']);
  if ($http_accept=='application/json') {
    header('Content-type: application/json; charset="utf8"');
    $descr = [
      'title'=> $info['title'],
      'doc'=> "Documentation et code source disponibles sur $info[docurl]",
      'swagger'=> "Spécification formelle Swagger disponible sur $info[swaggerurl]",
    ];
    echo json_encode($descr, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    die();
  }
  header('Content-type: text/html; charset="utf8"');
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>urba.geoapi.fr</title></head><body>\n",
       "<h2>$info[title]</h2>\n",
       "Documentation et code source disponibles sur ",ahref($info['docurl']),"<br>\n",
       "Spécification formelle Swagger disponible sur ",ahref($info['swaggerurl']),"<br>\n";
  die();
}

// /doc
// affichage de la doc en HTML
if (preg_match('!^/doc$!', $_SERVER['PATH_INFO'])) {
  header('HTTP/1.1 307 Temporary Redirect');
  header("Location: https://github.com/benoitdavidfr/geoapi-urba");
  die();
}

// /spec
// affichage de la spec Swagger soit en HTML soit en JSON
if (preg_match('!^/spec$!', $_SERVER['PATH_INFO'])) {
  $http_accept = http_accept(['text/html','application/json']);
  if ($http_accept=='application/json') {
    header('Content-type: application/json; charset="utf8"');
    die(file_get_contents('https://api.swaggerhub.com/apis/benoitdavidfr/urba.geoapi.fr'));
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
    $docUrba['piecesEcrites'][] = [
      'url'=> "http://urba.geoapi.fr/docurba/$doc[_id]/Pieces_ecrites/$pieceEcrite[cheminCourt]",
      'taille'=> $pieceEcrite['taille'],
    ];
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
    if ($pieceEcrite['cheminCourt']==$shortpath)
      $longpath = $pieceEcrite['cheminLong'];
  }
  if (!$longpath) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: aucune pièce écrite ne correspond à $shortpath\n";
    die();
  }
  $ext = substr($shortpath, strrpos($shortpath, '.')+1);
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
  $pathinzip = "zip://../../data/gpuzips/$idurba.zip#$longpath";
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

// JDSUP

// /gestionnaires
if (preg_match('!^/gestionnaires$!', $_SERVER['PATH_INFO'])) {
  $departement = (isset($_GET['departement']) ? $_GET['departement'] : null);
  $epci = (isset($_GET['epci']) ? $_GET['epci'] : null);
  $commune = (isset($_GET['commune']) ? $_GET['commune'] : null);
  
  $mgdbclient = new MongoDB\Client($mongouri);
  $baseurba = $mgdbclient->urba;
  $gestionnaires = [];
  foreach ($baseurba->gestionnaire->find() as $doc) {
    $doc = json_decode(json_encode($doc), true);
    if ($departement and ($doc['departement']<>$departement))
      continue;
    if ($epci and ($doc['_id']<>$epci))
      continue;
    if ($commune and !in_array($commune, $doc['communes']))
      continue;
    $gestionnaires[] = [
      'id'=> $doc['_id'],
      'libelle'=> $doc['libelle'],
      'nature'=> $doc['nature'],
      'etat'=> 'actuel',
      'departement'=> $doc['departement'],
      'uri'=> "http://siradmin.geoapi.fr/admins/$doc[_id]",
    ];
  }  
  header('Content-type: application/json; charset="utf8"');
  die(json_encode($gestionnaires, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// /categoriesSup
if (preg_match('!^/categoriesSup$!', $_SERVER['PATH_INFO'])) {
  $yaml = spycLoad(__DIR__.'/supcat.yaml');
  if (!$yaml) {
    header("HTTP/1.1 500 Internal Server Error");
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur: fichier supcat.yaml non trouvé\n");
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
  die(json_encode($supcats, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// /territoires
if (preg_match('!^/territoires$!', $_SERVER['PATH_INFO'])) {
  foreach(['deptmetro','dom','regionmetro'] as $key) {
    $yamls[$key] = spycLoad(__DIR__."/../../georef/admin/$key.yaml");
    if (!$yamls[$key]) {
      header("HTTP/1.1 500 Internal Server Error");
      header('Content-type: text/plain; charset="utf8"');
      die("Erreur: fichier $key.yaml non trouvé\n");
    }
  }
  //echo "<pre>"; print_r($yamls); die();
  $departements = [];
  foreach ($yamls['deptmetro']['data'] as $code => $doc) {
    $departements[] = [
      'code'=> $code,
      'libelle'=> $doc['title'],
      'uri'=> "http://id.insee.fr/geo/departement/$code",
    ];
  }
  foreach ($yamls['dom']['data'] as $code => $doc) {
    $departements[] = [
      'code'=> $code,
      'libelle'=> $doc['title'],
      'uri'=> "http://id.insee.fr/geo/departement/$code",
    ];
  }
  $regions = [];
  foreach ($yamls['regionmetro']['data'] as $code => $doc) {
    $regions[] = [
      'code'=> "R$code",
      'libelle'=> $doc['title'],
      'uri'=> "http://id.insee.fr/geo/region/$code",
    ];
  }
  foreach ($yamls['dom']['data'] as $doc) {
    $code = substr($doc['région'], 1);
    $regions[] = [
      'code'=> $doc['région'],
      'libelle'=> $doc['title'],
      'uri'=> "http://id.insee.fr/geo/region/$code",
    ];
  }
  $territoires = [
    [ 'decoupage'=> 'région',
      'libelle'=> "découpage en régions",
      'territoires'=> $regions,
    ],
    [ 'decoupage'=> 'département',
      'libelle'=> "découpage en départements",
      'territoires'=> $departements,
    ],
  ];
  header('Content-type: application/json; charset="utf8"');
  die(json_encode($territoires, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// /jdsup
if (preg_match('!^/jdsup$!', $_SERVER['PATH_INFO'], $matches)) {
  $query = [];
  if (isset($_GET['idGest']))
    $query['idGest'] = new MongoDB\BSON\Regex($_GET['idGest'], 'i');
  if (isset($_GET['codeSup']))
    $query['codeSup'] = new MongoDB\BSON\Regex($_GET['codeSup'], 'i');
  if (isset($_GET['codeTerritoire']))
    $query['codeTerritoire'] = new MongoDB\BSON\Regex($_GET['codeTerritoire'], 'i');
  $mgdbclient = new MongoDB\Client($mongouri);
  $first = true;
  foreach ($mgdbclient->urba->jdsup->find($query) as $doc) {
    $doc = json_decode(json_encode($doc), true);
    $jdsup = [
      'idjdsup'=> $doc['_id'],
      'idGest'=> $doc['idGest'],
      'codeSup'=> $doc['codeSup'],
      'codeTerritoire'=> $doc['codeTerritoire'],
      'dateRef'=> $doc['dateref'],
      'uri'=> "http://urba.geoapi.fr/jdsup/$doc[_id]",
    ];
    if ($first) {
      header('Content-type: application/json; charset="utf8"');
      echo "[\n";
      $first = false;
    }
    else
      echo ",\n";
    echo json_encode($jdsup, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  }
  if (!$first) {
    die("]\n");
  }
  else {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    die("Aucun jeu de données de SUP ne correspond à la requête '$_SERVER[QUERY_STRING]'\n");
  }
}

// /jdsup/{idJdSup}
if (preg_match('!^/jdsup/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idjdsup = $matches[1];
  $mgdbclient = new MongoDB\Client($mongouri);
  $doc = $mgdbclient->urba->jdsup->findOne(['_id'=> $idjdsup]);
  if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    die("Aucun jeu de données de SUP ne correspond à l'identifiant '$idjdsup'\n");
  }
  $doc = json_decode(json_encode($doc), true);
  $jdsup = [
    'idjdsup'=> $doc['_id'],
    'idGest'=> $doc['idGest'],
    'codeSup'=> $doc['codeSup'],
    'codeTerritoire'=> $doc['codeTerritoire'],
    'dateRef'=> $doc['dateref'],
    'uri'=> "http://urba.geoapi.fr/jdsup/$doc[_id]",
    'actes'=> [],
  ];
  foreach ($doc['actes'] as $acte) {
    $jdsup['actes'][] = [
      'url'=> "http://urba.geoapi.fr/jdsup/$doc[_id]/Actes/$acte[cheminCourt]",
      'taille'=> $acte['taille'],
    ];
  }
  header('Content-type: application/json; charset="utf8"');
  die(json_encode($jdsup, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

// /jdsup/{idJdSup}/metadata
if (preg_match('!^/jdsup/([^/]+)/metadata$!', $_SERVER['PATH_INFO'], $matches)) {
  header("HTTP/1.1 501 Not Implemented");
  header('Content-type: text/plain; charset="utf8"');
  echo "Erreur: fonctionnalité non implémentée\n";
  die();
}

// /jdsup/{idJdSup}/Actes/{sschemin}
if (preg_match('!^/jdsup/([^/]+)/Actes/(.+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idjdsup = $matches[1];
  $shortpath = $matches[2];
  $mgdbclient = new MongoDB\Client($mongouri);
  $doc = $mgdbclient->urba->jdsup->findOne(['_id'=> $idjdsup]);
  if (!$doc) {
    header("HTTP/1.1 404 Not Found");
    header('Content-type: text/plain; charset="utf8"');
    die("Aucun jeu de données de SUP ne correspond à l'identifiant '$idjdsup'\n");
  }
  $doc = json_decode(json_encode($doc), true);
  $longpath = null;
  foreach ($doc['actes'] as $acte) {
    if ($acte['cheminCourt']==$shortpath)
      $longpath = $acte['cheminLong'];
  }
  if (!$longpath) {
    header("HTTP/1.1 404 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur: aucune acte ne correspond à $shortpath\n");
  }
  $ext = substr($shortpath, strrpos($shortpath, '.')+1);
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
  $pathinzip = "zip://../../data/gpuzips/$idjdsup.zip#$longpath";
  if (readfile($pathinzip) === FALSE) {
    header('Content-type: text/plain; charset="utf8"');
    die("Erreur d'ouverture de $pathinzip");
  }
  die();
}

// /jdsup/{idJdSup}/{classeCnig}
if (preg_match('!^/jdsup/([^/]+)/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $idjdsup = $matches[1];
  if (!in_array(strtoupper($matches[2]), [
     'ASSIETTE_SUP_S', 'ASSIETTE_SUP_L', 'ASSIETTE_SUP_P', 'GENERATEUR_SUP_S', 'GENERATEUR_SUP_L', 'GENERATEUR_SUP_P'
  ])) {
    header("HTTP/1.1 400 Bad Request");
    header('Content-type: text/plain; charset="utf8"');
    echo "Erreur: la classe $matches[2] est inconnue\n";
    die();
  }
  $classeCnig = strtolower($matches[2]);
  $mgdbclient = new MongoDB\Client($mongouri);
  $first = true;
  foreach ($mgdbclient->urba->$classeCnig->find(['IDJDSUP'=> $idjdsup]) as $doc) {
    unset($doc['_id']);
    unset($doc['IDJDSUP']);
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
    die("Erreur: aucun objet géographique ne correspond à l'identifiant $idjdsup et à la classe $classeCnig\n");
  } else {
    die("\n]\n");
  }
}


header('HTTP/1.1 400 Bad Request');
echo "Unknown query\n";
die();
