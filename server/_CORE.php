<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '_ENC.php';
include '_MON.php';

// CORE | VANDALS MMO

// OTHER FUNCTIONS

function Load_News($con) {
	
	if($stmt = $con->prepare("SELECT MAX(id) FROM latest_news")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}

		$stmt->execute();
		$stmt->bind_result($dbMaxID);
		
		while ($stmt->fetch()) {
			$maxID = $dbMaxID;
		}
		
	$stmt->close();
	
	}
	
	if($stmt = $con->prepare("SELECT news_p1, news_p2, news_p3 FROM latest_news WHERE id = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}

		$stmt->bind_param("i", $maxID);
		$stmt->execute();
		$stmt->bind_result($dbNewsP1, $dbNewsP2, $dbNewsP3);
		
		while ($stmt->fetch()) {
			return $dbNewsP1 . "|" . $dbNewsP2 . "|" . $dbNewsP3;
		}
		
	$stmt->close();
	
	}
}


// ACCOUNT FUNCTIONS

// Higher functions

function Account_Create($con, $argUsername, $argPasswordCryptogram, $argIRLName, $argEmail, $argPhone, $argPromo) {
	
	if($stmt = $con->prepare("INSERT INTO members (username, password, owner, email, phone, promo) VALUES (?, ?, ?, ?, ?, ?)")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
			
		/* This function records a new account. The account won't have a permakey/browserkey pair yet.
		These are set during connexion. */
		
		$stmt->bind_param("sssssi", $argUsername, $argPasswordCryptogram, $argIRLName, $argEmail, $argPhone, $argPromo);
		$stmt->execute();
		$stmt->close();
		
	}
}

