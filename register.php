<?php
session_start();
include "db.php";

// ─── SANITIZE ALL INPUTS (SQL Injection Fix) ───
function clean($conn, $val) {
    return mysqli_real_escape_string($conn, trim($val));
}

$team      = clean($conn, $_POST['team']);
$college   = clean($conn, $_POST['college']);
$team_size = clean($conn, $_POST['team_size']);

$p1        = clean($conn, $_POST['p1']);
$p1_phone  = clean($conn, $_POST['p1_phone']);
$p1_email  = clean($conn, $_POST['p1_email']);
$p1_food   = clean($conn, $_POST['p1_food']);

$p2        = clean($conn, $_POST['p2'] ?? '');
$p2_phone  = clean($conn, $_POST['p2_phone'] ?? '');
$p2_email  = clean($conn, $_POST['p2_email'] ?? '');
$p2_food   = clean($conn, $_POST['p2_food'] ?? '');

$p3        = clean($conn, $_POST['p3'] ?? '');
$p3_phone  = clean($conn, $_POST['p3_phone'] ?? '');
$p3_email  = clean($conn, $_POST['p3_email'] ?? '');
$p3_food   = clean($conn, $_POST['p3_food'] ?? '');

$p4        = clean($conn, $_POST['p4'] ?? '');
$p4_phone  = clean($conn, $_POST['p4_phone'] ?? '');
$p4_email  = clean($conn, $_POST['p4_email'] ?? '');
$p4_food   = clean($conn, $_POST['p4_food'] ?? '');

$medical   = clean($conn, $_POST['medical'] ?? '');

// ─── PHONE & EMAIL VALIDATION ───
$phone_regex = '/^[6-9][0-9]{9}$/';
$email_regex = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';

if (!preg_match($phone_regex, $p1_phone)) {
    header("Location: register.html?error=invalid_phone");
    exit();
}

if (!filter_var($p1_email, FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $p1_email)) {
    header("Location: register.html?error=invalid_email");
    exit();
}

// Validate other members if filled
for ($i = 2; $i <= 4; $i++) {
    $phone = ${"p{$i}_phone"};
    $email = ${"p{$i}_email"};
    if (!empty($phone) && !preg_match($phone_regex, $phone)) {
        header("Location: register.html?error=invalid_phone");
        exit();
    }
    if (!empty($email) && (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($email_regex, $email))) {
        header("Location: register.html?error=invalid_email");
        exit();
    }
}

// ─── DUPLICATE CHECK ───
$dup_check = mysqli_query($conn, "SELECT id FROM registrations WHERE p1_phone='$p1_phone' OR p1_email='$p1_email'");
if (mysqli_num_rows($dup_check) > 0) {
    // Redirect back to register.html with error
    header("Location: register.html?error=duplicate");
    exit();
}

// ─── INSERT REGISTRATION ───
$sql = "INSERT INTO registrations
(team, college, team_size,
 p1, p1_phone, p1_email, p1_food,
 p2, p2_phone, p2_email, p2_food,
 p3, p3_phone, p3_email, p3_food,
 p4, p4_phone, p4_email, p4_food,
 medical)
VALUES
('$team','$college','$team_size',
 '$p1','$p1_phone','$p1_email','$p1_food',
 '$p2','$p2_phone','$p2_email','$p2_food',
 '$p3','$p3_phone','$p3_email','$p3_food',
 '$p4','$p4_phone','$p4_email','$p4_food',
 '$medical')";

if (mysqli_query($conn, $sql)) {
    $new_id = mysqli_insert_id($conn);
    // Format: TB3-0001
    $ref_id = 'TB3-' . str_pad($new_id, 4, '0', STR_PAD_LEFT);

    // Update the row with the formatted ref_id
    mysqli_query($conn, "UPDATE registrations SET ref_id='$ref_id' WHERE id=$new_id");

    // Pass data to success page via session
    $_SESSION['reg_success'] = [
        'ref_id'    => $ref_id,
        'team'      => $team,
        'leader'    => $p1,
        'email'     => $p1_email,
        'team_size' => $team_size,
        'college'   => $college,
    ];

    header("Location: success.php");
    exit();
} else {
    die("Registration failed. Please try again. Error: " . mysqli_error($conn));
}
?>