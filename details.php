<?php
require_once 'settings.php';
require_once 'inc/include.php';
require_once 'inc/libZotero.php';
include_once 'inc/header.php';  // HTML header including css file
$zotero = new Zotero_Library( 'user', $user_ID, $user_name, $API_key );
$itemkey = $_REQUEST['itemkey'];

if( isset( $apc_cache_ttl ) && $apc_cache_ttl )
    $zotero->setCacheTtl( $apc_cache_ttl );

//purge old files from the cache
purge_cache( get_real_path( $cache_dir ), $cache_age);

// reading item details from API
$item = $zotero->fetchItem( $itemkey );

// displaying content of main item
?>
<hr />
<h2>Item Details</h2><hr />
<table>
    <?php 
    foreach( $item->apiObject as $field_name => $field ) : 
        
        // Apply any field-specific formatting
        switch( $field_name ) {
            case 'itemType':
                $field_formatted = un_camel( $field );
                break;
            case 'tags':
                $field_formatted = implode( ', ', array_map( create_function( '$val', 'return $val["tag"];' ), $field ) );
                break;
            case 'creators':
                $field_formatted = implode('<br />', array_map( create_function( '$val', 'return un_camel($val["creatorType"]) . ": " . $val["firstName"] . " " . $val["lastName"];' ), $field ) );
                break;
            default:
                $field_formatted = $field;
        }

    ?>
    <tr>
        <th scope="row"><?php echo un_camel($field_name) ?></th>
        <td><?php echo $field_formatted ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<hr />
<h2>Attachments (<?php echo $item->numChildren ?>)</h2>
<hr />
<?php
// getting child items from API, parsing data, displaying results
$child_items = $zotero->fetchItemChildren($item);

if( $child_items ) { 
?>
<?php foreach( $child_items as $child_item ) { ?>    
<table>
    <thead>
        <th colspan="2"><?php echo $child_item->apiObject['title'] ?></th>
    </thead>
    <?php 
    foreach( $child_item->apiObject as $field_name => $field ) :

        // Apply any field-specific formatting
        switch( $field_name ) {
            case 'tags':
                $field_formatted = implode( ', ', array_map( create_function( '$val', 'return $val["tag"];' ), $field ) );
                break;
            default:
                $field_formatted = $field;
        }

    ?>
    <tr>
        <th scope="row"><?php echo un_camel($field_name) ?></th>
        <td><?php echo $field_formatted ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="url">
        <th scope="row">Link</th>
        <?php if ( in_array( $child_item->apiObject['linkMode'], array( 'linked_file', 'linked_url' ) ) ) { ?>
            <td><a href="<?php echo $child_item->apiObject['url'] ?>"><?php echo $child_item->apiObject['url'] ?></a></td>
        <?php } else { ?>
            <td><a href="attachment.php?itemkey=<?php echo $child_item->itemKey ?>&mime=<?php echo $child_item->apiObject['contentType'] ?>">Access the Attachment as stored on the WebDAV server</a></td>
        <?php } ?>
    </tr>
</table>
<hr />
<?php

    }

}

?>
</body>
</html>
