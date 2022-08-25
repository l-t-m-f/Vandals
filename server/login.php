<?php

// LoadSession | VANDALS MMO

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';
include '_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$password = Enigma::Decrypt($_GET['PW'], $K_PASSWORD, 0);

$con = ConnectToDatabase();


$connStatus = Account_ConnStatus_Return($con, $username);

if ($connStatus == 1) {

	echo "Error - Already connected !";

} elseif ($connStatus == 0) {

	/* Verify is the password sent with the get request matches the database password
	   for said username. */
	   
	$passwordMatch = Account_Password_CheckAgainstCrypto($con, $username, $password, $K_CREATE);

	if ($passwordMatch == 1) {
	
		/* The variable defined bellow is the encrypted database password.
		   This encryptiong is particular in that is uses a specific encryption key, which is only used during account registration. */

		$passwordCryptogram = Account_PasswordCryptogram_Return($con, $username);

		// Attempt to retrieve the permakey of the account.
		$permakey = Account_Permakey_Return_risky($con, $username, $passwordCryptogram);

		/* If its null, define one. This is only supposed to be done a single time per account.
		   The permakey will link this row the the members database with the character databases. */

		if ($permakey == null) {

			$permakey = bin2hex(random_bytes(96));

			/* Set the newly defined permakey in the database and retrieve it as you attempted before.
			   It won't be null now. */
			Account_Permakey_Set($con, $username, $passwordCryptogram, $permakey);
			$permakey = Account_Permakey_Return_risky($con, $username, $passwordCryptogram);

		}

		/* The session key is defined during the session as a combination of permakey and browserkey
		   so that every time the session is a loaded the new lastSessionkey is updated to the db. */
		$lastSessionkey = $permakey . $browserkey; 
			
		Account_LastSessionkey_Set($con, $username, $passwordCryptogram, $lastSessionkey);
		Account_CompleteLogin($con, $username, $passwordCryptogram, $lastSessionkey);

	} else {

		echo "Error - Wrong password !";
	
	}
}
    
mysqli_close($con);

?>