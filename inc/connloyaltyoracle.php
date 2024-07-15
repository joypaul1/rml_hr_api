 <?php
  $objConnect = oci_connect("LOYALTY", "LOYALTYP", "10.99.99.20:1525/ORCLPDB", 'AL32UTF8');
  if (!$objConnect)
    echo 'Failed to connect to Oracle';
  ?>