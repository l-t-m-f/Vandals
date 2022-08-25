<?php

// UpdateStatus | Vandals MMO

require_once '_GATEKEEPER.php';
include '_CONFIG.php';

$con = ConnectToDatabase();
        
$currentTime = date('YmdHis');
        
$stmt = $con->prepare("UPDATE members SET conn_status=? WHERE conn_status=? AND ? > last_ping");

if ($stmt === false) {
    trigger_error($this->mysqli->error, E_USER_ERROR);
    return;
} else {         
    echo $currentTime;      
}
        
$stmt->bind_param("iii", $st1=0, $st2=1, $currentTime);
$stmt->execute();
        
$stmt->close();

?>