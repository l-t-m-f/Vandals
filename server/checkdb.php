<?php

function CheckAgainst($cipher) {
        
    $con = mysqli_connect("localhost","vanda842_master","&s*x8ZMr7Cw&","vanda842_gamedb");
            
    // Check connection
    if (mysqli_connect_errno()) {
        die("Failed to connect to MySQL: " . mysqli_connect_error());
    }
        
    $query = mysqli_query($con, "SELECT * FROM banned_requests WHERE request = '".$cipher."'");
    $result = mysqli_fetch_array($query);
    $value = $result[$cipher];
            
    if($result == NULL){
        //No result Found
        
        $status = 0; 
        
        $con->query("INSERT INTO banned_requests (request)
        VALUES ('$cipher')");
            
    } else {
        $status = 1; 
    }
        
    return $status;
            
}
    
?>