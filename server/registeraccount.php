<?php

// RegisterAccount | VANDALS MMO

// Server code category: 200

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';
include '_CORE.php';

$registerUsername = Enigma::Decrypt($_GET['UN'], $K_CREATE, 0);
$registerPassword = Enigma::Decrypt($_GET['PW'], $K_CREATE, 0);
$registerPassword2 = Enigma::Decrypt($_GET['PW2'], $K_CREATE, 0);
$registerIRLName = Enigma::Decrypt($_GET['RN'], $K_CREATE, 0);
$registerEmail = Enigma::Decrypt($_GET['EM'], $K_CREATE, 0);
$registerPhone = Enigma::Decrypt($_GET['PH'], $K_CREATE, 0);
$age = $_GET['AGE'];
$promo = $_GET['PRO'];
$contract = $_GET['CT'];

$registerPasswordCryptogram = $_GET['PW'];

$con = ConnectToDatabase();

// 1. Verify submission conformity.

// 1a. Verify if password and password confirmation match.

if($registerPassword != $registerPassword2){	
    die("201");
}

// 1b. Verify username and password lengths.
elseif (strlen($registerUsername) < 3 && strlen($registerUsername) > 20){
    die("202");
}

elseif (strlen($registerPassword) < 6 && strlen($registerPassword) > 20){
    die("203");
}

// 1c. Verify if email is an email.
elseif (!filter_var($registerEmail, FILTER_VALIDATE_EMAIL)) {
    die("204");
}

// 2. Check if this username exists in the database.

else {

	$usernameExists = Account_Username_CheckIfExists($con, $registerUsername);

	if($usernameExists == 0) {

		Account_Create($con, $registerUsername, $registerPasswordCryptogram, $registerIRLName, $registerEmail, $registerPhone, $promo);

	} else {

		die("");

	}
}

?>


