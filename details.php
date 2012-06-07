<?php
require_once 'settings.php';
require_once 'inc/include.php';
require_once 'inc/phpZotero.php';
include_once 'inc/header.php';  // HTML header including css file
$zotero = new phpZotero($API_key);
$itemkey = $_REQUEST['itemkey'];

//purge old files from the cache
purge_cache(realpath("./" . $cache_dir), $cache_age);

// reading item details from API
$item = $zotero->getItem($user_ID, $itemkey, array(format=>'atom'));
$content = substr($item,strpos($item, "<content type="), strpos($item, "</content>") - strpos($item, "<content type=", $offset) + 10);

// displaying content of main item
echo("<hr><h2>Item Details</h2><hr>\n");
echo($content . "\n<hr>\n");
echo("<h2>Attachments</h2><hr>\n");

// getting child items from API, parsing data, displaying results
$child_items = $zotero->getItemChildren($user_ID, $itemkey, array(format=>'atom', limit=>99));
$offset=0;
$pos = strpos($child_items, "<entry>", $offset);
while ($pos !== false) {
    $entry = substr($child_items,strpos($child_items, "<entry>", $offset), strpos($child_items, "</entry>", $offset) - strpos($child_items, "<entry>", $offset) + 8);
    $title = substr($entry,strpos($entry, "<title>") + 7, strpos($entry, "</title>") - strpos($entry, "<title>") - 7);
    $child_itemkey = substr($entry,strpos($entry, "<zapi:key>") + 10, strpos($entry, "</zapi:key>") - strpos($entry, "<zapi:key>") - 10);
    $content  = substr($entry,strpos($entry, "<content type="), strpos($entry, "</content>") - strpos($entry, "<content type=") + 10);
    $url = substr($content, strpos($content, "<td>", strpos($content, "<tr class=\"url\">")) + 4);
    $url = substr($url, 0, strpos($url,"</td>"));  // this is broken up into two commands to keep it simple
    $mimeType = substr($content, strpos($content, "<td>", strpos($content, "<tr class=\"mimeType\">")) + 4);
    $mimeType = substr($mimeType, 0, strpos($mimeType,"</td>"));  // this is broken up into two commands to keep it simple
    $linkMode = substr($content, strpos($content, "<td>", strpos($content, "<tr class=\"linkMode\">")) + 4);
    $linkMode = intval(substr($linkMode, 0, strpos($linkMode,"</td>")));  // this is broken up into two commands to keep it simple
    $isntpdf = strcasecmp($mimeType,'application/pdf');
    $content2 = substr($content, 0, strpos($content, "</table>"));
    $content2 .= "  <tr class=\"url\">\n";
    $content2 .= "            <th style=\"text-align: right\">Link</th>\n";
    if ($linkMode==1 && $isntpdf ) {
        $content2 .= "            <td><a href=\"$url\">$url</a></td>\n";    
    } else {
        $content2 .= "            <td><a href=\"attachment.php?itemkey=$child_itemkey&mime=$mimeType\">Access the Attachment as stored on the WebDAV server</a></td>\n";
    }
    $content2 .= "          </tr>\n        ";
    $content2 .= substr($content, strpos($content, "</table>"));
    echo($content2 . "\n");
    echo("<hr>\n");
    $offset = strpos($child_items, "</entry>", $offset) + 8;
    $pos = strpos($child_items, "<entry>", $offset);
}

?>
</body>
</html>
