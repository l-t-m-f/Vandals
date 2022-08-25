<?php

// LoadCharacterList | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey);
$charCount = Account_CharCount_Return($con, $permakey);

if ($charCount > 0) {
	
	$charNumber = $charCount;
	$activationStatus = Character_CheckIfActive($con, $permakey, $charNumber);
	
	if($activationStatus == 0){
		
		die("301");
			
	} else {
		die("302");
	}
	
} elseif ($charCount <= 0){
    
    //Server code 301: Go to create a chatacter
    die("301");
    
}

?>