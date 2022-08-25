<?php

// UnspendPoint | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$attribute = Enigma::Decrypt($_GET['AT'], $K_CREATE, 0);

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);
$charCount = Account_CharCount_Return($con, $permakey);
$charNumber = $charCount;
$charHashID = Character_CharHashID_Return($con, $permakey, $charNumber);
$kind = Character_Kind_Return($con, $permakey, $charHashID);

Character_StartingAttributeScore_Set($con, $permakey, $charHashID, $attribute, $kind, -1);

echo Character_AttributeScore_ReturnAll($con, $permakey, $charHashID);

?>