<?php

// Monsters - UpdateGroupsPositions | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$leaderPositionComposite = Enigma::Decrypt($_GET['GPC'], $K_BROWSER, 0);

$con = ConnectToDatabase();

if ($leaderPositionComposite == "None") {
	
} else {

	$monsterLeaderTokens = explode('|', $leaderPositionComposite);

	if (isset($monsterLeaderTokens[0])) {
		
		$monsterLeader1 = $monsterLeaderTokens[0];
		
		$monsterLeader1Coordinates = explode('@', $monsterLeader1);
		
		if (isset($monsterLeader1Coordinates[0])) {
			$monsterGroup1HashID = $monsterLeader1Coordinates[0];
		}
		if (isset($monsterLeader1Coordinates[1])) {
			$monsterLeader1SpawnX = $monsterLeader1Coordinates[1];
			settype($monsterLeader1SpawnX, "integer");
		}
		if (isset($monsterLeader1Coordinates[2])) {
			$monsterLeader1SpawnY = $monsterLeader1Coordinates[2];
			settype($monsterLeader1SpawnY, "integer");
		}
			
		Monster_SavePos($con, $monsterGroup1HashID, $monsterLeader1SpawnX, $monsterLeader1SpawnY);
	}


	if (isset($monsterLeaderTokens[1])) {
		
		$monsterLeader2 = $monsterLeaderTokens[1];
		
		$monsterLeader2Coordinates = explode('@', $monsterLeader2);
		
		if (isset($monsterLeader2Coordinates[0])) {
			$monsterGroup2HashID = $monsterLeader2Coordinates[0];
		}
		if (isset($monsterLeader2Coordinates[1])) {
			$monsterLeader2SpawnX = $monsterLeader2Coordinates[1];
			settype($monsterLeader2SpawnX, "integer");
		}
		if (isset($monsterLeader2Coordinates[2])) {
			$monsterLeader2SpawnY = $monsterLeader2Coordinates[2];
			settype($monsterLeader2SpawnY, "integer");
		}

		Monster_SavePos($con, $monsterGroup2HashID, $monsterLeader2SpawnX, $monsterLeader2SpawnY);
	}

	if (isset($monsterLeaderTokens[2])) {
		
		$monsterLeader3 = $monsterLeaderTokens[2];

		$monsterLeader3Coordinates = explode('@', $monsterLeader3);
		
		if (isset($monsterLeader3Coordinates[0])) {
			$monsterGroup3HashID = $monsterLeader3Coordinates[0];
		}
		if (isset($monsterLeader2Coordinates[1])) {
			$monsterLeader3SpawnX = $monsterLeader3Coordinates[1];
			settype($monsterLeader3SpawnX, "integer");
		}
		if (isset($monsterLeader3Coordinates[2])) {
			$monsterLeader3SpawnY = $monsterLeader3Coordinates[2];
			settype($monsterLeader3SpawnY, "integer");
		}

		Monster_SavePos($con, $monsterGroup3HashID, $monsterLeader3SpawnX, $monsterLeader3SpawnY);
		
	}
}

?>