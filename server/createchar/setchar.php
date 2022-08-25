<?php

// SetChar | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$kind = Enigma::Decrypt($_GET['CK'], $K_CREATE, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charCount = Account_CharCount_Return($con, $permakey);

/* Depending on the character kind, initiates the new character with different stats.
 Which are set here : */



if ($kind == "Popples"){
	$hp = 18; $capital = 10; $fury = 0; $lore = 0; $cool = 0; $pulp = 0; $luck = 1; $skill1 = "Tag"; $skill2 = "Dashslash"; 
} elseif ($kind == "Lobmysians"){
	$hp = 15; $capital = 12; $fury = 0; $lore = 2; $cool = 0; $pulp = 0; $luck = 0; $skill1 = "Repell"; $skill2 = "Lullaby"; 
} elseif ($kind == "Molekins"){
	$hp = 20; $capital = 10; $fury = 0; $lore = 0; $cool = 1; $pulp = 0; $luck = 0; $skill1 = "Dashslash"; $skill2 = "Press"; 
} elseif ($kind == "Roxis"){
	$hp = 17; $capital = 10; $fury = 1; $lore = 0; $cool = 0; $pulp = 0; $luck = 0; $skill1 = "Handshake"; $skill2 = "PoisonString"; 
} elseif ($kind == "Droids"){
	$hp = 18; $capital = 10; $fury = 0; $lore = 0; $cool = 0; $pulp = 0; $luck = 0; $skill1 = "OilyPatch"; $skill2 = "Tag"; 
} elseif ($kind == "Nobs"){
	$hp = 22; $capital = 9; $fury = 0; $lore = 0; $cool = 1; $pulp = 1; $luck = 0; $skill1 = "Press"; $skill2 = "Counterspell"; 
} elseif ($kind == "WickerOnes"){
	$hp = 13; $capital = 13; $fury = 1; $lore = 0; $cool = 0; $pulp = 0; $luck = 0; $skill1 = "Laughter"; $skill2 = "Mark"; 
}

if($charCount < 3 && $charCount > 0) {
	
	$charNumber = $charCount;
	
	$activationStatus = Character_CheckIfActive($con, $permakey, $charNumber);
	
	if($activationStatus == 0){
	
		$charHashID = Character_CharHashID_Return($con, $permakey, $charNumber);
	
	} else {
		
		$charNumber = $charNumber + 1;
		
		$charHashID = bin2hex(random_bytes(48));
		Character_AllocateDbRows($con, $permakey, $charHashID, $charNumber);
	}
	
	CharacterSkills_ForgetAll($con, $permakey, $charHashID);
	Character_Attributes_Init($con, $permakey, $charHashID, $kind, $hp, $capital, $fury, $lore, $cool, $pulp, $luck);
	CharacterSkills_Learn($con, $permakey, $charHashID, $skill1);
	CharacterSkills_Learn($con, $permakey, $charHashID, $skill2);
	CharacterSkills_ActiveSkill_Set($con, $permakey, $charHashID, 1, $skill1);
	CharacterSkills_ActiveSkill_Set($con, $permakey, $charHashID, 2, $skill2);
	
} elseif($charCount == 0) {
	
	$charNumber = $charCount + 1;

	$charHashID = bin2hex(random_bytes(48));
		
	Character_AllocateDbRows($con, $permakey, $charHashID, $charNumber);

	CharacterSkills_ForgetAll($con, $permakey, $charHashID);
	Character_Attributes_Init($con, $permakey, $charHashID, $kind, $hp, $capital, $fury, $lore, $cool, $pulp, $luck);
	CharacterSkills_Learn($con, $permakey, $charHashID, $skill1);
	CharacterSkills_Learn($con, $permakey, $charHashID, $skill2);
	CharacterSkills_ActiveSkill_Set($con, $permakey, $charHashID, 1, $skill1);
	CharacterSkills_ActiveSkill_Set($con, $permakey, $charHashID, 2, $skill2);
	
} else {
	
	echo "Error - You've reached the character limit for this account.";
}

// Output

echo Character_AttributeScore_ReturnAll($con, $permakey, $charHashID);

?>