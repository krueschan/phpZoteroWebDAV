<?php
require_once 'settings.php';
require_once 'inc/include.php';
require_once 'inc/libZotero.php';
include_once 'inc/header.php';  // HTML header including css file
$zotero = new Zotero_Library( 'user', $user_ID, $user_name, $API_key );

if( isset( $apc_cache_ttl ) && $apc_cache_ttl )
	$zotero->setCacheTtl( $apc_cache_ttl );

$ipp  = isset($_REQUEST['ipp']) ? (int)$_REQUEST['ipp'] : $def_ipp;
$sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : $def_sort; 
$sortorder = isset($_REQUEST['sortorder']) ? $_REQUEST['sortorder'] : $def_sortorder;
$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;

$webdav_url=( isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST']  . str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']) . "webdav_server.php/zotero/";

?>
<h3>
    Total size of stored attachments: <u><?php echo format_size(foldersize(getcwd() . '/' . $data_dir)) ?></u>
    &nbsp; &nbsp; &nbsp; &nbsp;
    WebDAV URL: <a href="<?php echo $webdav_url ?>"><?php echo $webdav_url ?></a>
</h3>
<?php

$orders[0] = $sortorder;
if (strcmp($orders[0],"asc")){
    $orders[1]='asc';
}else{
    $orders[1]='desc';
}

//purge old files from the cache
purge_cache(realpath("./" . $cache_dir), $cache_age);

// get first set of items from API
$start = ($page - 1) * $ipp;
if ($ipp > $fetchlimit) $limit = $fetchlimit; else $limit = $ipp;

// TODO: include collections on index page for traversal
//$collections = $zotero->fetchCollections( array( 'collectionKey' => '' ) );
//print_r($collections);
//if( count( $collections ) ) {
//	$collectionKey = $collections[0]->collectionKey;
//}

$fetch_params = array(
    'order'   => $sort,
    'sort'    => $sortorder,
//    'content' => 'none',
    'limit'   => $limit
);

$fetch_offset = $start;
$items = array();

do {

    $fetched = $zotero->fetchItemsTop( array_merge( $fetch_params, array( 'start' => $fetch_offset ) ) );

    $items = array_merge( $items, $fetched );
    $fetch_offset += count( $items );

    if( isset($zotero->getLastFeed()->links['next'])){
       $moreItems = true;
    } else {
       $moreItems = false;
    }

} while( count( $items ) < $ipp && count( $fetched ) >= $fetchlimit );

$totalitems = $zotero->getLastFeed()->totalResults;

// MAIN DATA TABLE
// parse result sets, write out data and get more from API if needed
?>
<table class="library-items-div">
    <tr>
        <th>Attachments</th>
        <th><a href="?page=1&sort=dateAdded">Added</a></th>
        <th><a href="?page=1&sort=creator&sortorder=<?php echo $orders[!(boolean) abs(strcmp($sort,"creator"))] ?>">Creator</a></th>
        <th><a href="?page=1&sort=date&sortorder=<?php echo $orders[!(boolean) abs(strcmp($sort,"date"))] ?>">Date</a></th>
        <th><a href="?page=1&sort=title&sortorder=<?php echo $orders[!(boolean) abs(strcmp($sort,"title"))] ?>">Title</a></th>
    </tr>
<?php foreach( $items as $item ) { ?>
    <tr>
        <td><a href="details.php?itemkey=<?php echo $item->itemKey ?>"><?php echo $item->numChildren ?></a></td>
        <td><a href="details.php?itemkey=<?php echo $item->itemKey ?>"><?php echo format_date( $item->dateAdded )  ?></a></td>
        <td><a href="details.php?itemkey=<?php echo $item->itemKey ?>"><?php echo $item->creatorSummary ?></a></td>
        <td><a href="details.php?itemkey=<?php echo $item->itemKey ?>"><?php echo $item->apiObject['date'] ?></a></td>
        <td><a href="details.php?itemkey=<?php echo $item->itemKey ?>"><?php echo $item->title ?></a></td>
    </tr>
<?php } ?>
    <tfoot>
        <tr>
            <td><?php echo(($start +1) . " to " . count( $items ) . " of " . $totalitems); ?></td>
        </tr>
    </tfoot>
</table>
<br />
<hr />
<table>
<?php

// NAVIGATION FOOTER
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
