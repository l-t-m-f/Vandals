<?php

// LoadCharacterList | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$charNumber = $_GET['CS'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);

Character_Delete($con, $permakey, $charNumber);

?>