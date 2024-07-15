 <?php
  //  $objConnect=oci_connect("DEVELOPERS","Test1234","10.99.99.20:1525/ORCLPDB",'AL32UTF8');
   //$objConnect=oci_connect("DEVELOPERS2","Test1234","192.168.172.61:1521/xe");
   $objConnect = oci_connect("DEVELOPERS", "RMLIT2024DEV", "localhost:1521/ORCL",);
   $isDatabaseConnected=1;
    If (!$objConnect){
      echo 'Failed to connect to Oracle'; 

    }
?>
