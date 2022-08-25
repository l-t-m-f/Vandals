<?php 

//GATEKEEPER | VANDALS MMO

/*Gatekeeper keeps out unwanted connexion from accessing .php scripts
Use Enigma (for encryption)*/

include '_ENIGMA.php';

// Log connexion attemps in a file
$my_file = fopen("logs/httporigin-" . date("d.m.y") . ".log", "a") or die();

// Only allow POST type requests
header("Access-Control-Allow-Methods: POST");

$appID = $_GET['APPID'];

// If the request comes with no App ID!
if ($appID == NULL) {
    // Forbidden request: No app ID!
        die("006");  
} else {
            
    // Decrypt the querry to get the AppID
    $decrypted = Enigma::Decrypt($appID, "n6l5wOAvqsYe69OHn6l5wOAvqsYe69OH", 1);
        
    // Verifies against the AppID
    if ($decrypted == 4742177755576614) {
    
        // Allow
        header("Access-Control-Allow-Origin: *");
            
    } else {
        // Forbidden request: Wrong app ID!
        die("005");
    }
}

?>