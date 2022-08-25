<?php

// RefreshSlots | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$charHashID = $_GET['CHI'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey, $username);

echo Character_Kind_Return($con, $permakey, $charHashID);

?>