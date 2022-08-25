<?php 

function ConnectToDatabase() {
    
    $con=mysqli_connect("localhost","vanda842_master","&s*x8ZMr7Cw&","vanda842_gamedb");
    
    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
      
    return $con;
    
}

?>