<?php

include "db.php";

if (isset($_POST["signup"])) {

    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    $password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $sql = "INSERT INTO users
            (username, email, password)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sss",
        $username,
        $email,
        $password
    );

    if ($stmt->execute()) {

        echo "<script>
                alert('Account created successfully!');
                window.location='login.html';
              </script>";

    } else {

        echo "<script>
                alert('Username or email already exists!');
                window.location='signup.html';
              </script>";
    }
}

?>