<?php
$objConnect = oci_connect("RESALE", "resale", "10.99.99.20:1525/ORCLPDB", 'AL32UTF8');
if (!$objConnect)
  echo 'Failed to connect to Oracle';

?>