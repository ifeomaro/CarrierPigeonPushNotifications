<?php

/**  @author Ifeoma Okereke (April 2014) * */
include 'classes/class_APNS.php';

/* create database object */
$db = new DbConnect('localhost', '<sql-username>', '<sql-password>', '<dbname>');
$db->show_errors();

/* get the script arguments */
$args = (!empty($_GET)) ? $_GET : array('task' => $argv[1]);

$sandboxCertificate = 'cert/apns-dev.pem';

$logPath = 'log/apns.log';

/* process arguments */
if (!empty($args)) {

    switch ($args['task']) {
        case "register":
            /* new user registration */
            echo "New user registration\n";
            $apns = new APNS($db, $args, NULL, $sandboxCertificate, $logPath);
            break;
        case "msg":
            /* a new message has arrived */
            echo "New message received \n";
            //todo: ensure msg is of size less than 256 bytes
            $msg = "New message from " . $args['from'] . ": " . $args['body'];
            /* create apns object */
            $apns = new APNS($db, NULL, NULL, $sandboxCertificate, $logPath);

            $deviceToken = getDeviceTokenForUser($db, $args['to']);
            if ($deviceToken) {
                /* create message and send notification */
                $apns->newMessageByDeviceToken($deviceToken);
                $apns->addMessageBadge(1);
                $apns->addMessageAlert($msg);
                $apns->queueMessage();
                $apns->processQueue();
            } else {
                echo "A device token does not exist for this user.\n";
            }
        case "update":
            /* update user's device token record */

            if ($args['updatereason']) {
                $updateReason = $args['updatereason'];
                if ($updateReason === '1') {
                    /* update the database with the username for this device Token */

                    if ($args['devicetoken'] && $args['username']) {
                        updateAPNSTable($db, $args, $updateReason);
                    } else {
                        echo "Device token or username unavailable\n";
                    }
                }

                if ($updateReason === '2') {
                    /* update the database with the new username for this device Uid */

                    if ($args['deviceuid'] && $args['username'] && $args['pushalert'] && $args['pushbadge'] && $args['pushsound']) {
                        updateAPNSTable($db, $args, $updateReason);
                    } else {
                        echo "Device Uid, username, pushalert, pushbadge or pushsound unavailable\n";
                    }
                }

                if ($updateReason === '3') {
                    /* the user has logged out, clear the username from the table */
                    if ($args['username'] && $args['deviceuid']) {
                        updateAPNSTable($db, $args, $updateReason);
                    } else {
                        echo "Username or device uid unavailable\n";
                    }
                }
            } else {
                echo "No update reason provided\n";
            }
            break;
        default:
            echo "Unexpected param.\n";
            break;
    }
}

function getDeviceTokenForUser($db, $user) {
    /* get device token with username $user */
    $user = $db->prepare($user);
    $result = $db->query("SELECT `devicetoken` FROM `apns_devices` WHERE `username`='{$user}'");
    $row = $result->fetch_array(MYSQLI_ASSOC);
    if ($row != NULL) {
        return $row['devicetoken'];
    } else {
        return NULL;
    }
}

function updateAPNSTable($db, $args, $updateReason) {

    $db->query("SET NAMES 'utf8';");

    switch ($updateReason) {
        case "1":
            $deviceToken = $db->prepare($args['devicetoken']);
            $username = $db->prepare($args['username']);
            $db->query("UPDATE `apns_devices` SET `username`='{$username}' WHERE "
                    . "`devicetoken`='{$deviceToken}' "
                    . "LIMIT 1;");
            echo "Username set for device token\n";
            break;
        case "2":
            $deviceUid = $db->prepare($args['deviceuid']);
            $username = $db->prepare($args['username']);
            $pushalert = $db->prepare($args['pushalert']);
            $pushbadge = $db->prepare($args['pushbadge']);
            $pushsound = $db->prepare($args['pushsound']);
            $db->query("UPDATE `apns_devices` SET `username`='{$username}', "
                    . "`pushbadge`='{$pushbadge}', `pushalert`='{$pushalert}', "
                    . "`pushsound`='{$pushsound}' WHERE "
                    . "`deviceuid`='{$deviceUid}' "
                    . "LIMIT 1;");
            echo "Username set for device Uid\n";
            break;
        case "3":
            $oldUsername = $db->prepare($args['username']);
            $deviceUid = $db->prepare($args['deviceuid']);
            $newUsername = "";
            $db->query("UPDATE `apns_devices` SET `username`='{$newUsername}' WHERE "
                    . "`username`='{$oldUsername}' AND `deviceuid`='{$deviceUid}' "
                    . "LIMIT 1;");
            echo "Username unset for device token/Uid\n";
            break;
        default:
            echo "Unexpected update reason\n";
            break;
    }
}

?>