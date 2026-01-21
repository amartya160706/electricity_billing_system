<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$DB_HOST = 'localhost';
$DB_USER = 'electricity_user';
$DB_PASS = 'Elec@1234';
$DB_NAME = 'electricity_service_billing';
$DB_PORT = 3306;


$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function to_title_case(string $str): string {
    return ucwords(strtolower(trim($str)));
}

function check_overdue_bills_and_notify(mysqli $conn): void {
    $today = date('Y-m-d');

    $stmt = $conn->prepare(
        "SELECT bill_id, service_number 
         FROM bills 
         WHERE status='Unpaid' AND due_date < ?"
    );
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $bill_id = $r['bill_id'];
        $service = $r['service_number'];

        $conn->query("UPDATE bills SET status='Overdue' WHERE bill_id=$bill_id");

        $chk = $conn->prepare(
            "SELECT id FROM notifications 
             WHERE bill_id=? AND type='overdue' LIMIT 1"
        );
        $chk->bind_param("i", $bill_id);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows === 0) {
            $msg = "Your electricity bill is overdue. Please pay immediately.";
            $ins = $conn->prepare(
                "INSERT INTO notifications
                 (service_number,bill_id,message,type,is_seen,created_at)
                 VALUES (?,?,?,'overdue',0,NOW())"
            );
            $ins->bind_param("sis", $service, $bill_id, $msg);
            $ins->execute();
            $ins->close();
        }
        $chk->close();
    }
    $stmt->close();
}

function get_notification_count_for_user(
    mysqli $conn,
    string $role,
    ?string $service_number = null
): int {
    if ($role === 'consumer' && $service_number) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) c FROM notifications 
             WHERE service_number=? AND is_seen=0"
        );
        $stmt->bind_param("s", $service_number);
    } elseif ($role === 'admin') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) c FROM notifications 
             WHERE type='overdue' AND is_seen=0"
        );
    } else {
        return 0;
    }

    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$res['c'];
}

