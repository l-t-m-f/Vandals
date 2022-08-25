<?php

// ConfirmApp | VANDALS MMO

require_once '_GATEKEEPER.php';
require_once '_KEYCHAIN.php';

// Generates a random browserKey
$browserkey = substr(sha1(time()), 0, 16);

// Encrypts the browserKey
$browserkey_e = Enigma::Encrypt($browserkey, $K_BROWSER);

/* Sends the encrypted browserKey
The Construct 3 will catch this data as AJAX.LastData */

echo $browserkey_e;

?>