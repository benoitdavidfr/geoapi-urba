<?php
/*PhpDoc:
name: dwnld.php
title: dwnld.php - Lecture du flux Atom du Gpu et téléchargement des zip
doc: |
  Exécution sur OVH le 31/12/2017
  Téléchargement de 100 Go de documents dans /mnt/sdb/zips/ et plantage disque plein

  $ sudo docker run -it --rm --name dwnld \
      --mount type=bind,source=/mnt/sdb/zips,target=/usr/src/myapp/zips \
      -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:7.2-cli php dwnld.php

  Le 31/12/2017: Arrêt en erreur sur disque plein:
  <li>PLU de la commune de Rougiers (83110) - 20/03/2017
                    
  Flux du jeu de données de : 83110_PLU_20170320<br>
  Atom niv2 entry title[0]: Archive de "83110_PLU_20170320"
  ouverture de 'https://www.geoportail-urbanisme.gouv.fr/document/download-by-partition/DU_83110'<br>

  Warning: fopen(zips/83110_PLU_20170320.zip): failed to open stream: No space left on device in /usr/src/myapp/dwnld.php on line 46
  Erreur d'ouverture du fichier 'zips/83110_PLU_20170320.zip'
*/

// téléchargement d'un ZIP
function dwnldZip(string $url, string $name): bool {
  $destpath = 'zips/'.$name.'.zip';
  if (is_file($destpath)) {
    echo "Skip $destpath\n";
    return false;
  }
  echo "ouverture de '$url'<br>\n";
  // PHP's default user agent (which is most probably just a blank string) is blocked by the web server
  // you are requesting web page from. That's why you may need to set a fake user agent. 
  $opts = ['http'=> ['header' => "User-Agent:MyAgent/1.0\r\n"]];
  $context = stream_context_create($opts);
  $fzip = fopen($url, 'r', false, $context);
  if ($fzip === FALSE)
    die("Erreur d'ouverture de l'url '$url'\n");
  //print_r($http_response_header); die();
  $fdest = fopen($destpath, 'w');
  if ($fdest === FALSE)
    die("Erreur d'ouverture du fichier '$destpath'\n");
  while($buff = fread($fzip, 1024*1024)) {
    fwrite($fdest, $buff);
  }
  fclose($fdest);
  fclose($fzip);
  return true;
}

// téléchargement d'une entrée Atom de niveau 1
function dwnldEntry(string $entryurl) {
  $xmlstr = file_get_contents($entryurl,'r');
  if ($xmlstr === FALSE)
    die("Erreur d'ouverture du flux Atom n2 '$entryurl'");
  $atom = new SimpleXMLElement($xmlstr);
  echo $atom->title,"<br>\n";
  //echo "<pre>"; print_r($atom); die();
  foreach ($atom->entry as $entry) {
    $title = (string)$entry->title[0];
    echo "Atom niv2 entry title[0]: $title\n";
    if (!preg_match('!^Archive de "([^"]*)"$!', $title, $matches))
      die("No match on '$title'\n");
    $name = $matches[1];
    $url = '';
    foreach ($entry->link as $link) {
      if ($link['rel']=='alternate') {
        $url = $link['href'];
      }
    }
    if ($url) {
      dwnldZip($url, $name);
    }
  }
}

// boucle sur les pages du flux Atom de premier niveau
$url = 'https://www.geoportail-urbanisme.gouv.fr/atom/download-feed/';
while($url) {
  $xmlstr = file_get_contents($url,'r');
  if ($xmlstr === FALSE)
    die("Erreur d'ouverture du flux Atom n1 '$url'");
  $atom = new SimpleXMLElement($xmlstr);
  //echo "<pre>"; print_r($atom);

  echo $atom->title,"<br>\n";
  echo "<ul>\n";
  foreach ($atom->entry as $entry) {
    echo "<li>$entry->title\n";
    foreach ($entry->link as $link) {
      if ($link['rel']=='alternate')
        dwnldEntry($link['href']);
    }
  }
  echo "</ul>\n";
  $url = '';
  foreach ($atom->link as $link) {
    if ($link['rel']=='next')
      $url = $link['href'];
  }
}


