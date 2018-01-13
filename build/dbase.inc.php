<?php
// http://php.net/manual/fr/book.dbase.php

function read_dbf(string $dbfname): array {
    $fdbf = @fopen($dbfname,'r');
    if ($fdbf === FALSE)
      throw new Exception("Unable to open $dbfname");
    $fields = array();
    $buf = fread($fdbf,32);
    $header=unpack( "VRecordCount/vFirstRecord/vRecordLength", substr($buf,4,8));
    //echo 'Header: '.json_encode($header).'<br/>';
    $goon = true;
    $unpackString='';
    while ($goon && !feof($fdbf)) { // read fields:
        $buf = fread($fdbf,32);
        if (substr($buf,0,1)==chr(13)) {
          $goon=false;
        } // end of field list
        else {
            $field=unpack( "a11fieldname/A1fieldtype/Voffset/Cfieldlen/Cfielddec", substr($buf,0,18));
            //echo 'Field: '.json_encode($field)."<br/>\n";
            $unpackString.="A$field[fieldlen]$field[fieldname]/";
            array_push($fields, $field);
        }
    }
    fseek($fdbf, $header['FirstRecord']+1); // move back to the start of the first record (after the field definitions)
    $records = [];
    for ($i=1; $i<=$header['RecordCount']; $i++) {
        $buf = fread($fdbf,$header['RecordLength']);
        $record=unpack($unpackString,$buf);
        //echo 'record: '.json_encode($record).'<br/>';
        //echo $i.$buf."<br/>\n";
        $records[] = $record;
    } //raw record
    fclose($fdbf);
    return ['fields'=> $fields, 'records'=> $records];
}

//print_r(read_dbf('tmp/DOC_URBA.dbf'));
