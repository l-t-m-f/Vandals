<?php

// RefreshSlots | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charCount = Account_CharCount_Return($con, $permakey);

$data = NULL;

if ($charCount >= 1) {
	
	$data = Character_Load($con, $permakey, 1);
	
} 

if ($charCount >= 2){

	$data = $data . "|" . Character_Load($con, $permakey, 2);
    
}

if ($charCount == 3){

	$data = $data . "|" . Character_Load($con, $permakey, 3);
    
}

$data = $charCount . "|" . $data;

echo $data;

?>