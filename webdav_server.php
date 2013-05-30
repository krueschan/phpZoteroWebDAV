<?php

require 'settings.php';

chdir (dirname(__FILE__) . "/inc");

require 'include.php';
require_once "HTTP/WebDAV/Server/Filesystem.php";
$server = new HTTP_WebDAV_Server_Filesystem();

error_log( dirname( get_real_path( $data_dir ) ) );
error_log( dirname( dirname(__FILE__) . '/' . $data_dir ) );

$server->ServeRequest( dirname( get_real_path( $data_dir ) ) );

?>
