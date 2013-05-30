<?php

// remove all subdirectories of a cache folder which are older than a given number of minutes
// the age is determined by the folder name which has the format XXXXXXX_YYYYMMDDHHMMSS
// where XXXXXXX is an arbitrary string (e.g. itemKey) which must not contain underscores (_)
function purge_cache($path, $cacheage=0) {
    $dir  = opendir($path);
    $controldate = intval(date("YmdHis", mktime(date("H"), intval(date("i")) - $cacheage, date("s"), date("m"),   date("d"),   date("Y"))));
    while ($dir_item = readdir($dir)) { 
        if ((is_dir($path . "/" . $dir_item)) && ($dir_item != ".") && ($dir_item != "..")) {
            $dirdate = intval(str_replace("_", "", substr($dir_item, strpos($dir_item, "_") + 1)));
            if ($dirdate < $controldate) {
                rmdirr($path . "/" . $dir_item);
            }
        }
    }
}

// unzips a zipfile to a path and decodes filenames encoded in base64 (if they have a %ZB64 suffix)
// can be set to not execute writing of unzipped files ($writefiles=false)
// returns filename of the unzipped file (if only one file in zip file) otherwise the directory path that holds unzipped files 
function unzip($zipfile,$outpath,$depreciated_option=0,$writefiles=true) {

    $zip=zip_open($zipfile);
    if(!$zip) {return("Unable to proccess zipfile \"" . $zipfile . "\"");}

    $e='';
    $i=0;
    while($zip_entry=zip_read($zip)) {
       $zdir=dirname(zip_entry_name($zip_entry));
       $zname=zip_entry_name($zip_entry);

       if(!zip_entry_open($zip,$zip_entry,"r")) {$e.="Unable to proccess file \"".$zname."\"";continue;}
       if(!is_dir($outpath."/".$zdir)) mkdirr($outpath."/".$zdir,0777);

       #print "{$zdir} | {$zname} \n";

       $zip_fs=zip_entry_filesize($zip_entry);
       if(empty($zip_fs)) continue;

       $zz=zip_entry_read($zip_entry,$zip_fs);
       
       if (substr($zname,-5)=="%ZB64") {
          $zname=substr($zname,0,strpos($zname,"%ZB64"));
          $zname=base64_decode($zname);
       }
       
       if($writefiles) {
          $z=fopen($outpath . "/" . $zname,"w");
          fwrite($z,$zz);
          fclose($z);
        }
       zip_entry_close($zip_entry);
       
       $i = $i +1;
    } 
    zip_close($zip);

    if (strlen($e)>0) {
        echo($e);
        return($e);
    } else {
        if($i>1) {
            return($outpath);
        } else {
            return($outpath . "/" . $zname);
        }
    }
} 

// recursively remove directory (ie. directory and any file and subdirectory contents)
function rmdirr($dir) { 
    if (is_dir($dir)) { 
        $objects = scandir($dir); 
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (filetype($dir."/".$object) == "dir") rmdirr($dir."/".$object); else unlink($dir."/".$object); 
            } 
        } 
    reset($objects); 
    rmdir($dir); 
    } 
} 

// recursively create a directory (i.e. creates any parent tree if not existing)
function mkdirr($pn,$mode=null) {

  if(is_dir($pn)||empty($pn)) return true;
  $pn=str_replace(array('/', ''),DIRECTORY_SEPARATOR,$pn);

  if(is_file($pn)) {trigger_error('mkdirr() File exists', E_USER_WARNING);return false;}

  $next_pathname=substr($pn,0,strrpos($pn,DIRECTORY_SEPARATOR));
  if(mkdirr($next_pathname,$mode)) {if(!file_exists($pn)) {return mkdir($pn,$mode);} }
  return false;
}

function findwebfile($path) {
    $filename = "";
    if (is_dir($path)) { 
        $objects = scandir($path);
        foreach ($objects as $object) {
            if(substr($object,-5)==".html") {
                if ($filename!="") {
                    if(substr($filename,-7,1)=="_") $filename = $object;
                } else {
                    $filename = $object;                
                }
            }
        } 
    reset($objects); 
    }     
    return $filename;
}

function foldersize($path) {
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/'). '/';

    foreach($files as $t) {
        if ($t<>"." && $t<>"..") {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = foldersize($currentFile);
                $total_size += $size;
            }
            else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }   
    }

    return $total_size;
}

function format_size($size) {
    $units = explode(' ', 'B KB MB GB TB PB');

    $mod = 1024;

    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }

    $endIndex = strpos($size, ".")+3;

    return substr( $size, 0, $endIndex).' '.$units[$i];
}

/**
 * Format date from an item record into a 'x days/hours/minutes ago' format
 */
function format_date( $dateString ) {
    if( class_exists( 'DateTime' ) ) {
        $date = new DateTime();
        $date->setTimestamp( strtotime( $dateString ) );
        $interval = $date->diff( new DateTime( 'now' ) );


        $format_array = array();

        if( $interval->y !== 0 )
            $format_array[] = '%y years';
        if( $interval->m !== 0 )
            $format_array[] = '%m months';
        if( $interval->d !== 0 )
            $format_array[] = '%d days';
        if( $interval->h !== 0 )
            $format_array[] = '%h hours';

        return $interval->format( join(' ', $format_array ) . ' ago');
    }
    return $dateString;
}

/**
 * Make a camelCase string into legible words
 */
function un_camel( $string ) {
    return ucfirst( implode(' ', preg_split('/([[:upper:]][[:lower:]]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY) ) );
}

/**
 * Resolve absolute and relative paths
 */
function get_real_path( $path ) {
	
	// Try to resolve absolute paths, but just return them on error
	if( '/' == $path[0] ) {
		if( ! $real_path = realpath( $path ) )
			return $path;
		return realpath( $path );
	}
	
	// Make relative paths absolute
	$root = dirname( dirname(__FILE__) );
	if( ! realpath( $root . '/' . $path ) ) {
		return $root . '/' . $path;
	}
	return realpath( $root . '/' . $path );
}

?>