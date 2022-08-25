<?php

/* Char/LoadEncountersStatus | VANDALS MMO



*/

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$NPCHashIDs_Composite = Enigma::Decrypt($_GET['NPCS'], $K_BROWSER, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charHashID = Character_CharHashID_Return($con, $permakey, $charNumber);

$NPCHashIDs_asArray = explode("|", $NPCHashIDs_Composite);

foreach(array_keys($NPCHashIDs_asArray) as $key) {
			
	$currentNPCHashID = $NPCHashIDs_asArray[$key];
			
	$NPCInitStatus = CharactersEncounters_CheckStatus($con, $permakey, $charHashID, $currentNPCHashID);
	
	if($NPCInitStatus == 0) {
		
		CharactersEncounters_Init($con, $permakey, $charHashID, $currentNPCHashID);
		
	}

}

?>