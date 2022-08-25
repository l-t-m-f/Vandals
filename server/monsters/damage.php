<?php

// Monsters - RequestDestroy | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$mapHashID = Enigma::Decrypt($_GET['MHI'], $K_BROWSER, 0);
$monsterGroupHashID = Enigma::Decrypt($_GET['MGHI'], $K_BROWSER, 0);
$monsterHashID = Enigma::Decrypt($_GET['MHI2'], $K_BROWSER, 0);
$damageValue = Enigma::Decrypt($_GET['DV'], $K_BROWSER, 0);

settype($damageValue, "integer");

$con = ConnectToDatabase();

//echo Monster_Damage($con, $mapHashID, $monsterGroupHashID, $monsterHashID, $damageValue);

echo Enigma::Encrypt(Monsters_Damage($con, $mapHashID, $monsterGroupHashID, $monsterHashID, $damageValue), $K_BROWSER, 0);

?>