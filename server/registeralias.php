<?php

// RegisterAlias | VANDALS MMO

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';

$noMatch = false;

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$submitAlias = Enigma::Decrypt($_GET['AL'], $K_CREATE, 0);

//Establish db connection
$con = ConnectToDatabase();

if($stmt = $con->prepare("SELECT alias FROM members WHERE alias = ?")){
        
    if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return; 
    }
    
    //Bind parameters and execute statement
    $stmt->bind_param("s", $submitAlias);
    $stmt->execute();
    
    $stmt->store_result();
    
    if($stmt->num_rows > 0) {
    
        //Server error 297: Public name already exists
        die("297");
    
    } else {
        
        $noMatch = true;
        
    }
    
    $stmt->close();
            
}


if($noMatch == true) {

    if($stmt = $con->prepare("UPDATE members
        SET alias = ? WHERE username = ? and last_sessionkey = concat(permakey, ?)")){
            
        if ($stmt === false) {
            trigger_error($this->mysqli->error, E_USER_ERROR);
            return; 
        }
        
        //Bind parameters and execute statement
        $stmt->bind_param("sss", $submitAlias, $username, $browserkey);
        $stmt->execute();
        
        echo "400";
        
        $stmt->close();
                
    }
    
}

?>