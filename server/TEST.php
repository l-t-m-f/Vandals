<?php

// TEST | VANDALS MMO

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';
include '_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$charNumber = $_GET['CS'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charHashID = Character_CharHashID_Return($con, $permakey, $charNumber);

$spellData = CharacterSkills_SkillLevel_ReturnAll($con, $permakey, $charHashID);

$secondK = array();

foreach($spellData as $sub) {
    $secondK = array_merge($secondK, $sub);
}        

foreach ($spellData as $row) { 

	foreach ($row as $index=>$element) {
			
		if ($element > 0 && $element < 101 && $index != "id" && $index != "char_hashid") {
			
			echo $index . "€" . $element . "|";
		}
	}
}

?>