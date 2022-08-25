<?php

// CheckAlias | VANDALS MMO
/* Server code to check if the username already as an Alias during login.
This should allow us to have Rename in the shop later on and implement it easier. */

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);

// 1. Establish MySQL connection
$con = ConnectToDatabase();

// 2. Select a specific alias from the database using the username sent from the client 
// and a combination of the permakey and browserkey (also sent from the client).

if($stmt = $con->prepare("SELECT alias FROM members WHERE username = ? and 
	last_sessionkey = concat(permakey, ?)")){
        
    if ($stmt === false) {
        trigger_error($this->mysqli->error, E_USER_ERROR);
        return; 
    }
   
    $stmt->bind_param("ss", $username, $browserkey);
    $stmt->execute();
    $stmt->bind_result($alias);
    
    while ($stmt->fetch()) {
        $alias = $alias;
    }

    $stmt->close;
}

// 3. Verifies if the alias selected is null.

if($alias == null){
    // Server code 296 - No alias for this user (so set one)
    die("296");
} else {
	// Server code 300 - Load character list
    die("300");
}

?>