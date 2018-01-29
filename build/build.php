<?php
/*PhpDoc:
name: build.php
title: build.php - génération de la base urba à partir des zip
tables:
  - name: du
    title: du - collection des documents d'urbanisme
    database: [../urba]
    doc: |
      voir schema.yaml
  - name: jdsup
    title: jdsup - collection des JD de SUP
    database: [../urba]
    doc: |
      voir schema.yaml
  - name: '{TableCnig}'
    title: '{TableCnig} - collections définies dans un des standards CNIG'
    database: [../urba]
    doc: |
      voir schema.yaml
  
doc: |
journal: |
  28/1/2018:
    modif du chemin du répertoire des zips
    changement nom collection sup en jdsup
    ajout de idGest dans jdsup
    correction d'un bug dans les données géographiques de JDSUP
  27/1/2018 :
    recopie sur http://s0.bdavid.eu/geoapi/urba/
  15/1/2018:
    filtrage des fichiers par leur extension
  14/1/2018:
    filtrage des fichiers shapes admissibles
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
  $pathinzip = "zip://$zipname#$path";
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

$dirpath = '../../../data/gpuzips';
$dir = opendir($dirpath)
  or die("Erreur d'ouverture du répertoire $dirpath\n");
// traitement de chaque ZIP
while (($zipname = readdir($dir)) !== false) {
  if (in_array($zipname, ['.','..','.DS_Store']))
    continue;
  //if (preg_match('!^(([\d]+)_(PLU|PLUi|POS|CC)_(\d+))\.zip$!i', $zipname, $matches))
  //  continue;
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
  if (1) { // dézippage
    echo "zip=$zipname\n";
    $zip = new ZipArchive;
    if ($zip->open("$dirpath/$zipname")!==TRUE) {
      echo "Erreur ligne ",__LINE__,": impossible d'ouvrir le fichier <$zipname>\n";
      continue;
    }
    for ($i=0; $i < $zip->numFiles; $i++) {
      $path = $zip->getNameIndex($i);
      if (substr($path,-1)=='/')
        continue;
      $bname = basename($path);
      if (($bname == 'Thumbs.db') or (substr($bname,0,1)=='.'))
        continue;
      $ext = substr($bname, strrpos($bname, '.')+1);
      $ext = strtoupper($ext);
      //echo "  path=$path\n";
      // les données géographiques
      if (in_array($ext,[
            'SHP','SHX','DBF','PRJ','QPJ','CPG','IDX',
            'TAB','DAT','ID','MAP','IND',
            'GML',
          ])) {
        extractFromZip("$dirpath/$zipname", $path);
        if (in_array($ext,['SHP','TAB','GML']))
          $shapes[substr($bname, 0, strlen($bname)-4)] = substr($bname, strlen($bname)-3);
      }
      // les pièces écrites
      elseif (in_array($ext,['PDF','JPEG','JPG','DOC','ODT','ODS'])
           and preg_match('!/(Pieces_ecrites|Actes)/!i', $path, $matches)) {
        $stat = $zip->statIndex($i);
        if (preg_match('!/(Pieces_ecrites|Actes)/(.*)$!i', $path, $matches))
          $Pieces_ecrites[] = [
            'cheminCourt'=> $matches[2], // chemin après /(Pieces_ecrites|Actes)/
            'cheminLong'=> $path, // chemin complet dans le Zip
            'taille'=> $stat['size'], // taille
          ];
        else
          $Pieces_ecrites[] = [
            'cheminLong'=> $path, // chemin complet dans le Zip
            'taille'=> $size, // taille
          ];
      }
      // probablement la fiche de métadonnnées
      elseif (in_array($ext,['XML'])) {
      }
      // bizarre
      elseif (in_array($ext,['PDF','HTML'])) {
      }
      // a voir
      elseif (in_array($ext,['CSV'])) {
      }
      // a voir
      elseif (in_array($ext,['ZIP','TXT','QGS','QML','QIX','SBN','SBX','GBMETA','LOCK'])) {
      }
      // a voir
      //else
        //die("fichier $path extension $ext non extrait\n");
    }
    $zip->close();
    //echo "Liste des shapes:"; print_r($shapes);
    //echo "Liste des Pieces_ecrites:"; print_r($Pieces_ecrites);
  }
  
  $duRecord = [];
  $jdsupRecord = [];
  // ZIP DU
  if (preg_match('!^(([\d]+)_(PLU|PLUi|POS|CC)_(\d+))\.zip$!i', $zipname, $matches)) {
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
    //echo "duRecord = "; print_r($duRecord);
    $baseurba->du->insertOne($duRecord);
  }
  
  // ZIP JD SUP
  elseif (preg_match('!^((\d+)_([^_]+)_(([\dAB]+)|(R\d+))_(\d+))\.zip$!i', $zipname, $matches)) {
    $idJdSup = $matches[1];
    $idGest = $matches[2];
    $codeSup = $matches[3];
    $codeTerritoire = $matches[4];
    $dateref = $matches[7];
    if ((strlen($codeTerritoire)==3) and (substr($codeTerritoire,0,1)=='0'))
      $codeTerritoire = substr($codeTerritoire,1);
    $jdsupRecord = [
      '_id'=> $idJdSup,
      'idGest'=> $idGest,
      'codeSup'=> $codeSup,
      'codeTerritoire'=> $codeTerritoire,
      'dateref'=> $dateref,
      'zipname'=> $zipname,
      'actes'=> $Pieces_ecrites,
    ];
    //echo "jdsupRecord = "; print_r($jdsupRecord);
    $baseurba->jdsup->insertOne($jdsupRecord);
    echo "Liste des shapes:"; print_r($shapes);
    //echo "Liste des Actes:"; print_r($Pieces_ecrites);
  }
  // ZIP SCOT
  elseif (preg_match('!^\d+_SCOT\.zip$!i', $zipname, $matches)) {
  }
  else
    die("No match ligne ".__LINE__." on $zipname\n");
  
  if ($shapes) {
    foreach ($shapes as $shape => $shpext) {
      // definition de la collection dans laquelle seront stockés les objets géographiques
      $collection = strtolower($shape);
      if ($duRecord) {
        if (preg_match('!^(zone_urba|secteur_cc)!', $collection, $matches))
          $collection = $matches[1];
        elseif ($collection == 'secteur')
          $collection = 'secteur_cc';
        elseif (preg_match('!^(prescription|info|habillage)_(pct|lin|surf|txt)!', $collection, $matches))
          $collection = $matches[1].'_'.$matches[2];
        elseif (preg_match('!^(information)_(pct|lin|surf|txt)$!', $collection, $matches))
          $collection = 'info_'.$matches[2];
        elseif (preg_match('!^(prescritpion)_(pct|lin|surf|txt)$!', $collection, $matches))
          $collection = 'prescription_'.$matches[2];
        else { /* if (in_array($collection,
                         ['doc_urba','doc_urba_com','ano_dessin','commune_pci_vecteur',
                          'er_dragey_ronthon','epr_dragey_ronthon',
                          '53185_annexe_ass_lin','53185_annexe_ass_pct'])) { */
          echo "Attention, le fichier $shape.$shpext n'est pas prévu par le standard CNIG\n";
          continue;
        }
        //else
          //die("Erreur: pour un JD de PLU, fichier shape $shape.$shpext inconnu\n");
      }
      elseif ($jdsupRecord) {
        $pattern_territoire = '(_r?[\dab]+|)?';
        $patern1 = "!^([^_]+)_(assiette|generateur)_sup_([slp])$pattern_territoire$!";
        $pattern2 = "!^(([^_]+)_)?(acte_sup|gestionnaire_sup|servitude|servitude_acte_sup)$pattern_territoire$!";
        if (preg_match($patern1, $collection, $matches)) {
          $collection = $matches[2].'_sup_'.$matches[3];
        }
        else { //if (preg_match($pattern2, $collection)) {
          echo "Attention, le fichier $shape.$shpext n'est pas prévu par le standard CNIG\n";
          continue;
        }
        //else
          //die("Erreur: pour un JD de SUP, fichier shape $shape.$shpext inconnu\n");
      }
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
        $s_srs = "-s_srs EPSG:2154 ";
        echo "Nouvel essai avec $s_srs\n";
        $command = "/usr/bin/ogr2ogr -f GeoJSON -t_srs EPSG:4326 $s_srs tmp/$shape.json tmp/$shape.$shpext";
        $string = exec($command, $output, $return_var);
        if ($return_var<>0) {
          print_r($output);
          throw new Exception("Erreur sur commande $command");
        }
      }
      $fp = fopen("tmp/$shape.json",'r');
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
        if ($duRecord)
          $feature['IDURBA'] = $duRecord['_id'];
        elseif ($jdsupRecord)
          $feature['IDJDSUP'] = $jdsupRecord['_id'];
        else {
          echo "Erreur ligne ",__LINE__,": le feature ne correspond ni à un DU ni à un JDSUP\n";
          continue;
        }
        $baseurba->$collection->insertOne($feature);
      }
      echo "Fin OK de lecture de tmp/$shape.json\n";
    }
  }
}
echo "Fin normale de build.php\n";
