<?php

// Monsters / RefreshRespawnTimer | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$browserkey = Enigma::Decrypt($_GET['BK'], $K_BROWSER, 0);
$mapHashID = Enigma::Decrypt($_GET['MHI'], $K_BROWSER, 0);

$con = ConnectToDatabase();

echo Enigma::Encrypt(Monsters_RefreshRespawnTimer($con, $mapHashID), $K_BROWSER, 0);

?>