function Account_CompleteLogin($con, $argUsername, $argPasswordCryptogram, $argLastSessionkey) {

	if($stmt = $con->prepare("SELECT last_sessionkey FROM members WHERE username = ? and password = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		/* This functions retrieves the last_sessionkey from the database and compares it with 
		the one submitted in argument (the local version). If the comparison is true, then the login authentification is
		completed and the conn_status is set to 1. (Also triggers login in client.) */

		$stmt->bind_param("ss", $argUsername, $argPasswordCryptogram);
		$stmt->execute();
		$stmt->bind_result($dbLastSessionkey);

		while ($stmt->fetch()) {
			if($dbLastSessionkey == $argLastSessionkey) {
				
				Account_ConnStatus_Set($con, $argUsername, $argPasswordCryptogram, 1);
				
				echo "1";
				
			} else {
				
				echo "0";
			}    
		}

		$stmt->close();
	}
}

function Account_PopSave($con, $argPermakey, $argSlotID) {
	
	$charHashID = Character_CharHashID_Return($con, $argPermakey, $argSlotID);
	
	if($charHashID != NULL) {
	
		Character_Delete($con, $argPermakey, $charHashID);
		
		if ($argSlotID < 2) {
			Character_ChangeCharNumber($con, $argPermakey, 2);		
		}
		
		if ($argSlotID < 3) {
			Character_ChangeCharNumber($con, $argPermakey, 3);			
		
		}
	}
}

// ConnStatus

function Account_ConnStatus_Return($con, $argUsername) {
	
	if($stmt = $con->prepare("SELECT conn_status FROM members WHERE username = ?")){
        
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
    
		/*  This function retrieves the conn_status value 
		for a specific username. (0 = not connected ; 1 = connected ; 2 = merchant mode)*/
	
		$stmt->bind_param("s", $argUsername);
		$stmt->execute();
		$stmt->bind_result($dbConnStatus);
    
		while ($stmt->fetch()) {
			return $dbConnStatus;
		}

		$stmt->close();      
	}
}

function Account_ConnStatus_Set($con, $argUsername, $argPasswordCryptogram, $argConnStatus) {
	
	if($stmt = $con->prepare("UPDATE members SET conn_status = ? WHERE username = ? and password = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
        
		/* This function updates the conn_status value for a specific username/password
		(at login). */
		
		$stmt->bind_param("iss", $argConnStatus, $argUsername, $argPasswordCryptogram);
		$stmt->execute();
        
		$stmt->close();
	}
}


// Username
	
function Account_Username_CheckIfExists($con, $argUsername) {

	if($stmt = $con->prepare("SELECT username FROM members WHERE username = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		/* This function verifies if a username exists by returning 0 if no row for this Username 
		is found in the database. Otherwise, return 1. */
		
		$stmt->bind_param("s", $argUsername);
		$stmt->execute();	
		$stmt->store_result();
		$resultNumRows = $stmt->num_rows;
		$stmt->free_result();

		$stmt->close();
		
	}
	
	if($resultNumRows == 0) {
		return 0;
	} else {
		return 1;
	}
}

// Password

function Account_PasswordCryptogram_Return($con, $argUsername) {

	if($stmt = $con->prepare("SELECT password FROM members WHERE username = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		     
		// This function returns the password cryptogram associated with a username from the database. 
		// This cryptogram is encoded with K_CREATE and should never be encoded with K_PASSWORD, which
		// is the key used to send the password during the login.
		
		$stmt->bind_param("s", $argUsername);
		$stmt->execute();
		$stmt->bind_result($dbPasswordCryptogram);
        
		while ($stmt->fetch()) {
			return $dbPasswordCryptogram;
		}
	 
		$stmt->close();     
    }
}

function Account_Password_CheckAgainstCrypto($con, $argUsername, $argPassword, $argKey) {

	/* This function compares a decrypted password with the database's password.
	Uses decryption on the dbPassword during this process. */

	$passwordCryptogram = Account_PasswordCryptogram_Return($con, $argUsername);

	$dbPassword = Enigma::Decrypt($passwordCryptogram, $argKey, 0);

	if ($dbPassword == $argPassword) {
		return 1;
	} else {
		return 0;
	}
}

// Permakey

function Account_Permakey_Set($con, $argUsername, $argPasswordCryptogram, $argPermakey) {

	if($stmt = $con->prepare("UPDATE members SET permakey = ? WHERE username = ? and password = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		/* This function sets the permakey for a specific username/password combination.
		The permakey is known by the server and can be used with the browserkey from the client
		to authentify a user with his sessionkey (which is = to permakey + browserkey). */
            
		$stmt->bind_param("sss", $argPermakey, $argUsername, $argPasswordCryptogram);
		$stmt->execute();
        
		$stmt->close();     
	}
}

function Account_Permakey_Return_risky($con, $argUsername, $argPasswordCryptogram) {

	if($stmt = $con->prepare("SELECT permakey FROM members WHERE username = ? and password = ?")){
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		
		/* This function retrieves a permakey from a username / password combination.
		It is not "safe" since we transit confidential information to get the permakey. In most game processes
		we will be using the SafeRetrive function instead which only requires the browserkey to
		get the server to figure out the proper sessionkey. */

		$stmt->bind_param("ss", $argUsername, $argPasswordCryptogram);
		$stmt->execute();
		$stmt->bind_result($dbPermakey);

		while ($stmt->fetch()) {
			return $dbPermakey;
		}	

		$stmt->close();
	}
}

function Account_Permakey_Return($con, $argBrowserkey) {

	if ($stmt = $con->prepare("SELECT permakey FROM members WHERE last_sessionkey = CONCAT(permakey, ?)")){
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}

		/* This is the magnificient SafeRetrieve function. It uses the secret permakey (always stays server-side) in association with a browserkey (short token)
		and this can determine which sessionkey corresponds to our user. Then the server picks the permakey component to interact 
		in the database. Often permakey and hashid allow to affect a specific character of the same user, since users can have more
		than 1 character. */

		$stmt->bind_param("s", $argBrowserkey);
		$stmt->execute();
		$stmt->bind_result($dbPermakey);

		while ($stmt->fetch()) {
			return $dbPermakey;
		}	

		$stmt->close();
	}
}

// Last Sessionkey

function Account_LastSessionkey_Set($con, $argUsername, $argPasswordCryptogram, $argLastSessionkey) { 

	if($stmt = $con->prepare("UPDATE members SET last_sessionkey = ? WHERE username = ? and password = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		} 

		/* This function updates the sessionkey with a new one. 
		(Only changes the last 16 chars which are the browserkey used for connecting the client) */

		$stmt->bind_param("sss", $argLastSessionkey, $argUsername, $argPasswordCryptogram);
		$stmt->execute();
        
		$stmt->close();
	}
}


// CharCount

function Account_CharCount_Return($con, $argPermakey) {

	if($stmt = $con->prepare("SELECT char_count FROM members WHERE permakey = ?")){
        
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argPermakey);
		$stmt->execute();
		$stmt->bind_result($dbCharCount);
    
		while ($stmt->fetch()) {
			return $dbCharCount;
		}

		$stmt->close();
	}
}

function Account_CharCount_Set($con, $argPermakey, $argChangeValue) {
	
	if($stmt = $con->prepare("UPDATE members SET char_count = char_count + ? WHERE permakey = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		} 

		$stmt->bind_param("is", $argChangeValue, $argPermakey);
		$stmt->execute();
        
		$stmt->close();
	}
}


// CHARACTERS FUNCTIONS

// Higher functions

function Character_AllocateDbRows($con, $argPermakey, $argCharHashID, $argCharNumber) {
	
	if($stmt = $con->prepare("INSERT INTO characters (char_number, permakey, char_hashid) VALUES (?, ?, ?)")) {
            
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
        
		$stmt->bind_param("iss", $argCharNumber, $argPermakey, $argCharHashID);
		$stmt->execute();
        
		$stmt->close();   
	}
	
	if($stmt = $con->prepare("INSERT INTO characters_skills (permakey, char_hashid) VALUES (?, ?)")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		  
		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
					   
		$stmt->close(); 

	}
	
	if($stmt = $con->prepare("INSERT INTO characters_inv (permakey, char_hashid) VALUES (?, ?)")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return;
		}
		  
		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
					   
		$stmt->close(); 

	}
	
	Account_CharCount_Set($con, $argPermakey, 1);
}

function Character_Create($con, $argPermakey, $argCharHashID, $argName) {
	
	if($stmt = $con->prepare("UPDATE characters SET active = 1 WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
	
	if($stmt = $con->prepare("UPDATE characters SET name = ? WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("sss", $argName, $argPermakey, $argCharHashID);
		$stmt->execute();

		echo "500";

		$stmt->close();
	}
	
}

function Character_Load($con, $argPermakey, $argSlotID) {
	
	$charHashID = Character_CharHashID_Return($con, $argPermakey, $argSlotID);
	
	return Character_RetrieveData_Slot($con, $argPermakey, $charHashID);
	
}

function Character_Delete($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("DELETE FROM characters WHERE permakey = ? and char_hashid = ?")) {
		
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
	
		if($stmt = $con->prepare("DELETE FROM characters_inv WHERE permakey = ? and char_hashid = ?")) {
		
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
	
		if($stmt = $con->prepare("DELETE FROM characters_skills WHERE permakey = ? and char_hashid = ?")) {
		
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
	
		if($stmt = $con->prepare("UPDATE members SET char_count = char_count - 1 WHERE permakey = ?")) {
		
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argPermakey);
		$stmt->execute();

		$stmt->close();
	}

}

function Character_Data_Set($con, $argPermakey, $argCharHashID, $argCharCipher) {
	
	$cipherTokenRank1 = explode('|', $argCharCipher);
	$charHashID = $cipherTokenRank1[0];
	$charName = $cipherTokenRank1[1];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[2]);
	$charHashID = $cipherTokenRank2[0];
	$cipherTokenRank3 = explode('@', $cipherTokenRank2[1]);
	$charX = $cipherTokenRank3[0];
	$charY = $cipherTokenRank3[1];
	$cipherTokenRank3 = explode('@', $cipherTokenRank2[2]);
	$charMoveDir = $cipherTokenRank3[1];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[3]);
	$charKind = $cipherTokenRank2[0];
	$charKind = $cipherTokenRank2[1];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[4]);
	$charLevel = $cipherTokenRank2[0];
	$charXP = $cipherTokenRank2[1];
	$charNextXPCap = $cipherTokenRank2[3];
	$charXPRatio = $cipherTokenRank2[4];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[5]);
	$charHex1 = $cipherTokenRank2[0];
	$charHex2 = $cipherTokenRank2[1];
	$charHex3 = $cipherTokenRank2[3];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[6]);
	$charCurrentHealth = $cipherTokenRank2[0];
	$charHealthScore = $cipherTokenRank2[1];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[7]);
	$charCurrentEnergy = $cipherTokenRank2[0];
	$charEnergyScore = $cipherTokenRank2[1];
	$cipherTokenRank2 = explode('@', $cipherTokenRank1[8]);
	$charFuryScore = $cipherTokenRank2[0];
	$charLoreScore = $cipherTokenRank2[1];
	$charCoolScore = $cipherTokenRank2[2];
	$charPulpScore = $cipherTokenRank2[3];
	$charLuckScore = $cipherTokenRank2[4];
	
	
	
	
}

function Character_Data_ReturnAll($con, $argPermakey, $argCharHashID) {
	
	$returnString = $argCharHashID;
	
	$returnString = $returnString . "|" . Character_Data_ReturnLocalData($con, $argPermakey, $argCharHashID);
	$returnString = $returnString . "|" . Character_Data_ReturnStats($con, $argPermakey, $argCharHashID);
	$returnString = $returnString . "|" . CharacterSkills_ReturnActiveSkills($con, $argPermakey, $argCharHashID);
	
	return $returnString;
	
}

function Character_Data_ReturnLocalData($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT name, current_map_hashid,
							x, y,
							last_move_dir,
							kind, cradle
							FROM characters 
							WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbCharName, $dbCharCurrentMapHashID,
							$dbCharX, $dbCharY,
							$dbCharLastMoveDir,
							$dbKind, $dbCradle);

		while ($stmt->fetch()) {
			
			$dbCharMoveDir = $dbCharLastMoveDir;
			
			return $dbCharName . "|" . $dbCharCurrentMapHashID . "@" . 
					$dbCharX . "#" . $dbCharY . "@" . 
					$dbCharMoveDir . "#" . $dbCharLastMoveDir . "|" . 
					$dbKind . "@" . $dbCradle;
		}

		$stmt->close();
	}	
	
}

