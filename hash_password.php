<?php
// Array of users with their email addresses and plain passwords
$users = [
    ['email' => 'pccadministrator@pcc.gov.ph', 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'],
    ['email' => 'user2@example.com', 'password' => 'adminpassword'],
    ['email' => 'user3@example.com', 'password' => 'mypassword'],
    // Add more users here as needed
];

foreach ($users as $user) {
    $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
    echo "Email: " . $user['email'] . " | Hashed Password: " . $hashedPassword . "<br>";
}
?>
