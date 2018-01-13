<?php
/*PhpDoc:
name: api.php
title: api.php - script exécuté pour l'API
doc: |
  l'URL http://urba.geoapi.fr est redirigée vers http://geoapi.fr/urba/api.php
journal: |
  6/1/2018:
    création
*/

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

$uri0 = "http://urba.geoapi.fr/autorites/";
$data = [
  'autorites'=> [
    ['id'=> '21054', 'libelle'=> "Commune de Beaune", 'statut'=>'actuel', 'departement'=>'21', 'uri'=>$uri0.'21054'],
    ['id'=> '247600596', 'libelle'=> "Communauté de l'agglomération havraise", 'statut'=>'actuel', 'departement'=>'76', 'uri'=>$uri0.'247600596'],
    null
  ],
];

// /terms
if (preg_match('!^/terms$!', $_SERVER['PATH_INFO'])) {
  header('Content-type: text/html; charset="utf8"');
  echo file_get_contents(__DIR__.'/terms.html');
  die();
}

// /autorites
if (preg_match('!^/autorites$!', $_SERVER['PATH_INFO'])) {
  header('Content-type: application/json; charset="utf8"');
  echo json_encode($data['autorites'],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
  );
  die();
}

// /autorites/{id}
if (preg_match('!^/autorites/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
  $id = $matches[1];
  for($i=0; $data['autorites'][$i]; $i++)
    if ($data['autorites'][$i]['id']==$id)
      break;
  if ($data['autorites'][$i]) {
    header('Content-type: application/json; charset="utf8"');
    echo json_encode($data['autorites'][$i],
      JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
    );
    die();
  }
}

header('HTTP/1.1 400 Bad Request');
echo "Unknown query\n";
die();
