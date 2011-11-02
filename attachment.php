<?php
require_once 'settings.php';
require_once 'inc/include.php';
require_once 'inc/phpZotero.php';
$zotero   = new phpZotero($API_key);
$itemkey  = $_REQUEST['itemkey'];
$mimeType = $_REQUEST['mime'];

//purge old files from the cache
purge_cache(realpath("./" . $cache_dir), $cache_age);

// set up some stuff
$abs_output  = realpath("./" . $cache_dir) . "/" . $itemkey . "_" . date("YmdHis");
$abs_zipfile = realpath("./" . $data_dir . "/" . $itemkey . ".zip");
$writefiles=true;

// check if attachment is already unzipped in cache. if so, point directory there and prevent new unzipping
$dir  = opendir(realpath("./" . $cache_dir));
while ($dir_item = readdir($dir)) { 
    if (is_dir(realpath("./" . $cache_dir) . "/" . $dir_item)) {
        if (substr($dir_item, 0, strpos($dir_item, "_")) == $itemkey) {
            $abs_output = realpath("./" . $cache_dir) . "/" . $dir_item;
            $writefiles = false;      
            echo("found one");
        }
    } 
} 
closedir($dir);

// call the unzipping function to get the filename and unzip the attachment to cache if not already in cache
$result = unzip($abs_zipfile,$abs_output,true,$writefiles);

// send attachment (or error message) to browser
$cacheURL = "";
if(substr($result,0,strlen($abs_output)) == $abs_output) {
    if($result == $abs_output) {
        echo("Display of Websnapshots is not fully implemented yet. Sorry, but this is due to a shortcoming of the zotero server API.<br><br>\n");
        $scriptpath = realpath(substr($_SERVER['SCRIPT_FILENAME'],0,strrpos($_SERVER['SCRIPT_FILENAME'],"/"))); 
        if ($scriptpath == substr(realpath("./" . $cache_dir),0,strlen($scriptpath))) {
            $cacheURL = "http://" . $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],"/")) . substr($abs_output,strlen($scriptpath));
        } else {
            $cacheURL = $cache_base_URL;
        }
        if(strlen($cacheURL)>0) {
            echo("However, the websnap shot has been written to the cache directory and can be accessed there.<br>\n");
            $webfilename=findwebfile($abs_output);
            if (strlen($webfilename)>0) {
                echo ("<a href=\"$cacheURL/$webfilename\" target=\"_blank\">Click here</a> to access the file that looks most like the main file for the websnapshot.<br>\n"); 
                echo ("If that doens't work, check out <a href=\"$cacheURL\" target=\"_blank\">the entire websnapshot</a> folder and look for the main file yourself.");
            } else {
                echo ("I didn't find a file that looks most like the main file for the websnapshot, "); 
                echo ("so you might want to check out <a href=\"$cacheURL\" target=\"_blank\">the entire websnapshot</a> folder and look for the main file yourself.");
            }
        } else {
            echo("However, if you use a cache directory which is located in the same directory as this script ");
            echo("($scriptpath) or you provide the base URL to whichever cache directory in the settings.php, ");
            echo("you would be able to use the workaround provided by this script.");
        }
    } else {
        header("Content-type: " . $mimeType);
        header("Content-Disposition: filename=\"" . pathinfo($result, PATHINFO_BASENAME) . "\"");
        readfile($result);
    }
} else {
    echo("AN ERROR HAS OCCURRED!  -  " . $result);
}

//purge old files from the cache again if $cache_age=0 (ie immediate deletion of cache files)
if (($cache_age==0) && (strlen($cacheURL)==0)) {    // do not purge immediately if web accessible websnapshot has been un zipped
    purge_cache(realpath("./" . $cache_dir), -1);
}
?>