function Character_Data_ReturnStats($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT lvl, next_xp_cap, xp, xp_ratio,
							hex1, hex2, hex3, 
							current_hp, current_energy,
							att_health, att_energy,
							free_capital,
							att_fury, att_lore, att_cool, att_pulp, att_luck
							FROM characters
							WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbCharLevel, $dbCharNextXPCap, $charXP, $charXPRatio,
							$dbCharHex1, $dbCharHex2, $dbCharHex3,
							$dbCharCurrentHp, $dbCharCurrentEnergy,
							$dbCharHealthScore, $dbCharEnergyScore,
							$dbCharFreeCapital,
							$dbCharFuryScore, $dbCharLoreScore, $dbCharCoolScore, $dbCharPulpScore, $dbCharLuckScore);

		while ($stmt->fetch()) {
			return $dbCharLevel . "@" . $dbCharNextXPCap . "@" . $charXP . "@" . $charXPRatio . "|" . 
							$dbCharHex1 . "@" . $dbCharHex2 . "@" . $dbCharHex3 . "|" . 
							$dbCharCurrentHp . "@" . $dbCharHealthScore . "|" . 
							$dbCharCurrentEnergy . "@" . $dbCharEnergyScore . "|" . 
							$dbCharFreeCapital . "|" . 
							$dbCharFuryScore . "@" . $dbCharLoreScore . "@" . $dbCharCoolScore . "@" . $dbCharPulpScore . "@" . $dbCharLuckScore;
		}

		$stmt->close();
	}	
	
}

function Character_Data_ReturnSecondaryStats() {
	
	
}

function CharacterSkills_ReturnActiveSkills($con, $argPermakey, $argCharHashID) {
	
	$returnString = "";
	
	if($stmt = $con->prepare("SELECT active_skill1, 
							active_skill2, 
							active_skill3, 
							active_skill4,
							active_skill5, 
							active_skill6, 
							active_skill7, 
							active_skill8,
							active_skill9
							FROM characters 
							WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbActiveSkill1, $dbActiveSkill2, $dbActiveSkill3, $dbActiveSkill4, $dbActiveSkill5, 
							$dbActiveSkill6, $dbActiveSkill7, $dbActiveSkill8, $dbActiveSkill9);

		while ($stmt->fetch()) {
			$activeSkills = $dbActiveSkill1 . "@" .  $dbActiveSkill2 . "@" . $dbActiveSkill3 . "@" . $dbActiveSkill4 . "@" . $dbActiveSkill5 . "@" . 
			$dbActiveSkill6 . "@" . $dbActiveSkill7 . "@" . $dbActiveSkill8 . "@" . $dbActiveSkill9;

		}

		$stmt->close();
	}	
	
	$activeSkills = explode('@', $activeSkills);
	
	$level = -1;
	
	foreach ($activeSkills as $index=>$skill) {
		
		if ($skill != null) {
		
			if($stmt = $con->prepare("SELECT " . $skill . " FROM characters_skills WHERE permakey = ? and char_hashid = ?")) {
		
				if ($stmt === false) {
					trigger_error($this->mysqli->error, E_USER_ERROR);
					return; 
				}

				$stmt->bind_param("ss", $argPermakey, $argCharHashID);
				$stmt->execute();
				
				$stmt->bind_result($dbSkillLevel);
				
				while ($stmt->fetch()) {

					$returnString = $returnString . ($index+1) . "#" . $skill . "#" . $dbSkillLevel . "@";	

				}
				
				$stmt->close();
			}		
			
		}
		
	}
	
	$returnString = substr($returnString, 0, (strlen($returnString) - 1));
	
	return $returnString;
	
}

