<?php
/**
 * Created by PhpStorm.
 * User: kirichek
 * Date: 10/26/16
 * Time: 12:05 PM
 */

require_once($_SERVER["DOCUMENT_ROOT"] . "/car-shipping/resources/config.php");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Start Page</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../resources/library/bootstrap-3.3.7-dist/css/bootstrap.css">
    <script src="../resources/library/jquery-3.1.1.min.js"></script>
    <script src="../resources/library/bootstrap-3.3.7-dist/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>

<nav class="navbar navbar-inverse mynavbar">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">Online Car Shipping Service</a>
        </div>
        <div class="collapse navbar-collapse" id="myNavbar">
            <ul class="nav navbar-nav">
                <li class="active">
                <li><a href="userpage.php">My Account</a></li>
                <li><a href="search.php">Search</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="../index.php"><span class="glyphicon glyphicon-log-in"></span> Sign out </a></li>
            </ul>
        </div>
    </div>
</nav>


</body>
</html>

