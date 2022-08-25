<?php

// Refresh | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$username = Enigma::Decrypt($_GET['UN'], $K_USERNAME, 0);
$charHashID = $_GET['CHI'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);

$charData = Character_RefreshClientState($con, $permakey, $charHashID);
$messageToClient = $charHashID . "|" . $charData;

echo $messageToClient;

?>