function CharacterSkills_ReturnSkillbook() {
	
	
}


function CharacterInventory_ReturnContent() {
	
	
}

// Activation status

function Character_CheckIfActive($con, $argPermakey, $argCharNumber) {
	
	if($stmt = $con->prepare("SELECT active FROM characters WHERE permakey = ? and char_number = ?")) {
        
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
    
		$stmt->bind_param("si", $argPermakey, $argCharNumber);
		$stmt->execute();
    
		$stmt->bind_result($dbActivationStatus);
   
		while ($stmt->fetch()) {
			return $dbActivationStatus;
		}
    
		$stmt->close();
	}
}

// CharNumber

function Character_CharNumber_Set($con, $argPermakey, $argCharNumber) {
	
	if($stmt = $con->prepare("UPDATE characters SET char_number = char_number - 1 WHERE permakey = ? and char_number = ?")) {
		
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("si", $argPermakey, $argCharNumber);
		$stmt->execute();

	$stmt->close();

	}
}

// CharHashID

function Character_CharHashID_Return($con, $argPermakey, $argCharNumber) {

	if($stmt = $con->prepare("SELECT char_hashid FROM characters WHERE permakey = ? and char_number = ?")){
        
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
		
		/* This function retrieves a char_hashid corresponding to a permakey/char_count (integer)
		combination */

		$stmt->bind_param("si", $argPermakey, $argCharNumber);
		$stmt->execute();
		$stmt->bind_result($dbCharHashID);
   
		while ($stmt->fetch()) {
			return $dbCharHashID;
		}
		$stmt->close();
	}
}

// Xp

function Character_Xp_Return($con, $argPermakey, $argCharHashID) {
	
}

function Character_Xp_Set($con, $argPermakey, $argCharHashID, $XpModifier) {
	
	
}

function Character_XpRatio_Return($con, $argPermakey, $argCharHashID) { }

