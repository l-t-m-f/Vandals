<?php

// Monsters - UpdateMonsterCipher | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$monsterCipher = Enigma::Decrypt($_GET['MC'], $K_BROWSER, 0);

$con = ConnectToDatabase();


if ($monsterCipher == "None") {
	
} else {
	
	$indexShift = 0;

	$monsterCipherComposite = explode("|", $monsterCipher);

	$groupCount = $monsterCipherComposite[0];

	for($i_Group = 1; $i_Group <= $groupCount ; $i_Group++) {
		
		$groupToken = $monsterCipherComposite[1+$indexShift];
		
		$groupTokenComposite = explode("@", $groupToken);
		
		$groupSize = $groupTokenComposite[0];
		$groupHashID = $groupTokenComposite[1];
		
		$groupPositionComposite = explode("#", $groupTokenComposite[2]);
		
		$spawnX = $groupPositionComposite[0];
		$spawnY = $groupPositionComposite[1];
		
		Monster_SavePos($con, $groupHashID, $spawnX, $spawnY);
		
		$indexShift = $indexShift + 2 + $groupSize;
		
	}

}

?>