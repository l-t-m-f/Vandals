<?php

//EXISTPING | VANDALS BETA

//Requires an AppID to continue
include 'gatekeeper.php';
//Will connect to the DB
include 'config.php';
//Uses keys from the server keychain
include 'keychain.php';

//Decrypts the query (AppID was used in gatekeeper.php)
$browserKey = Enigma::Decrypt($_GET['BrowserKey'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['Username'], $K_USERNAME, 0);
$password = Enigma::Decrypt($_GET['Password'], $K_PASSWORD, 0);

//Establish db connection
$con = ConnectToDatabase();

//Load permakey of Username/Password combination
if($stmt = $con->prepare("SELECT permakey FROM members WHERE username = ? and password = ?")){
            
    if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return;
    }
        
    //Bind parameters and execute statement
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    
    //Fetch results
    $stmt->bind_result($permakey);
        
    while ($stmt->fetch()) {
        $permakey = $permakey;
    }
        
    $stmt->close();
            
}

$sessionKey = $permakey . $browserKey;
$ping = date('YmdHis');
$ping = $ping + 120;

//Load permakey of Username/Password combination
if($stmt = $con->prepare("UPDATE members 
    SET lastPing = ?, conn_status = ? WHERE lastSessionKey = ?")){
            
    if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return;
    }
        
    //Bind parameters and execute statement
    $stmt->bind_param("sis", $ping, $st=1, $sessionKey);
    $stmt->execute();
    
    $stmt->close();
            
}

echo $ping - 120;

mysqli_close($con);

?>