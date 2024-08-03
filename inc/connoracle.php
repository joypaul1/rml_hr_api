 <?php
  $objConnect = oci_connect("DEVELOPERS2", "RMLIT2024DEV", "localhost:1521/ORCL",);
  $isDatabaseConnected = 1;
  if (!$objConnect) {
    echo 'Failed to connect to Oracle';
  }
  ?>