function Character_XpRatio_Set($con, $argPermakey, $argCharHashID, $argXpRatioModifier) {

	if($stmt = $con->prepare("UPDATE characters SET xp_ratio = xp_ratio + ? WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("iss", $argXpRatioModifier, $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
}

// Kind

function Character_Kind_Return($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT kind FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
		
		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		$stmt->bind_result($dbKind);
		
		while ($stmt->fetch()) {
			return $dbKind;
		}
		
		$stmt->close();
	}
}

// Attributes

function Character_Attributes_Init($con, $argPermakey, $argCharHashID, $argKind, $argHealth, $argCapital, $argFury, $argLore, $argCool, $argPulp, $argLuck) {

	if($stmt = $con->prepare("UPDATE characters SET kind = ?, att_health = ?, current_hp = ?, free_capital = ?, att_fury = ?, att_lore = ?, att_cool = ?, att_pulp = ?, att_luck = ?
	WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("siiiiiiiiss", 
			$argKind, $argHealth, $argHealth, $argCapital,
			$argFury, $argLore, $argCool, $argPulp,
			$argLuck, $argPermakey, $argCharHashID);
			
		$stmt->execute();

		$stmt->close();

	}
}

function Character_StartingAttributeScore_Set($con, $argPermakey, $argCharHashID, $argAttribute, $argKind, $argChangeValue) {

	/* This function changes a specified attribute value corresponding to a permakey/char_hashid match.
	The change can be negative or positive, both are taken into account with this function.
	The change is permanent to the value, as opposed to temporary debuff to stats. */

	if($argChangeValue == 1) {
		if($argAttribute == "Health") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Health"));
			$remainingCapital = $parts[0];
			$healthScore = $parts[1];

			if ($remainingCapital > 0 && $healthScore < 25) {

				if($stmt = $con->prepare("UPDATE characters SET att_health = att_health + ?, current_hp = current_hp + ?, 
				free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

					if ($stmt === false) {
						trigger_error($this->mysqli->error, E_USER_ERROR);
						return; 
					}

					$stmt->bind_param("iiiss", $argChangeValue, $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
					$stmt->execute();

					$stmt->close();
				}
			}
		}
		elseif ($argAttribute == "Fury") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Fury"));
			$remainingCapital = $parts[0];
			$furyScore = $parts[1];

			if ($argKind == "Roxis" || $argKind == "WickerOnes") {
				if ($remainingCapital > 0 && $furyScore < 6) {

					if($stmt = $con->prepare("UPDATE characters SET att_fury = att_fury + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			} else {
				if ($remainingCapital > 0 && $furyScore < 5) {

					if($stmt = $con->prepare("UPDATE characters SET att_fury = att_fury + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			}
		}
		elseif ($argAttribute == "Lore") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Lore"));
			$remainingCapital = $parts[0];
			$loreScore = $parts[1];

			if ($argKind == "Lobmysians") {
				if ($remainingCapital > 0 && $loreScore < 7) {

					if($stmt = $con->prepare("UPDATE characters SET att_lore = att_lore + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			} else {
				if ($remainingCapital > 0 && $loreScore < 5) {

					if($stmt = $con->prepare("UPDATE characters SET att_lore = att_lore + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			}
		}
		elseif ($argAttribute == "Cool") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Cool"));
			$remainingCapital = $parts[0];
			$coolScore = $parts[1];

			if ($argKind == "Molekins" || $argKind == "Nobs") {
				if ($remainingCapital > 0 && $coolScore < 6) {
					if($stmt = $con->prepare("UPDATE characters SET att_cool = att_cool + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($remainingCapital > 0 && $coolScore < 5) {
					if($stmt = $con->prepare("UPDATE characters SET att_cool = att_cool + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
		elseif ($argAttribute == "Pulp") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Pulp"));
			$remainingCapital = $parts[0];
			$pulpScore = $parts[1];

			if ($argKind == "Nobs") {
				if ($remainingCapital > 0 && $pulpScore < 6) {
					if($stmt = $con->prepare("UPDATE characters SET att_pulp = att_pulp + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($remainingCapital > 0 && $pulpScore < 5) {
					if($stmt = $con->prepare("UPDATE characters SET att_pulp = att_pulp + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
		elseif ($argAttribute == "Luck"){

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Luck"));
			$remainingCapital = $parts[0];
			$luckScore = $parts[1];

			if ($argKind == "Popples") {
				if ($remainingCapital > 0 && $luckScore < 6) {
					if($stmt = $con->prepare("UPDATE characters SET att_luck = att_luck + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($remainingCapital > 0 && $luckScore < 5) {
					if($stmt = $con->prepare("UPDATE characters SET att_luck = att_luck + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
	} elseif($argChangeValue == -1) {
		
		if($argAttribute == "Health") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Health"));
			$remainingCapital = $parts[0];
			$healthScore = $parts[1];
			
			if ($argKind == "WickerOnes") {
				if ($healthScore > 13) {

					if($stmt = $con->prepare("UPDATE characters SET att_health = att_health + ?, current_hp = current_hp + ?, 
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiiss", $argChangeValue, $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($healthScore > 15) {

					if($stmt = $con->prepare("UPDATE characters SET att_health = att_health + ?, current_hp = current_hp + ?, 
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiiss", $argChangeValue, $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
		elseif ($argAttribute == "Fury") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Fury"));
			$remainingCapital = $parts[0];
			$furyScore = $parts[1];

			if ($argKind == "Roxis" || $argKind == "WickerOnes") {
				if ($furyScore > 1) {

					if($stmt = $con->prepare("UPDATE characters SET att_fury = att_fury + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			} else {
				if ($furyScore > 0) {

					if($stmt = $con->prepare("UPDATE characters SET att_fury = att_fury + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			}
		}
		elseif ($argAttribute == "Lore") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Lore"));
			$remainingCapital = $parts[0];
			$loreScore = $parts[1];

			if ($argKind == "Lobmysians") {
				if ($loreScore > 2) {

					if($stmt = $con->prepare("UPDATE characters SET att_lore = att_lore + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			} else {
				if ($loreScore > 0) {

					if($stmt = $con->prepare("UPDATE characters SET att_lore = att_lore + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();

					}
				}
			}
		}
		elseif ($argAttribute == "Cool") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Cool"));
			$remainingCapital = $parts[0];
			$coolScore = $parts[1];

			if ($argKind == "Molekins" || $argKind == "Nobs") {
				if ($coolScore > 1) {
					if($stmt = $con->prepare("UPDATE characters SET att_cool = att_cool + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($coolScore > 0) {
					if($stmt = $con->prepare("UPDATE characters SET att_cool = att_cool + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						//Bind parameters and execute statement
						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
		elseif ($argAttribute == "Pulp") {

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Pulp"));
			$remainingCapital = $parts[0];
			$pulpScore = $parts[1];

			if ($argKind == "Nobs") {
				if ($pulpScore > 1) {
					if($stmt = $con->prepare("UPDATE characters SET att_pulp = att_pulp + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($pulpScore > 0) {
					if($stmt = $con->prepare("UPDATE characters SET att_pulp = att_pulp + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}
		elseif ($argAttribute == "Luck"){

			$parts = explode('|', Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, "Luck"));
			$remainingCapital = $parts[0];
			$luckScore = $parts[1];

			if ($argKind == "Popples") {
				if ($luckScore > 1) {
					if($stmt = $con->prepare("UPDATE characters SET att_luck = att_luck + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			} else {
				if ($luckScore > 0) {
					if($stmt = $con->prepare("UPDATE characters SET att_luck = att_luck + ?,
					free_capital = free_capital - ? WHERE permakey = ? and char_hashid = ?")){

						if ($stmt === false) {
							trigger_error($this->mysqli->error, E_USER_ERROR);
							return; 
						}

						$stmt->bind_param("iiss", $argChangeValue, $argChangeValue, $argPermakey, $argCharHashID);
						$stmt->execute();

						$stmt->close();
					}
				}
			}
		}		
	}
}

function Character_AttributeScore_Set($con, $argPermakey, $argCharHashID, $argAttribute, $argKind, $argChangeValue) {}

function Character_AttributeScore_Return($con, $argPermakey, $argCharHashID, $argAttribute) {
	
	/* This function checks the value of a specific stat and returns an output string
	which also includes the remaining free capital before the change to the stat
	as been applied. 
	This function is called in the Attribute-Change function. */
	
	if($argAttribute == "Health") {
		if($stmt = $con->prepare("SELECT free_capital, att_health FROM characters
		WHERE permakey = ? and char_hashid = ?")) {
        
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
    
			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();
    
			$stmt->bind_result($dbCapital, $dbHealthScore);
    
			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbHealthScore;
			}
    
			$stmt->close();
		}
	} elseif ($argAttribute == "Fury"){
		if($stmt = $con->prepare("SELECT free_capital, att_fury FROM characters 
		WHERE permakey = ? and char_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();

			$stmt->bind_result($dbCapital, $dbFuryScore);

			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbFuryScore;
			}

			$stmt->close();
		}
	}
	elseif ($argAttribute == "Lore"){
		if($stmt = $con->prepare("SELECT free_capital, att_lore FROM characters 
		WHERE permakey = ? and char_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();

			$stmt->bind_result($dbCapital, $dbLoreScore);

			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbLoreScore;
			}

			$stmt->close();
		}
	}
	elseif ($argAttribute == "Cool"){
		if($stmt = $con->prepare("SELECT free_capital, att_cool FROM characters 
		WHERE permakey = ? and char_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();

			$stmt->bind_result($dbCapital, $dbCoolScore);

			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbCoolScore;
			}

			$stmt->close();
		}
	}
	elseif ($argAttribute == "Pulp"){
		if($stmt = $con->prepare("SELECT free_capital, att_pulp FROM characters 
		WHERE permakey = ? and char_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();

			$stmt->bind_result($dbCapital, $dbPulpScore);

			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbPulpScore;
			}

			$stmt->close();
		}
	}
	elseif ($argAttribute == "Luck"){
		if($stmt = $con->prepare("SELECT free_capital, att_luck FROM characters 
		WHERE permakey = ? and char_hashid = ?")) {

			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}

			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			$stmt->execute();

			$stmt->bind_result($dbCapital, $dbLuckScore);

			while ($stmt->fetch()) {
				return $dbCapital . "|" . $dbLuckScore;
			}

			$stmt->close();
		}
	}
}

function Character_AttributeScore_ReturnAll($con, $argPermakey, $argCharHashID) {

	/* This function outputs a string composed of the character's remaining free capital
	and current stats score (values from the database). Usually to the client. */

	if($stmt = $con->prepare("SELECT free_capital, att_health, att_fury, att_lore,
	att_cool, att_pulp, att_luck FROM characters WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		$stmt->bind_result($c, $hp, $f, $l, $co, $p, $lu);

		while ($stmt->fetch()) {
			return $c . "|" . $hp  . "|" . $f . "|" . $l . "|" . $co . "|" . $p . "|" . $lu;
		}

		$stmt->close();
	}
}

// Cradle

function Character_Cradle_Set($con, $argPermakey, $argCharHashID, $argCradle) {
	
	/* This function updates the 'characters' database to set a specific character's 'cradle' field.
	To specify which member has one of his characters being affected by this function, we use the 'permakey' 
	that must be obtained beforehand and passed as an argument to this function.
	While to specify which character we need the 'char_hashid' also obtained beforehand and passed as
	an argument to this function.
	
	Depending on which Cradle is passed as argument, this function also has a different outcome one
	either the 'characters', 'characters_inv' or 'characters_skills' databases. */
	
	$previousCradle = Character_Cradle_Return($con, $argPermakey, $argCharHashID);
	
	if ($previousCradle != null) {
		
		if ($previousCradle == "ShadowOfTheTower") {
		
			CharacterInventory_Currency_Set($con, $argPermakey, $argCharHashID, "chips", -100);
		
		} elseif ($previousCradle == "WesternSea") {
		
			Character_XpRatio_Set($con, $argPermakey, $argCharHashID, -10);
		
		} elseif ($previousCradle == "ForestersVillage") {
		
			CharacterSkills_Forget($con, $argPermakey, $argCharHashID, "Herbalism");
			CharacterSkills_ActiveSkill_Remove($con, $argPermakey, $argCharHashID, 9);
		} 
	}
	
	if($stmt = $con->prepare("UPDATE characters SET cradle = ? WHERE permakey = ? and char_hashid = ?")) {
		
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("sss", $argCradle, $argPermakey, $argCharHashID);
		$stmt->execute();
		
		//echo $argCradle;
		
		$stmt->close();
	}

	if ($argCradle == "ShadowOfTheTower") {
		
		CharacterInventory_Currency_Set($con, $argPermakey, $argCharHashID, "chips", 100);
		
	} elseif ($argCradle == "WesternSea") {
		
		Character_XpRatio_Set($con, $argPermakey, $argCharHashID, 10);
		
	} elseif ($argCradle == "ForestersVillage") {
		
		CharacterSkills_Learn($con, $argPermakey, $argCharHashID, "Herbalism");
		CharacterSkills_ActiveSkill_Set($con, $argPermakey, $argCharHashID, 9, "Herbalism");
			
	} 
}

function Character_Cradle_Return($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT cradle FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
	if ($stmt === false) {
		trigger_error($this->mysqli->error, E_USER_ERROR);
		return; 
	}

	$stmt->bind_param("ss", $argPermakey, $argCharHashID);
	$stmt->execute();
	
	$stmt->bind_result($dbCradle);

	while ($stmt->fetch()) {
		return $dbCradle;
	}

	$stmt->close();
	}
}

// Hex Selection

function Character_HexSelection_Set($con, $argPermakey, $argCharHashID, $argHexSelectionString) {
	
	$parts = explode('|', $argHexSelectionString);
	$hex1 = $parts[0];
	$hex2 = $parts[1];
	$hex3 = $parts[2];
	
	if ($hex1 != NULL) {
		Character_XpRatio_Set($con, $argPermakey, $argCharHashID, 0.2);
	}
	if ($hex2 != NULL) {
		Character_XpRatio_Set($con, $argPermakey, $argCharHashID, 0.2);
	}
	if ($hex3 != NULL) {
		Character_XpRatio_Set($con, $argPermakey, $argCharHashID, 0.2);
	}
	
	
	if($stmt = $con->prepare("UPDATE characters SET hex1 = ?, hex2 = ?, hex3 = ? WHERE permakey = ? and char_hashid = ?")){

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("sssss", $hex1, $hex2, $hex3, $argPermakey, $argCharHashID);
		$stmt->execute();

		$stmt->close();
	}
	
}

function Character_HexSelection_Return($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT hex1, hex2, hex3 FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbHex1, $dbHex2, $dbHex3);

		while ($stmt->fetch()) {
			return $dbHex1 . "|" . $dbHex2 . "|" . $dbHex3;
		}

		$stmt->close();
	}
}

// CHARACTER INVENTORY FUNCTIONS

// Currency

function CharacterInventory_Currency_Set($con, $argPermakey, $argCharHashID, $argCurrency, $argChangeValue) {
	
	if($argCurrency == "Chips") {
		if($stmt = $con->prepare("UPDATE characters_inv SET chips = chips + ? 
		WHERE permakey = ? and char_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("iss", $argChangeValue, $argPermakey, $argCharHashID);
			
		}
	} elseif($argCurrency == "Spare Parts") {
		if($stmt = $con->prepare("UPDATE characters_inv SET spare_parts = spare_parts + ? 
		WHERE permakey = ? and char_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("iss", $argChangeValue, $argPermakey, $argCharHashID);
	
		}
	} elseif($argCurrency == "Gemstones") {
		if($stmt = $con->prepare("UPDATE characters_inv SET gemstones = gemstones + ? 
		WHERE permakey = ? and char_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("iss", $argChangeValue, $argPermakey, $argCharHashID);
			
		}
	} elseif($argCurrency == "Phylacteries") {
		if($stmt = $con->prepare("UPDATE characters_inv SET phylacteries = phylacteries + ? 
		WHERE permakey = ? and char_hashid = ?")) {
			
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("iss", $argChangeValue, $argPermakey, $argCharHashID);
			
		}
		
		$stmt->execute();
		$stmt->close();	
	}	
}


// CHARACTER SKILLS FUNCTIONS

// Higher functions

function CharacterSkills_Learn($con, $argPermakey, $argCharHashID, $argSkill) {
	
	$skillLevel = CharacterSkills_SkillLevel_Return($con, $argPermakey, $argCharHashID, $argSkill);
	
	if($skillLevel < 1) {
		
		if($stmt = $con->prepare("UPDATE characters_skills SET " . $argSkill . " = 1 
		WHERE permakey = ? and char_hashid = ?")) {
	
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
		
			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		
			$stmt->execute();
			$stmt->close();	
		}	
	}
}

function CharacterSkills_LevelUp($con, $argPermakey, $argCharHashID, $argSkill) {
	
	$skillLevel = CharacterSkills_SkillLevel_Return($con, $argPermakey, $argCharHashID, $argSkill);
	
	if($skillLevel <= 100) {
		
		if($stmt = $con->prepare("UPDATE characters_skills SET " . $argSkill . " = " . $argSkill . " + 1 
		WHERE permakey = ? and char_hashid = ?")) {
	
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
		
			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		
			$stmt->execute();
			$stmt->close();	
		}	
	}
}

function CharacterSkills_Forget($con, $argPermakey, $argCharHashID, $argSkill) {
	
	$skillLevel = CharacterSkills_SkillLevel_Return($con, $argPermakey, $argCharHashID, $argSkill);
	
	if($skillLevel > 0) {
		
		if($stmt = $con->prepare("UPDATE characters_skills SET " . $argSkill . " = -1 
		WHERE permakey = ? and char_hashid = ?")) {
	
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
		
			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		
			$stmt->execute();
			$stmt->close();	
		}	
	}
}

function CharacterSkills_ForgetAll($con, $argPermakey, $argCharHashID) {
	if($stmt = $con->prepare("UPDATE characters_skills SET Tag = -1, Dashslash = -1,
		Press = -1, Handshake = -1, Erase = -1, Mark = -1, SongOfBravery = -1, Winkletongue = -1, Symbolshards = -1,
		Laughter = -1, Tongue = -1, Repell = -1, Lullaby = -1, BlankPage = -1, Radiance = -1, Cut = -1, Darkinkage = -1,
		Counterspell = -1, Bind = -1, Silence = -1, CullTheMeek = -1, Candlelight = -1, TriumVera = -1,
		Clairvoyance = -1, Toll = -1, Chimera = -1, OilyPatch = -1, Drifter = -1, GiftOfLobmys = -1,
		Herbalism = -1, Metallurgy = -1, Scrollmaking = -1, Hunting = -1, Fishing = -1 
		WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
		
		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		
		$stmt->execute();
		$stmt->close();	
	}	
}

function CharacterSkills_Cast($con, $argPermakey, $argCharHashID, $argActiveSkill) {
	
	$castRangeMin = -1;
	$castRangeMax = -1;
	
	if ($argActiveSkill == 0) {
		$skillName = "BasicAttack";
	}
	else {
		
		if($stmt = $con->prepare("SELECT active_skill" . $argActiveSkill . " FROM characters WHERE permakey = ? and char_hashid = ?")) {
		
			if ($stmt === false) {
				trigger_error($this->mysqli->error, E_USER_ERROR);
				return; 
			}
			
			$stmt->bind_param("ss", $argPermakey, $argCharHashID);
			
			$stmt->execute();
			$stmt->bind_result($dbSkillName);
			
			while ($stmt->fetch()) {
				$skillName = $dbSkillName;
			}

			$stmt->close();	
		}	
	}
	
	if($stmt = $con->prepare("SELECT " . $skillName . " FROM characters_skills WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
		
		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		
		$stmt->execute();
		$stmt->bind_result($dbSkillLevel);
		
		while ($stmt->fetch()) {
			$skillLevel = $dbSkillLevel;
		}

		$stmt->close();	
	}

	if($stmt = $con->prepare("SELECT cast_range_min, cast_range_max, range_increment_per_lvl FROM liveref_skills WHERE skill_name = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}
		
		$stmt->bind_param("s", $skillName);
		$stmt->execute();

		$stmt->bind_result($dbCastRangeMin, $dbCastRangeMax, $dbRangeIncrementPerLevel);

		while ($stmt->fetch()) {
			$castRangeMin = $dbCastRangeMin + ($dbRangeIncrementPerLevel * $skillLevel);
			$castRangeMax = $dbCastRangeMax + ($dbRangeIncrementPerLevel * $skillLevel);
		}

		$stmt->close();	
	}
	
	return $skillName . "|" . $skillLevel. "|" . $castRangeMin . "|" . $castRangeMax;
		
}

// Active skills

function CharacterSkills_ActiveSkill_Set($con, $argPermakey, $argCharHashID, $argSkillSlot, $argSkillName) {
	
	if($stmt = $con->prepare("UPDATE characters SET active_skill" . $argSkillSlot . " = ? WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("sss", $argSkillName, $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->close();	
	}	

	if($stmt = $con->prepare("SELECT active_skill" . $argSkillSlot . " FROM characters WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		$stmt->bind_result($dbSkillName);

		while ($stmt->fetch()) {
			return $dbSkillName;
		}

		$stmt->close();
	}
	
}

function CharacterSkills_ActiveSkill_Remove($con, $argPermakey, $argCharHashID, $argSkillSlot) {
	
	if($stmt = $con->prepare("UPDATE characters SET active_skill" . $argSkillSlot . " = NULL WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->close();	
	}
}

// SkillLevel

function CharacterSkills_SkillLevel_Return($con, $argPermakey, $argCharHashID, $argSkill) {

	if($stmt = $con->prepare("SELECT " . $argSkill . " FROM characters_skills WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		$stmt->bind_result($dbSkillLevel);

		while ($stmt->fetch()) {
			return $dbSkillLevel;
		}

		$stmt->close();
	}
}

function CharacterSkills_SkillLevel_ReturnAll($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT * FROM characters_skills WHERE permakey = ? and char_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$returnData[] = $row;
		}

		return $returnData;

		$stmt->close();
	}
}	

//ROTATE OUT:
function Character_CreateCharSheet_Retrieve($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT kind, cradle, hex1, hex2, hex3, att_health, att_fury, att_lore, att_cool, att_pulp, att_luck FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbKind, $dbCradle, $dbHex1, $dbHex2, $dbHex3, $dbHealthScore, $dbFuryScore, $dbLoreScore, $dbCoolScore, $dbPulpScore, $dbLuckScore);

		while ($stmt->fetch()) {
			return $dbKind . "|" . $dbCradle . "|" . $dbHex1 . "|" . $dbHex2 . "|" . $dbHex3 . "|" . $dbHealthScore . "|" . $dbFuryScore . "|" . 
			$dbLoreScore . "|" . $dbCoolScore . "|" . $dbPulpScore . "|" . $dbLuckScore;
		}

		$stmt->close();
	}	
	
}

function Character_RetrieveData_Slot($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT kind, name, lvl, xp, xp_ratio, cradle, hex1, hex2, hex3, current_hp FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbKind, $dbName, $dbLvl, $dbXp, $dbXpRatio, $dbCradle, $dbHex1, $dbHex2, $dbHex3, $dbCurrentHp);

		while ($stmt->fetch()) {
			return $dbKind . "€" . $dbName . "€" . $dbLvl . "€" . $dbXp . "€" . $dbXpRatio . "€" . $dbCradle . "€" . $dbHex1 . "€" . 
			$dbHex2 . "€" . $dbHex3 . "€" . $dbCurrentHp;
		}

		$stmt->close();
	}	
	
}

function Character_RetrieveData($con, $argPermakey, $argCharHashID) {
	
	if($stmt = $con->prepare("SELECT kind, name, lvl, xp, xp_ratio, cradle, 
							hex1, hex2, hex3, current_hp,
							att_health, att_fury, att_lore, att_cool, att_pulp, att_luck
							FROM characters WHERE permakey = ? and char_hashid = ?")) {
	
		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("ss", $argPermakey, $argCharHashID);
		$stmt->execute();
		
		$stmt->bind_result($dbKind, $dbName, $dbLvl, $dbXp, $dbXpRatio, $dbCradle, 
							$dbHex1, $dbHex2, $dbHex3, $dbCurrentHp,
							$dbHealthScore, $dbFuryScore, $dbLoreScore, $dbCoolScore, $dbPulpScore, $dbLuckScore);

		while ($stmt->fetch()) {
			return $dbKind . "|" . $dbName . "|" . $dbLvl . "|" . $dbXp . "|" . $dbXpRatio . "|" . $dbCradle . "|" . $dbHex1 . "|" . 
			$dbHex2 . "|" . $dbHex3 . "|" . $dbCurrentHp . "|" . 
			$dbHealthScore . "|" . $dbFuryScore . "|" . $dbLoreScore . "|" . $dbCoolScore . "|" . $dbPulpScore . "|" . $dbLuckScore ;
		}

		$stmt->close();
	}	
	
}




// MONSTER

function Monster_AgreeOnStatus($con, $argMonsterGroupHashID, $argUpdateX, $argUpdateY) {

	if($stmt = $con->prepare("UPDATE monster_groups SET monstergr_pos_x = ?, monstergr_pos_y = ?
		WHERE monstergr_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("iis", $argUpdateX, $argUpdateY, $argMonsterGroupHashID);
		$stmt->execute();	
		
		$stmt->close();	
	}
	
	if($stmt = $con->prepare("SELECT monstergr_pos_x, monstergr_pos_y FROM monster_groups WHERE monstergr_hashid = ?")) {

		if ($stmt === false) {
			trigger_error($this->mysqli->error, E_USER_ERROR);
			return; 
		}

		$stmt->bind_param("s", $argMonsterGroupHashID);
		$stmt->execute();		
		
		$stmt->bind_result($dbMonsterGrPosX, $dbMonsterGrPosY);	
		
		while ($stmt->fetch()) {
			return $dbMonsterGrPosX . "|" . $dbMonsterGrPosY;
		}
			
		$stmt->close();	
	}
	
}

?>