<?php

ini_set("log_errors", 1);
ini_set("error_log", "/var/www/ROCC/login_errors");

error_log('hello errors');

session_start();

require_once('API/utils/Model.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (array_key_exists('Email', $_POST) && array_key_exists('Password', $_POST)) {
        $email = strtolower(trim($_POST['Email']));
        $password = $_POST['Password'];
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $conn = GetDatabaseConnection();
            if ($conn !== null) {
                $sql = "SELECT password, isAdmin
                        FROM Consultants
                        WHERE email = ?;";
                $stmt = PrepareAndExecute($conn, $sql, array($email));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $passwordHash = $row['password'];
                $isAdmin = $row['isAdmin'];

                if (password_verify($password, $passwordHash)) {
                    $_SESSION['email'] = $email;
                    $_SESSION['admin'] = $isAdmin === '1';
                    $isLoginSuccess = true;
                    error_log("login successful");
                } else {
                    $isLoginSuccess = false;
                    $error = "Incorrect username or password.";
                }
            } else {
                $isLoginSuccess = false;
                $error = "Could not connect to database.";
            }
        } else {
            $isLoginSuccess = false;
            $error = "Email is not a valid email.";
        }
    } else {
        $isLoginSuccess = false;
        $error = "Server did receive an Email and a Password.";
    }
}

if (isset($_SESSION['email'])) {
    header("Location: index.php");
}
?>

<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta https-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">

        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

        <!-- Popper JS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>

        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
        <link href="https://fonts.googleapis.com/css?family=Montserrat|Abel|Comfortaa|Raleway|Roboto|Quicksand|Lora|PT+Serif|Sulphur+Point&display=swap" rel="stylesheet">


        <link rel="stylesheet" href="css/login.css">
        <title>ROCC</title>
    </head>
    <body>
        <div class="container">
            <div class ="pgInfo">
                <img id ="logo" src='images/SP.png'>
                <p3> Welcome to <strong>ROCC</strong> for SPadv</p3>
                <p4> A Remote Oral Consultant Checker tool for remote StoryProducerAdv users</p4>
            </div>
            <div class="row">
                    <div class="card card-signin my-5">
                        <div class="card-body">
                            <h5 class="card-title text-center">Sign In</h5>
                            <form class="form-signin" method="POST">
                                <div class="form-label-group">

                                    <input type="text" name="Email" placeholder="Email"></input>
                                    <label for="inputEmail"></label>
                                </div>

                                <div class="form-label-group">
                                    <input type="password" name="Password" placeholder="Password"></input>
                                    <label for="inputPassword"></label>
                                </div>
                                <div class="s-button">
                                    <button class="btn btn-lg btn-primary btn-block text-uppercase" type="submit" value="Login">Sign in</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </body>
</html>
