<?php
// Zotero API settings. Obtain from zotero.org after logging in by going to https://www.zotero.org/settings/keys
// the script only needs read access to your personal library, so you can disable everything else.
$API_key = '';      // obtain from https://www.zotero.org/settings/keys [no default]
$user_ID = '';      // obtain from https://www.zotero.org/settings/keys [no default]
$fetchlimit = 20;   // [default=20]; how many items to fetch with each API call (maximum allowed by zotero is 99). use your value for items per page below ($def_ipp) unless there are issues, e.g. server timeouts


// I know, those are not API settings, but I need to know anyway...
$data_dir  = 'data/zotero';     // [default="data/zotero"]; directory with sync data, either relative to the script's location or absolute path (on the same server though!). (NB: due to the way zotero sync works, this path ends most definetely in "/zotero)
$cache_dir = 'cache';           // [default="cache"]; cache directory, relative or absolute. must be writable by the script. will attempt to create if not found
$cache_age = 0;                 // [default=0]; in minutes. files will not be re-read from the sync storage to the file cache if they have already been grabbed within this time period. Also, cache folders that are older than this will be purged from the cache. set to 0 if you want to empty cache after each use (NB: if the attachment in question is a web snapshot and can be accessed by web the attachment will not be purged until the next use of the script)
$cache_base_URL = "";           // [default=""]; if the cache directory is NOT a subdirectory of the directory which contains the script AND it (the cache) is web accessible, provide its URL here for use to display web snapshots. if the cache directory is not a subdirectory of the script's own directory and nothing it provided here, display of web snapshots will not work (all other attachment types are unaffected) 


// library view defaults
$def_ipp = 20;              // [default=20]; items per page
$def_sort = "dateAdded";    // [default="dateAdded"]; The name of the field by which entries are ordered
                            // valid values= "dateAdded", "title", "creator", "type", "date", "publisher", "publication", "journalAbbreviation", "language", "dateModified", "accessDate", "libraryCatalog", "callNumber", "rights", "addedBy", "numItems"
$def_sortorder = "desc";    // [default="desc"]; The sorting direction of the field specified in the order parameter, valid values: "desc", "asc"


// time zone errors - if your script throws errors regarding time zone settings, uncomment (and edit) this line to set the time zone your server is located manually 
// (this is only used to determine cache age, so it doesn't matter too much if it is set wrongly as long as it is set)
// a list of supported time zone strings is here: http://www.php.net/manual/en/timezones.php 
# if (!ini_get('date.timezone')) ini_set('date.timezone', 'America/Toronto');

?>
