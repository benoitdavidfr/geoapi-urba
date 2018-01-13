<?php
/*PhpDoc:
name: build.php
title: build.php - génération de la base urba à partir des zip
doc: |
journal: |
  11/1/2018:
    améliorations, il existe une variété de nom de shape non conformes au standard
    ex: 
      urba.zone_urba_88095
    il faudrait générer une liste d'anomalies qui n'auraient pas du passer le validateur GpU
  7/1/2018:
    améliorations
*/
ini_set('memory_limit', '1280M');
require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/dbase.inc.php';
require_once __DIR__.'/../mongouri.inc.php';

$mgdbclient = new MongoDB\Client($mongouri);
$baseurba = $mgdbclient->urba;
$baseurba->drop();

// extrait dans tmp du zip zipname le fichier path
function extractFromZip($zipname, $path) {
  $pathinzip = "zip://zips/$zipname#$path";
  /*
  $fzip = fopen($pathinzip, 'r');
  if ($fzip === FALSE)
    die("Erreur d'ouverture de $pathinzip");
  $bname = basename($path);
  $fout = fopen("tmp/$bname", 'w');
  if ($fout === FALSE)
    die("Erreur d'ouverture de tmp/$bname");
  while ($buff = fread($fzip, 4*1024))
    fwrite($fout, $buff);
  fclose($fout);
  fclose($fzip);
  */
  $bname = basename($path);
  if (!copy($pathinzip, "tmp/$bname"))
    die("Erreur de copie de $pathinzip dans tmp/$bname");
}

// suppression des fichiers d'un répertoire
function cleanDir($dirpath) {
  $tmpdir = opendir($dirpath)
    or die("Erreur d'ouverture du répertoire $dirpath");
  while (($fname = readdir($tmpdir)) !== false) {
    if (!in_array($fname, ['.','..']))
      unlink("$dirpath/$fname");
  }
}

$dirpath = 'zips';
$dir = opendir($dirpath)
  or die("Erreur d'ouverture du répertoire $dirpath");
