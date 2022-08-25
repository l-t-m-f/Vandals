<?php

/* UpdateCharCipher | VANDALS MMO



*/
include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$charCipher = $_GET['CP'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charHashID = Character_CharHashID_Return($con, $permakey, $charNumber);

//echo Character_Data_ReturnAll($con, $permakey, $charHashID);
echo Enigma::Encrypt(Character_Data_ReturnAll($con, $permakey, $charHashID), $K_BROWSER);

?>