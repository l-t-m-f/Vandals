<?php

// TEST | VANDALS MMO

include '_GATEKEEPER.php';
include '_CONFIG.php';
include '_KEYCHAIN.php';
include '_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$charHashID = $_GET['CHI'];

$con = ConnectToDatabase();

$permakey = Account_Permakey_Return($con, $browserkey);

echo CharacterSkills_Cast($con, $permakey, $charHashID, 0);

?>