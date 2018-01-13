<?php
/*PhpDoc:
name: atom.php
title: atom.php - Lecture du flux Atom du Gpu
doc: |
  Si le paramètre url est défini alors il contient l'URL de la page Atom suivante, sinon c'est la page initiale
  Si le paramètre action est défini alors une action particulière est réalisée,
  sinon la liste des entrées est affichée avec pour chacune le lien vers le flux Atom de second niveau
  et en pied de page 2 liens sont affichées:
   1) le lien vers la page Atom suivante
   2) le lien vers un dump de la page courante pour debugging
*/
if (isset($_GET['url']))
  $url = $_GET['url'];
else
  $url = 'http://www.geoportail-urbanisme.gouv.fr/atom/download-feed/';
$xmlstr = file_get_contents($url,'r');
if ($xmlstr === FALSE)
  die("Erreur d'ouverture du flux $url");
//echo $xmlstr;

$atom = new SimpleXMLElement($xmlstr);
//echo "<pre>"; print_r($atom);

echo $atom->title,"<br>\n";
if (isset($_GET['action']) and ($_GET['action']=='dump')) {
  echo "<pre>\n";
  print_r($atom);
  die();
}
echo "<ul>\n";
foreach ($atom->entry as $entry) {
  foreach ($entry->link as $link) {
    if ($link['rel']=='alternate')
      $entryurl = $link['href'];
  }
  echo "<li><a href='$entryurl'>$entry->title</a>\n";
}
echo "</ul>\n";
foreach ($atom->link as $link) {
  if ($link['rel']=='next')
    echo "<a href='?url=",urlencode($link['href']),"'>page suivante</a><br>\n";
}
echo "<a href='?action=dump&amp;url=",urlencode($url),"'>dump</a><br>\n";