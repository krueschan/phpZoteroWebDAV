phpZoteroWebDAV
===============

Store your Zotero attachments on your own site, even on shared hosting.

Features
--------

- Sync library attachment to any webhosting space that supports PHP (including freely available ones).
This means your attachment data is never stored on computers (clients or servers) that you do not control yourself.
- Access your Zotero library on your own webspace through the zotero.org server API, including sorting, detail view, custom number of items per page etc
- Browse your Zotero collections from any web browser
- View your synced attachments (incl. web snapshots) from any web browser without having to use zotero.org's storage server
- Enjoy complete security with support for HTTPS connections

Installation and Configuration Instructions
-------------------------------------------
http://blog.holz.ca/2011/11/phpzoterowebdav-installation/
http://blog.holz.ca/2011/10/proudly-presenting/


License
-------

phpZoteroWebDAV was originally written by Christian Holz and is licensed under the AGPLv3 license.
Significant updates have been made by:
* fishburn (Real name unknown - https://github.com/fishburn)
* David Dean

phpZoteroWebDAV includes the following third party components:
- The WebDAV server PEAR module written by Hartmut Holzgraefe as well as the PEAR base module, both licensed under the PHP license (http://www.php.net/license/3_01.txt)
- The libZotero class for zotero API connection, released under an unknown open source license (https://github.com/fcheslack/libZotero)
- The zotero.org css style sheet, apparently released under the AGPLv3 license (http://www.gnu.org/licenses/agpl.html))

