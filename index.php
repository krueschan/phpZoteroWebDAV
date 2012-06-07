<?php
require_once 'settings.php';
require_once 'inc/include.php';
require_once 'inc/phpZotero.php';
include_once 'inc/header.php';  // HTML header including css file
$zotero = new phpZotero($API_key);
$ipp=$_REQUEST['ipp']; 
if (!($ipp)) $ipp = $def_ipp;
$sort=$_REQUEST['sort']; 
if (!($sort)) $sort = $def_sort;
$sortorder=$_REQUEST['sortorder']; 
if (!($sortorder)) $sortorder = $def_sortorder;
$page=$_REQUEST['page']; 
if (!($page)) $page = 1;

$webdav_url="http://" . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'] . "webdav_server.php/zotero/";

echo("<h3>");
echo("Total size of stored attachments: <u>" . format_size(foldersize(getcwd() . '/' . $data_dir)) . "</u>");
echo("&nbsp; &nbsp; &nbsp; &nbsp;");
echo("WebDAV URL: <a href='" . $webdav_url . "'>" . $webdav_url . "</a>");
echo("</h3>");

$orders[0] = $sortorder;
if (strcmp($orders[0],"asc")){
    $orders[1]='asc';
}else{
    $orders[1]='desc';
}

//purge old files from the cache
purge_cache(realpath("./" . $cache_dir), $cache_age);

$i = 0;
$item = array( 0 => array(title=>"", itemKey=>"", creatorSummary=>"", year=>"", numChildren=>""));

// get first set of items from API
$start = ($page - 1) * $ipp;
if ($ipp > $fetchlimit) $limit = $fetchlimit; else $limit = $ipp;
$items = $zotero->getItemsTop($user_ID, array(format=>'atom', content=>'none', start=>$start, limit=>$limit, order=>$sort, sort=>$sortorder));
$totalitems = intval(substr($items,strpos($items, "<zapi:totalResults>") + 19, strpos($items, "</zapi:totalResults>") - strpos($items, "<zapi:totalResults>") - 19));

// MAIN DATA TABLE
// parse result sets, write out data and get more from API if needed
echo("<table class=\"library-items-div\">\n");
echo("<tr><td><b>Attachments</a></b></td>");
echo("<td><b><a href='?page=1&sort=creator&sortorder=".$orders[!(boolean) abs(strcmp($sort,"creator"))]."'>Creator</a></b></td>");
echo("<td><b><a href='?page=1&sort=date&sortorder=".$orders[!(boolean) abs(strcmp($sort,"year"))]."'>Year</b></td>");
echo("<td><b><a href='?page=1&sort=title&sortorder=".$orders[!(boolean) abs(strcmp($sort,"title"))]."'>Title</b></td>");
echo("</tr></b>");
while (($i < ($ipp - 1)) && (strpos($items, "<entry>")>0)) {
    $offset=0;
    $pos = strpos($items, "<entry>", $offset);
    while ($pos !== false) {
        $entry = substr($items,strpos($items, "<entry>", $offset), strpos($items, "</entry>", $offset) - strpos($items, "<entry>", $offset) + 8);
        $item_title = "";
        $item_itemKey = "";
        $item_creatorSummary = "";
        $item_year = "";
        $item_numChildren = "";
        if (strpos($entry, "<title>")>0) $item_title = substr($entry,strpos($entry, "<title>") + 7, strpos($entry, "</title>") - strpos($entry, "<title>") - 7);
        if (strpos($entry, "<zapi:key>")>0) $item_itemKey = substr($entry,strpos($entry, "<zapi:key>") + 10, strpos($entry, "</zapi:key>") - strpos($entry, "<zapi:key>") - 10);
        if (strpos($entry, "<zapi:creatorSummary>")>0) $item_creatorSummary = substr($entry,strpos($entry, "<zapi:creatorSummary>") + 21, strpos($entry, "</zapi:creatorSummary>") - strpos($entry, "<zapi:creatorSummary>") - 21);
        if (strpos($entry, "<zapi:year>")>0) $item_year = substr($entry,strpos($entry, "<zapi:year>") + 11, strpos($entry, "</zapi:year>") - strpos($entry, "<zapi:year>") - 11);
        if (strpos($entry, "<zapi:numChildren>")>0) $item_numChildren = substr($entry,strpos($entry, "<zapi:numChildren>") + 18, strpos($entry, "</zapi:numChildren>") - strpos($entry, "<zapi:numChildren>") - 18);
        $item[$i] = array(title=>$item_title, itemKey=>$item_itemKey, creatorSummary=>$item_creatorSummary, year=>$item_year, numChildren=>$item_numChildren);
        echo("<tr>");
        echo("<td><a href=\"details.php?itemkey="  . $item[$i]['itemKey'] . "\">" . $item[$i]['numChildren'] . "</a></td>");
        echo("<td><a href=\"details.php?itemkey="  . $item[$i]['itemKey'] . "\">" . $item[$i]['creatorSummary'] . "</a></td>");
        echo("<td><a href=\"details.php?itemkey="  . $item[$i]['itemKey'] . "\">" . $item[$i]['year'] . "</a></td>");
        echo("<td><a href=\"details.php?itemkey="  . $item[$i]['itemKey'] . "\">" . $item[$i]['title'] . "</a></td>");
        echo("</tr>\n");
        $i = $i +1;
        $offset = strpos($items, "</entry>", $offset) + 8;
        $pos = strpos($items, "<entry>", $offset);
    }
    $items = $zotero->getItemsTop($user_ID, array(format=>'atom', content=>'none', start=>($start+$i), limit=>$limit, order=>$sort, sort=>$sortorder));    
}
echo("</table><br>\n\n");
echo(($start +1) . " to " . ($start + $i) . " of " . $totalitems);

