<?php

// ChooseHexes | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$hexSelectionString = Enigma::Decrypt($_GET['HS'], $K_CREATE, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charCount = Account_CharCount_Return($con, $permakey);
$charHashID = Character_CharHashID_Return($con, $permakey, $charCount);

Character_HexSelection_Set($con, $permakey, $charHashID, $hexSelectionString);

echo Character_CreateCharSheet_Retrieve($con, $permakey, $charHashID);

?>