// traitement de chaque ZIP
while (($zipname = readdir($dir)) !== false) {
  if (in_array($zipname, ['.','..','.DS_Store']))
    continue;
  /*if (!in_array($zipname, [
      //'37166_PLU_20170330.zip',
      //'244900775_PLUi_20170926.zip',
      //'63061_CC_20051125.zip',
      //'84148_POS_20160322.zip',
      '29232_PLU_20170316.zip',
    ]))
    continue;*/
  // nettoyage de tmp
  cleanDir('tmp');
  $shapes = []; // liste des couches sous la forme [ basename => ext ]
  $Pieces_ecrites = []; // liste des chemin des pièces écrites
  if (1) { // dézippage des SHP
    echo "zip=$zipname\n";
    $zip = new ZipArchive;
    if ($zip->open("zips/$zipname")!==TRUE) {
      echo "Impossible d'ouvrir le fichier <$zipname>\n";
      continue;
    }
    for ($i=0; $i < $zip->numFiles; $i++) {
      $path = $zip->getNameIndex($i);
      $bname = basename($path);
      $ext = substr($bname, strrpos($bname, '.')+1);
      $ext = strtoupper($ext);
      //echo "  path=$path\n";
      if (in_array($ext,['SHP','SHX','DBF','PRJ','QPJ','CPG'])) {
        extractFromZip($zipname, $path);
        if ($ext == 'SHP')
          $shapes[substr($bname, 0, strlen($bname)-4)] = substr($bname, strlen($bname)-3);
      }
      elseif (($ext=='PDF') and preg_match('!/Pieces_ecrites/!', $path, $matches)) {
        if (preg_match('!^(([\d]+)_(PLU|PLUi|POS|CC)_(\d+))/Pieces_ecrites/(.*)$!', $path, $matches))
          $Pieces_ecrites[] = [$matches[5], $path];
        else
          $Pieces_ecrites[] = [$path, $path];
      }
    }
    $zip->close();
    echo "Liste des shapes:"; print_r($shapes);
    echo "Liste des Pieces_ecrites:"; print_r($Pieces_ecrites);
  }
  
  $idurba = '';
  if (preg_match('!^(([\d]+)_(PLU|PLUi|POS|CC)_(\d+))\.zip$!i', $zipname, $matches)) {
    // DU
    $idurba = $matches[1];
    $idAutorite = $matches[2];
    $nature = $matches[3];
    $approbation = $matches[4];
    // lecture DOC_URBA
    //$doc_urba = read_dbf('tmp/DOC_URBA.dbf');
    //echo "doc_urba = "; print_r($doc_urba['records']);
    $duRecord = [
      '_id'=> $idurba,
      //'titre'=> '',
      'idAutorite'=> $idAutorite,
      'nature'=> $nature,
      'approbation'=> $approbation,
      'piecesEcrites'=> $Pieces_ecrites,
    ];
    if (in_array($nature, ['PLU','POS','CC'])) {
      $duRecord['communes'] = [ $idAutorite ];
    }
    else {
      // lecture DOC_URBA_COM
      try {
        $doc_urba_com = read_dbf('tmp/DOC_URBA_COM.dbf');
        //echo "doc_urba_com = "; print_r($doc_urba_com['records']);
        if (count($doc_urba_com['records']) > 1) {
          $duRecord['communes'] = [];
          foreach ($doc_urba_com['records'] as $du_com) {
            if ($du_com['IDURBA'] == $idurba)
              $duRecord['communes'][] = $du_com['INSEE'];
          }
        }
      } catch (Event $e) {
        echo "Erreur: ",$e->getMessage(),"\n";
      }
    }
    echo "duRecord = "; print_r($duRecord);
    $baseurba->du->insertOne($duRecord);
  }
  // ZIP SUP
  elseif (preg_match('!^(\d+)_([^_]+)_((\d+)|(2[AB])|(R\d+))_(\d+)\.zip$!i', $zipname, $matches)) {
    $codeSup = $matches[2];
    $codeTerritoire = $matches[3];
    $dateref = $matches[7];
    $supRecord = [
      '_id'=> $codeSup.'_'.$codeTerritoire.'_'.$dateref,
      'codeSup'=> $codeSup,
      'codeTerritoire'=> $codeTerritoire,
      'dateref'=> $dateref,
    ];
    echo "supRecord = "; print_r($supRecord);
    $baseurba->sup->insertOne($supRecord);
  }
  // ZIP SCOT
  elseif (preg_match('!^\d+_SCOT\.zip$!i', $zipname, $matches)) {
  }
  else
    die("No match ligne ".__LINE__." on $zipname\n");
  
  if ($idurba) {
    foreach ($shapes as $shape => $shpext) {
      if (is_file("tmp/$shape.json")) {
        //echo "unlink($bname.json)<br>\n";
        unlink("tmp/$shape.json");
      }
      $s_srs = '';
      if (!is_file("tmp/$shape.prj") and !is_file("tmp/$shape.PRJ")) {
        //die("Erreur: pas de PRJ pour $shape\n");
        $s_srs = "-s_srs EPSG:2154 ";
        echo "Attention: pas de PRJ pour tmp/$shape.$shpext, utilisation de $s_srs\n";
      }
      $command = "/usr/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 $s_srs tmp/$shape.json tmp/$shape.$shpext";
      //echo "command=$command<br>\n\n";
      $string = exec($command, $output, $return_var);
      if ($return_var<>0) {
        print_r($output);
        throw new Exception("Erreur sur commande $command");
      }
      $fp = fopen("tmp/$shape.json",'r');
      $shape = strtolower($shape);
      if (!$fp)
        throw new Exception("Erreur d'ouverture de $shape.json");
      while (($line = fgets($fp)) !== FALSE) {
        if (strncmp($line,'{ "type": "Feature",', 20)<>0)
          continue;
        $line = rtrim($line);
        $len = strlen($line);
        if (substr($line,$len-1,1)==',')
          $line = substr($line,0,$len-1);
        //echo "line=$line\n";
        $feature = json_decode($line, true);
        if (!$feature and (json_last_error()==JSON_ERROR_UTF8)) {
          $line = utf8_encode($line);
          $feature = json_decode($line, true);
        }
        if (!$feature)
          throw new Exception("Erreur '".self::json_message_error(json_last_error())
                              ."' dans json_encode() sur: $line");
        if (!isset($feature['IDURBA']))
          $feature['IDURBA'] = $idurba;
        $baseurba->$shape->insertOne($feature);
      }
      echo "Fin OK de lecture de tmp/$shape.json\n";
    }
  }
}