// NAVIGATION FOOTER
echo("<hr>\n<table>\n");
$parm_ipp = ($ipp == $def_ipp) ? "": "&ipp=$ipp";
$parm_sort = ($sort == $def_sort) ? "": "&sort=$sort";
$parm_sortorder = ($sortorder == $def_sortorder) ? "": "&sortorder=$sortorder";
$i = 1;
echo("<tr><td>Pages</td><td>");
$pages = intval($totalitems / $ipp) + 1;
while ($i <= $pages) {
    if ($i != $page) echo("<a href=\"?page=$i" . $parm_ipp . $parm_sort . $parm_sortorder . "\">");
    echo ("-$i-");
    if ($i != $page) echo("</a>");
    echo ("&nbsp;&nbsp;&nbsp;");
    $i = $i + 1;
    if ($i > 5) {
        if ($i < ($page - 2)) {
            $i = $page - 2;
            echo (" . . . &nbsp;&nbsp;&nbsp;");
        }
    }
    if (($i > ($page + 2)) && ($i > 5)) {
        if ($i < ($pages - 4)) {
            $i = $pages - 4;
            echo (" . . . &nbsp;&nbsp;&nbsp;");
        }
    }    
}
echo ("</td></tr>\n");
echo("<tr><td>Items&nbsp;per&nbsp;Page</td><td>");
$ipp_list = array (1,10,20,50,100,200,500,1000,9999999);
$i = 0;
while ($i <= 7) {
    if ($ipp != $ipp_list[$i]) echo("<a href=\"?page=$page&ipp=" . $ipp_list[$i] . $parm_sort . $parm_sortorder . "\">");
    echo ("-$ipp_list[$i]-");
    if ($ipp != $ipp_list[$i]) echo("</a>");
    echo ("&nbsp;&nbsp;&nbsp;");
    $j = $i + 1;
    if (($ipp > $ipp_list[$i]) && ($ipp < $ipp_list[$j])) echo ("-$ipp-&nbsp;&nbsp;&nbsp;");
    $i = $i + 1;
}
echo ("</td></tr>\n");
echo("<tr><td>Sort By</td><td>");
$s_list = array ("dateAdded", "title", "creator", "type", "date", "publisher", "publication", "journalAbbreviation", "language", "dateModified", "accessDate", "libraryCatalog", "callNumber", "rights", "addedBy", "numItems");
$i = 0;
while ($i <= 15) {
    if ($sort != $s_list[$i]) echo("<a href=\"?page=$page" . $parm_ipp . "&sort=" . $s_list[$i] . $parm_sortorder . "\">");
    echo ("-$s_list[$i]-");
    if ($sort != $s_list[$i]) echo("</a>");
    echo ("&nbsp;&nbsp;&nbsp;");
    $i = $i + 1;
}
echo ("</td></tr>\n");
echo("<tr><td>Sort Order</td><td>");
$so_list = array ("asc", "desc");
$i = 0;
while ($i <= 1) {
    if ($sortorder != $so_list[$i]) echo("<a href=\"?page=$page" . $parm_ipp . $parm_sort . "&sortorder=" . $so_list[$i] . "\">");
    echo ("-$so_list[$i]-");
    if ($sortorder != $so_list[$i]) echo("</a>");
    echo ("&nbsp;&nbsp;&nbsp;");
    $i = $i + 1;
}
echo ("</td></tr>\n</table>\n");
?>
</body>
</html>
