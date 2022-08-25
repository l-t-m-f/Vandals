<?php 

// Monsters core | Vandals MMO

function Monsters_RefreshRespawnTimer($con, $argMapHashID) {
	
	if($stmt = $con->prepare("SELECT monstergr_respawn_timer FROM map_data WHERE map_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
	
		$stmt->bind_param("s", $argMapHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbMonsterGroupRespawnTimer);	
		
		while ($stmt->fetch()) {
			$monsterGroupRespawnTimer = $dbMonsterGroupRespawnTimer;
		}
		
		$stmt->close();
	}
	
	$monsterGroupRespawnTimer = $monsterGroupRespawnTimer - 1;
	
	if ($monsterGroupRespawnTimer <= 0) {
		
		if($stmt = $con->prepare("UPDATE map_data SET monstergr_respawn_timer = monstergr_respawn_max_timer WHERE map_hashid = ?")) {
	
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
		
			$stmt->bind_param("s", $argMapHashID);
			$stmt->execute();
			
			$stmt->close();
		}
		
	} else {
		
		if($stmt = $con->prepare("UPDATE map_data SET monstergr_respawn_timer = monstergr_respawn_timer - 1 WHERE map_hashid = ?")) {
	
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
		
			$stmt->bind_param("s", $argMapHashID);
			$stmt->execute();
			
			$stmt->close();
		}
	
	return $monsterGroupRespawnTimer;
	
	}
}

function Monsters_SpawnState_Return($con, $argMapHashID) {
	
	/* Return an initial indication to the client */
	
	/* update */ $messageToClient = "";

	$monsterGroupCountInfo = explode("|", Monsters_GroupCount_Return($con, $argMapHashID));
	$monsterGroupRespawnTimerInfo = explode("|", Monsters_RespawnTimer_Return($con, $argMapHashID));
	
	$monsterGroupCount = $monsterGroupCountInfo[0]; 
	$monsterGroupMaxCount = $monsterGroupCountInfo[1]; 
	$monsterGroupRespawnTimer = $monsterGroupRespawnTimerInfo[0]; 

	$monsterGroupHashIDsComposite = Monsters_GroupHashID_Return($con, $argMapHashID);
	
	if ($monsterGroupCount == $monsterGroupMaxCount) {

		$spawnState = 0;
			
	} else {

		$spawnState = 1;
	}
	
	/* update */ $messageToClient = $spawnState . "|" . $monsterGroupRespawnTimer . "|" . $monsterGroupCount;
	
	if ($monsterGroupHashIDsComposite == NULL) {
		
	} else {
	
		$monsterGroupHashIDs_asArray = explode("|", $monsterGroupHashIDsComposite);
			
		foreach(array_keys($monsterGroupHashIDs_asArray) as $key) {
				
			$currentMonsterGroupHashID = $monsterGroupHashIDs_asArray[$key];
				
				
			$monsterGroupInfo = Monsters_MonsterGroupInfo_Return($con, $currentMonsterGroupHashID);
			$monsterInfo = Monsters_MonsterMonsterInfo_ReturnMonsterInfo($con, $currentMonsterGroupHashID);
			
			/* update */ $messageToClient = $messageToClient . $monsterGroupInfo . $monsterInfo;
		}
	}
	
	return $messageToClient;
}

function Monsters_GroupCount_Return($con, $argMapHashID) {
	
	if($stmt = $con->prepare("SELECT monstergr_count, monstergr_max_count 
							FROM map_data 
							WHERE map_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMapHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbMonsterGroupCount, $dbMonsterGroupMaxCount);
		
		// Only one row is expected, so we make variables of the results.
		while ($stmt->fetch()) {
			$monsterGroupCount = $dbMonsterGroupCount;
			$monsterGroupMaxCount = $dbMonsterGroupMaxCount;
		}
		
		$stmt->close();
	}
	
	return $monsterGroupCount . "|" . $dbMonsterGroupMaxCount;
	
}

function Monsters_RespawnTimer_Return($con, $argMapHashID) {
	
	if($stmt = $con->prepare("SELECT monstergr_respawn_timer, monstergr_respawn_max_timer
							FROM map_data 
							WHERE map_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMapHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbMonsterGroupRespawnTimer, $dbMonsterGroupRespawnMaxTimer);
		
		// Only one row is expected, so we make variables of the results.
		while ($stmt->fetch()) {
			$monsterGroupRespawnTimer = $dbMonsterGroupRespawnTimer;
			$monsterGroupRespawnMaxTimer = $dbMonsterGroupRespawnMaxTimer;
		}
		
		$stmt->close();
	}
	
	return $monsterGroupRespawnTimer . "|" . $monsterGroupRespawnMaxTimer;
	
}

function Monsters_GroupHashID_Return($con, $argMapHashID) {
	
	$monsterGroupHashIDs = "";
	
	if($stmt = $con->prepare("SELECT monstergr_hashid 
							FROM monster_groups 
							WHERE monstergr_map_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMapHashID);
		$stmt->execute();
				
		$stmt->bind_result($dbMonsterGroupHashID);
				
		while ($stmt->fetch()) {
			$monsterGroupHashIDs = $monsterGroupHashIDs . "|" . $dbMonsterGroupHashID;			
		}
			
		// Remove the last "|" character to eliminate the NULL token.
		//$monsterGroupHashIDs = substr($monsterGroupHashIDs, 0, (strlen($monsterGroupHashIDs)-1));
				
		$stmt->close();
	}
	
	return $monsterGroupHashIDs;
	
}

function Monsters_MonsterGroupInfo_Return($con, $argMonsterGroupHashID) {

	$monsterGroupInfo = "";

	if($stmt = $con->prepare("SELECT monstergr_size, 
							monstergr_pos_x, monstergr_pos_y 
							FROM monster_groups 
							WHERE monstergr_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMonsterGroupHashID);
		$stmt->execute();
				
		$stmt->bind_result($dbMonsterGroupSize, $dbMonsterGroupSpawnX, $dbMonsterGroupSpawnY);
				
		while ($stmt->fetch()) {	
			$monsterGroupInfo = $monsterGroupInfo ."|" . $dbMonsterGroupSize . "@" . 
								$argMonsterGroupHashID . "@" . 
								$dbMonsterGroupSpawnX . "#" . $dbMonsterGroupSpawnY;
		}
				
		$stmt->close();
	}
	
	return $monsterGroupInfo;
}

function Monsters_MonsterMonsterInfo_ReturnMonsterInfo($con, $argMonsterGroupHashID) {
	
	$monsterInfo = "";
	
	if($stmt = $con->prepare("SELECT monster_hashid, 
							monster_name, monster_is_leader, 
							monster_current_health, monster_max_health 
							FROM monsters
							WHERE monstergr_hashid = ?")) {
			
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMonsterGroupHashID);
		$stmt->execute();		
				
		$stmt->bind_result($monsterHashID, $monsterName, $monsterIsLeader, $monsterCurrentHealth, $monsterMaxHealth);

		while($stmt->fetch()) {
			$monsterInfo = $monsterInfo . "|" . $monsterHashID . "@" . 
							$monsterIsLeader . "@" . 
							$monsterName . "@" .
							$monsterCurrentHealth . "#" . $monsterMaxHealth . "@" . 
							-1 . "#" . -1 . "#" . "NULL" . "@" . 
							-1;		
		}
		
		$stmt->close();	
	}
	
	return $monsterInfo;
}

function Monsters_MonsterInfo_Return($con, $argMonsterHashID) {
	
	$monsterInfo = "";
	
	if($stmt = $con->prepare("SELECT monster_hashid, 
							monster_name, monster_is_leader, 
							monster_current_health, monster_max_health 
							FROM monsters
							WHERE monster_hashid = ?")) {
			
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMonsterHashID);
		$stmt->execute();		
				
		$stmt->bind_result($monsterHashID, $monsterName, $monsterIsLeader, $monsterCurrentHealth, $monsterMaxHealth);

		while($stmt->fetch()) {
			$monsterInfo = $monsterInfo . "|" . $monsterHashID . "@" . 
							$monsterIsLeader . "@" . 
							$monsterName . "@" . 
							$monsterCurrentHealth . "#" . $monsterMaxHealth . "@" . 
							-1 . "#" . -1 . "#" . "NULL" . "@" .
							-1;		
		}
		
		$stmt->close();	
	}
	
	return $monsterInfo;
}


function Monsters_Create($con, $argMapHashID, $argGridGraphSpawnX, $argGridGraphSpawnY) {
	
	$monsterGroupInfo = "";
	$monsterInfo = "";
	$messageToClient = "";
	
	$groupCountComposite = explode("|", Monsters_GroupCount_Return($con, $argMapHashID));
	$monsterGroupCount = $groupCountComposite[0];
	$monsterGroupMaxCount = $groupCountComposite[1];
	
	if($monsterGroupCount < $monsterGroupMaxCount) {
		
		$newMonsterGroupHashID = md5(uniqid(rand(), true)); // Generate the MonsterGroup's hashID.
		$monsterGroupCount = $monsterGroupCount + 1; // Increment current count.
		
		if($stmt = $con->prepare("UPDATE map_data SET monstergr_count = ?
			WHERE map_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("is", $monsterGroupCount, $argMapHashID);
			$stmt->execute();
			
			$stmt->close();	
		}
		
		if($stmt = $con->prepare("SELECT map_zone_name FROM map_data 
		WHERE map_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("s", $argMapHashID);
			$stmt->execute();
			$stmt->bind_result($dbMapZoneName);
			
			while ($stmt->fetch()) {
				$mapZoneName = $dbMapZoneName;		
			}
			
			$stmt->close();
		}
		
		if($stmt = $con->prepare("SELECT monster_name
								FROM liveref_monsters 
								WHERE monster_habitat = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("s", $mapZoneName);
			$stmt->execute();
			
			$stmt->bind_result($dbMonsterNameResult);

			$monsterNames = "";

			while($stmt->fetch()) {
				$monsterNames = $monsterNames . $dbMonsterNameResult . "|";
			}
			
			$monsterNames = substr($monsterNames , 0, (strlen($monsterNames)-1));

			//return $monsterNames;
			
			$stmt->close();
		}
		
		// Pick a random name from the selected possibilities.
		
		$monsterNamesArray = explode("|", $monsterNames);
		$randomMonsterKey = array_rand($monsterNamesArray, 1);
		$randomMonsterName = $monsterNamesArray[$randomMonsterKey];
		
		//return $randomMonsterName;
		
		// 8. Using the randomly selected monster name, select the health min and max range
		// as well as the followers min and max range corresponding values. 
		
		if($stmt = $con->prepare("SELECT monster_health_min_range, monster_health_max_range,
								monster_followers_min_range, monster_followers_max_range,
								monster_possible_followers
								FROM liveref_monsters 
								WHERE monster_name = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("s", $randomMonsterName);
			$stmt->execute();
			
			$stmt->bind_result($dbMonsterHealthMinRange, $dbMonsterHealthMaxRange, 
							$dbMonsterFollowersMinRange, $dbMonsterFollowersMaxRange,
							$dbMonsterPossibleFollowers);
			
			while($stmt->fetch()) {
				$monsterHealthMinRange = $dbMonsterHealthMinRange;
				$monsterHealthMaxRange = $dbMonsterHealthMaxRange;
				$monsterFollowerMinRange = $dbMonsterFollowersMinRange;
				$monsterFollowerMaxRange = $dbMonsterFollowersMaxRange;
				$monsterPossibleFollowers = $dbMonsterPossibleFollowers;
			}
			
			$stmt->close();
		}
		
		$monsterFollowersCount = rand($monsterFollowerMinRange, $monsterFollowerMaxRange);
		$monsterHealth = rand($monsterHealthMinRange, $monsterHealthMaxRange);
		
		//return $monsterHealth . "|" . $monsterFollowersCount;
		
		// 10. Save spawn coordinates and follower count into the database and associate it to the monster group's HashID.
		
		if($stmt = $con->prepare("INSERT INTO monster_groups (monstergr_map_hashid, monstergr_hashid, monstergr_size, monstergr_pos_x, monstergr_pos_y)
			VALUES (?, ?, ?, ?, ?)")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ssiii", $argMapHashID, $newMonsterGroupHashID, $monsterFollowersCount, $argGridGraphSpawnX, $argGridGraphSpawnY);
			$stmt->execute();
			
			$stmt->close();	
		}
		
		// 11. Insert a new monster into the database linked to the monsterGroup HashID, set its health and make it the leader of its monster group.
		
		if($stmt = $con->prepare("INSERT INTO monsters (monstergr_hashid, monster_hashid, monster_name, monster_is_leader, monster_current_health, monster_max_health)
			VALUES (?, ?, ?, ?, ?, ?)")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$monsterHashID = md5(uniqid(rand(), true));
			
			$l=1;
			
			$stmt->bind_param("sssiii", $newMonsterGroupHashID, $monsterHashID, $randomMonsterName, $l, $monsterHealth, $monsterHealth);
			$stmt->execute();
			
			$stmt->close();	
		}
		
		
		// 12. If any follower is part of the monster group, add them to the database as well.

		for ($i = 0; $i < $monsterFollowersCount; $i++) {
				
			// 12b. To do so, pick a possible follower and give it a random SubID.
				
			$monsterPossibleFollowers_asArray = explode("|", $monsterPossibleFollowers);
			$randomMonsterFollowerKey = array_rand($monsterPossibleFollowers_asArray, 1);
			$randomFollowerName = $monsterPossibleFollowers_asArray[$randomMonsterFollowerKey];
			
			//return $randomFollowerName;
			
			
			// 12c. Load the health range
				
			if($stmt = $con->prepare("SELECT monster_health_min_range, monster_health_max_range
								FROM liveref_monsters 
								WHERE monster_name = ?")) {
			
				$stmt->bind_param("s", $randomFollowerName);
				$stmt->execute();
					
				$stmt->bind_result($dbMonsterFollowerHealthMinRange, $dbMonsterFollowerHealthMaxRange);
				
				while($stmt->fetch()) {
					$monsterFollowerHealthMinRange = $dbMonsterFollowerHealthMinRange;
					$monsterFollowerHealthMaxRange = $dbMonsterFollowerHealthMaxRange;
				}
				
				$stmt->close();
			}
				
			$monsterFollowerHealth = rand($monsterFollowerHealthMinRange, $monsterFollowerHealthMaxRange);
			
			if($stmt = $con->prepare("INSERT INTO monsters (monstergr_hashid, monster_hashid, monster_name, monster_is_leader, monster_current_health, monster_max_health)
									VALUES (?, ?, ?, ?, ?, ?)")) {

				if ($stmt === false) {
					trigger_error($this->mysqli->error, E_USER_ERROR);
					return; 
				}
				
								
				$monsterFollowerHashID = md5(uniqid(rand(), true));
				$l=0;
				
				$stmt->bind_param("sssiii", $newMonsterGroupHashID, $monsterFollowerHashID, $randomFollowerName, $l, $monsterFollowerHealth, $monsterFollowerHealth);
				$stmt->execute();
						
				$stmt->close();	
			}
			
		}
		
		$monsterGroupInfo = Monsters_MonsterGroupInfo_Return($con, $newMonsterGroupHashID);
		$monsterInfo = Monsters_MonsterMonsterInfo_ReturnMonsterInfo($con, $newMonsterGroupHashID);
			
		/* update */ $messageToClient = $messageToClient . $monsterGroupInfo . $monsterInfo;

	return substr($messageToClient, 1, strlen($messageToClient)-1);
	
	} 
	
	else {
		
		return "MAX";
		
	}
}

function Monsters_Damage($con, $argMapHashID, $argMonsterGroupHashID, $argMonsterHashID, $argDamage) {
	
	/* Function to deal a set amount of damage (int argDamage) to a specified monster.
	If the damage reduces the monster to 0 current Health, destroy it. If it has followers,
	they are also destroyed (not present on next load pf the map by the Photon host).*/
	
	// Initialize the variables that will later define the Function's resulting effect
	$destroyMonster = false;
	$destroyLeader = false;
	
	// Check current status of targeted monster
	if($stmt = $con->prepare("SELECT monster_is_leader, monster_current_health 
							FROM monsters 
							WHERE monstergr_hashid = ? and monster_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argMonsterGroupHashID, $argMonsterHashID);
		$stmt->execute();		
		
		$stmt->bind_result($dbMonsterIsLeader, $dbMonsterCurrentHealth);	
		
		while ($stmt->fetch()) {
			if (($dbMonsterCurrentHealth - $argDamage) <= 0) {
				
				$destroyMonster = true;
				
				if ($dbMonsterIsLeader == 1) {
					$destroyLeader = true;
				}	
			}
		}
			
		$stmt->close();	
	}
	
	if ($destroyMonster == true) {
			// Case A: Destroying a MonsterLeader : Destroy all followers for the next Host loading the map (if any).
			
		if($stmt = $con->prepare("DELETE FROM monsters 
								WHERE monster_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("s", $argMonsterHashID);
			$stmt->execute();							
			$stmt->close();	
		}
			
		if($stmt = $con->prepare("SELECT monstergr_size
								FROM monster_groups
								WHERE monstergr_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("s", $argMonsterGroupHashID);
			$stmt->execute();		
				
			$stmt->bind_result($dbMonsterGroupSize);	
				
			while ($stmt->fetch()) {
				$monsterGroupSize = $dbMonsterGroupSize;
			}
					
			$stmt->close();	
		}
			
		if($monsterGroupSize > 0) {

			if($stmt = $con->prepare("UPDATE monster_groups
									SET monstergr_size = monstergr_size - 1
									WHERE monstergr_hashid = ?")) {

				if ($stmt === false) {
					trigger_error($this->mysqli->error, E_USER_ERROR);
					return; 
				}
					
				$stmt->bind_param("s", $argMonsterGroupHashID);
				$stmt->execute();							
				$stmt->close();	
			}
				
			if ($destroyLeader == true) {
					
				if($stmt = $con->prepare("SELECT monster_hashid
										FROM monsters
										WHERE monstergr_hashid = ?
										ORDER BY RAND()
										LIMIT 1")) {
												
					if ($stmt === false) {
						trigger_error($this->mysqli->error, E_USER_ERROR);
						return; 
					}

					$stmt->bind_param("s", $argMonsterGroupHashID);
					$stmt->execute();		
							
					$stmt->bind_result($dbMonsterHashID);	
							
					while ($stmt->fetch()) {
							$promotedMonsterHashID = $dbMonsterHashID;
					}
								
					$stmt->close();					
				}
					
				if($stmt = $con->prepare("UPDATE monsters
										SET monster_is_leader = 1
										WHERE monster_hashid = ?")) {
												
					if ($stmt === false) {
						trigger_error($this->mysqli->error, E_USER_ERROR);
						return; 
					}

					$stmt->bind_param("s", $promotedMonsterHashID);
					$stmt->execute();		
					$stmt->close();					
						
						
					return 1 . "|" . $argMonsterHashID . "|" . $promotedMonsterHashID;
				}
			}
		} else {
			if($stmt = $con->prepare("UPDATE map_data 
									SET monstergr_count = monstergr_count - 1, monstergr_respawn_timer = monstergr_respawn_timer
									WHERE map_hashid = ? and monstergr_count > 0")) {

				if ($stmt === false) {
					trigger_error($this->mysqli->error, E_USER_ERROR);
					return; 
				}

				$stmt->bind_param("s", $argMapHashID);
				$stmt->execute();							
				$stmt->close();	
			}
				
			if($stmt = $con->prepare("DELETE FROM monster_groups 
									WHERE monstergr_hashid = ?")) {

				if ($stmt === false) {
					trigger_error($this->mysqli->error, E_USER_ERROR);
					return; 
				}

				$stmt->bind_param("s", $argMonsterGroupHashID);
				$stmt->execute();							
				$stmt->close();	
			}
				
			return 2 . "|" . $argMonsterHashID;
		}
	} else {
		
			// Case C: On deal damage
			
		if($stmt = $con->prepare("UPDATE monsters 
								SET monster_current_health = monster_current_health - ?
								WHERE monstergr_hashid = ? and monster_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("iss", $argDamage, $argMonsterGroupHashID, $argMonsterHashID);
			$stmt->execute();
					
			$stmt->close();	
		}
			
		if($stmt = $con->prepare("SELECT monster_current_health 
								FROM monsters WHERE monstergr_hashid = ? and monster_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argMonsterGroupHashID, $argMonsterHashID);
			$stmt->execute();		
				
			$stmt->bind_result($dbMonsterCurrentHealth);	
				
			while ($stmt->fetch()) {	
				return 0 . "|" . $argMonsterHashID . "|" .$dbMonsterCurrentHealth;
			}
					
			$stmt->close();	
		}		
	}
}

function Monster_SavePos($con, $argMonsterGroupHashID, $argSpawnX, $argSpawnY) {

	if($stmt = $con->prepare("UPDATE monster_groups SET monstergr_pos_x = ?, monstergr_pos_y = ?
		WHERE monstergr_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("iis", $argSpawnX, $argSpawnY, $argMonsterGroupHashID);
		$stmt->execute();
			
		$stmt->close();	
	}
}

?>