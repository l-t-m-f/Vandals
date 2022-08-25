<?php

// LoadNews | VANDALS MMO

require_once '_GATEKEEPER.php';
require_once '_KEYCHAIN.php';
require_once '_CONFIG.php';
require_once '_CORE.php';

$con = ConnectToDatabase();

echo Load_News($con);

?>