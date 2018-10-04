<?php
require_once 'settings.php';
require_once 'inc/include.php';
$itemkey  = $_REQUEST['itemkey'];
$mimeType = $_REQUEST['mime'];

//purge old files from the cache
purge_cache( get_real_path( $cache_dir ), $cache_age);

// set up some stuff
$abs_output  = get_real_path( $cache_dir ) . "/" . $itemkey . "_" . date("YmdHis");
$abs_zipfile = get_real_path( $data_dir ) . "/" . $itemkey . ".zip";
$writefiles=true;

// check if attachment is already unzipped in cache. if so, point directory there and prevent new unzipping
$dir  = opendir( get_real_path( $cache_dir ) );
while ($dir_item = readdir($dir)) { 
    if (is_dir( get_real_path( $cache_dir ) . "/" . $dir_item)) {
        if (substr($dir_item, 0, strpos($dir_item, "_")) == $itemkey) {
            $abs_output = get_real_path( $cache_dir ) . "/" . $dir_item;
            $writefiles = false;      
//          echo("found one\n");
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
        $html_output="";
        $html_output .= "Display of Websnapshots is not fully implemented yet. Sorry, but this is due to a shortcoming of the zotero server API.<br><br>\n";
        $scriptpath = realpath(substr($_SERVER['SCRIPT_FILENAME'],0,strrpos($_SERVER['SCRIPT_FILENAME'],"/"))); 
        if ($scriptpath == substr( get_real_path( $cache_dir ), 0, strlen($scriptpath) ) ) {
            $cacheURL = ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],"/")) . substr($abs_output,strlen($scriptpath));
        } else {
            $cacheURL = $cache_base_URL;
        }
        if(strlen($cacheURL)>0) {
            $html_output .= "However, the websnap shot has been written to the cache directory and can be accessed there.<br>\n";
            $webfilename=findwebfile($abs_output);
            if (strlen($webfilename)>0) {
                $html_output .= "<a href=\"$cacheURL/$webfilename\" target=\"_blank\">Click here</a> to access the file that looks most like the main file for the websnapshot (or wait 5 seconds to be redirected there).<br>\n"; 
                $html_output .= "If that doens't work, check out <a href=\"$cacheURL\" target=\"_blank\">the entire websnapshot</a> folder and look for the main file yourself.";
                $html_output = "<html>\n<head>\n<script type=\"text/javascript\">\n<!--\nfunction delayer(){\nwindow.location = \"$cacheURL/$webfilename\"\n}\n//-->\n</script>\n</head>\n<body onLoad=\"setTimeout('delayer()', 5000)\">\n" . $html_output . "</body>\n</html>";
            } else {
                $html_output .= "I didn't find a file that looks most like the main file for the websnapshot, "; 
                $html_output .= "so you might want to check out <a href=\"$cacheURL\" target=\"_blank\">the entire websnapshot</a> folder and look for the main file yourself.";
                $html_output = "<html>\n<head>\n</head>\n<body>\n" . $html_output . "</body>\n</html>";
            }
        } else {
            $html_output .= "However, if you use a cache directory which is located in the same directory as this script ";
            $html_output .= "($scriptpath) or you provide the base URL to whichever cache directory in the settings.php, ";
            $html_output .= "you would be able to use the workaround provided by this script.";
            $html_output = "<html>\n<head>\n</head>\n<body>\n" . $html_output . "</body>\n</html>";
        }

        if( empty( $cache_base_URL ) ) {

            $output = file_get_contents($abs_output . '/' . $webfilename);

            // Add a <base> as a hack to include CSS files
            if( preg_match('/\<head(.*?)(\>)/i', $output, $matches, PREG_OFFSET_CAPTURE ) ) {

                $head_start = $matches[0][1];
                $head_end   = $matches[2][1];
                $output = substr( $output, 0, $head_end + 1 ) . '<base href="' .$cacheURL  . '/" />' . substr( $output, $head_end + 1 );
                echo $output;

            }

        } else {

            echo $html_output;

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
    purge_cache( get_real_path( $cache_dir ), -1);
}
?>
