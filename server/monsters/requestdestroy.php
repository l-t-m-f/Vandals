<?php

// Monsters - RequestDestroy | VANDALS MMO

include '../_GATEKEEPER.php';
include '../_CONFIG.php';
include '../_KEYCHAIN.php';
include '../_CORE.php';

$mapHashID = Enigma::Decrypt($_GET['MHI'], $K_BROWSER, 0);
$monster = Enigma::Decrypt($_GET['X'], $K_BROWSER, 0);
$gridGraph_spawnY = Enigma::Decrypt($_GET['Y'], $K_BROWSER, 0);

settype($gridGraph_spawnX, "integer");
settype($gridGraph_spawnY, "integer");

$con = ConnectToDatabase();

echo Enigma::Encrypt(Monster_Create($con, $mapHashID, $gridGraph_spawnX, $gridGraph_spawnY), $K_BROWSER, 0);


?>