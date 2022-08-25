<?php 

// Character Encounters core | Vandals MMO

function CharactersEncounters_Init($con, $argPermakey, $argCharHashID, $argNPCHashID) {
	
	/* Initializes an encounter row referencing a character an NPC. */
	
	$npcEncounterStatus = CharactersEncounters_CheckStatus($con, $argPermakey, $argCharHashID, $argNPCHashID);
	
	if ($npcEncounterStatus > 0) {
		
		return "DEBUG ERROR: NPC already initialized for this Character in Db!";
		
	} elseif($npcEncounterStatus == 0) {
	
		// 1. Select lowest "line_weight" corresponding to this npc in the liveref db.
	
		if($stmt = $con->prepare("SELECT MIN(line_weight)
								FROM liveref_npcs_dialoglines
								WHERE npc_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return;
			}
			
			$stmt->bind_param("s", $argNPCHashID);
			$stmt->execute();	
			$stmt->bind_result($dbLineWeight);

			while($stmt->fetch()){
				$lineWeight = $dbLineWeight;
			}

			$stmt->close();
			
		}
		
		// 2. Recover the hashid for the line corresponding to this lowest "line_weight" value.
		
		if($stmt = $con->prepare("SELECT line_hashid
								FROM liveref_npcs_dialoglines
								WHERE npc_hashid = ?, line_weight = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return;
			}
			
			$stmt->bind_param("si", $argNPCHashID, $lineWeight);
			$stmt->execute();	
			$stmt->bind_result($dbLineHashID);

			while($stmt->fetch()){
				$lineHashID = $dbLineHashID;
			}

			$stmt->close();
			
		}
		
		// 3. Insert a new row in the encounters db with the select line (the default line).
		
		if($stmt = $con->prepare("INSERT INTO characters_encounters 
								(permakey, char_hashid, npc_hashid, current_dialogline_hashid)
								VALUES (?, ?, ?, ?)")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return;
			}
			
			$stmt->bind_param("ssss", $argPermakey, $argCharHashID, $argNPCHashID, $lineHashID);
			$stmt->execute();	

			$stmt->close();
			
		}
	}
}

function CharactersEncounters_CheckStatus($con, $argPermakey, $argCharHashID, $argNPCHashID) {
	
	/* Check if there is a row for a Character/NPC combination in the db and return 1 if so.
	Otherwise, return 0. */
	
	if($stmt = $con->prepare("SELECT current_dialogline_hashid 
							FROM characters_encounters 
							WHERE permakey = ?, char_hashid = ?, npc_hashid = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		$stmt->bind_param("sss", $argPermakey, $argCharHashID, $argNPCHashID);
		$stmt->execute();	
		$stmt->store_result();
		
		$resultNumRows = $stmt->num_rows;
		
		$stmt->bind_result($dbCurrentDialogLineHashID);
		
		if($resultNumRows == 0) {
		
			return 0;
			
		} else {
			
			while($stmt->fetch()) {
				return 1;
			}
			
		}
		
		$stmt->free_result();

		$stmt->close();
		
	}
}

function CharactersEncounters_ReturnDialog($con, $argPermakey, $argCharHashID, $argNPCHashID) {
	
	$messageToClient = "";
	$dialogGift = false;
	$dialogEnd = false;
	$cascadeLineHashID = NULL;
	
	if($stmt = $con->prepare("SELECT current_dialogline_hashid 
							FROM characters_encounters 
							WHERE permakey = ?, char_hashid = ?, npc_hashid = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		$stmt->bind_param("sss", $argPermakey, $argCharHashID, $argNPCHashID);
		$stmt->execute();	
		$stmt->bind_result($dbCurrentDialogLineHashID);
		
		while($stmt->fetch()){
			
			$currentDialogLineHashID = $dbCurrentDialogLineHashID;
			
		}
		
		$stmt->close();
		
	}
	
	$lineData = explode("|", CharactersEncounters_ReturnLineData($con, $argNPCHashID, $currentDialogLineHashID));
	
	$messageToClient = $messageToClient . $lineData[0];
	$cascadeLineHashID = $lineData[1];
	
	if($lineData[2] == 1) {
		
		$dialogGift = true;

	} else {
				
		$dialogGift = false;

	}
			
	if ($lineData[3] == 1 ) {
				
		$dialogEnd = true;
				
	} else {
				
		$dialogEnd = false;
				
	}
	
	while ($cascadeLineHashID != NULL) {
		
		$lineData = explode("|", CharactersEncounters_ReturnLineData($con, $argNPCHashID, $cascadeLineHashID));
	
		$messageToClient = $messageToClient . $lineData[0];
		$cascadeLineHashID = $lineData[1];
		
	} 
	
	$choiceData = CharactersEncounters_ReturnChoiceData($con, $currentDialogLineHashID);
	
	$messageToClient = $messageToClient . "|" . $choiceData;
	
	return $messageToClient;
}

function CharactersEncounters_ReturnChoiceData($con, $argLineHashID) {
	
	$choiceData = "";
	
	if($stmt = $con->prepare("SELECT choice_hashid, choice_text, choice_goto_line_hashid
							FROM liveref_npcs_dialogchoices
							WHERE choice_line_hashid = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		$stmt->bind_param("s", $argLineHashID);
		$stmt->execute();	
		$stmt->bind_result($dbChoiceHashID, $dbChoiceText, $dbChoiceGotoLineHashID);
		
		while($stmt->fetch()){
			
			$choiceData = $choiceData . $dbChoiceHashID . "@" . $dbChoiceText . "@" . $dbChoiceGotoLineHashID . "|";
			
		}
		
		$stmt->close();
		
	}
	
	return $choiceData;
	
}

function CharactersEncounters_ReturnLineData($con, $argNPCHashID, $argLineHashID) {
	
	if($stmt = $con->prepare("SELECT line_text, cascade_line_hashid, has_gift, is_end
							FROM liveref_npcs_dialoglines 
							WHERE line_hashid = ?, npc_hashid = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		$stmt->bind_param("ss", $argLineHashID, $argNPCHashID);
		$stmt->execute();	
		$stmt->bind_result($dbLineText, $dbCascadeLineHashID, $dbHasGift, $dbIsEnd);
		
		while($stmt->fetch()){
			
			return $dbLineText . "#" . $dbCascadeLineHashID . "#" . $dbHasGift . "#" . $dbIsEnd . "@" ;
			
		}
		
		$stmt->close();
		
	}
	
	
}


?>