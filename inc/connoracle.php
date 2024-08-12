 <?php
  // $objConnect = oci_connect("DEVELOPERS2", "RMLIT2024DEV", "localhost:1521/ORCL");
  // $objConnect = oci_connect("DEVELOPERS", "RMLIT2024DEV", "localhost:1521/ORCL");
  $objConnect=oci_connect("DEVELOPERS","Test1234","10.99.99.20:1525/ORCLPDB",'AL32UTF8');
  $isDatabaseConnected = 1;
  if (!$objConnect) {
    echo 'Failed to connect to Oracle';
  }
  ?>
