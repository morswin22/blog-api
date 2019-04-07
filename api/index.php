<?php

session_start();
require_once "helper.php";

// set up
$apiPath = "/api/";
$usersPath = "data/users.json";
$setupPath = "data/setup.json";
$postsPath = "data/posts.json";
$imagesPath = "data/images/";

// setup users database
if (!file_exists($usersPath) || count(loadJSON($usersPath)) == 0) {
    if(isMethod("addUser") && isPosted('name', 'email', 'pass')) {
        saveJSON($usersPath, array(array("name"=> $_POST["name"], "email"=>$_POST['email'], "pass"=>$_POST['pass'])));
        login($_POST['email'], $_POST['pass']);
        redirect($apiPath);
    } else {
        render("addUser", array("url"=>$apiPath));
        exit();
    }
}
$users = loadJSON($usersPath);

// check if user is logged
if (!isLogged()) {
    if (isMethod('login') && isPosted('email', 'pass')) {
        if (!login($_POST['email'], $_POST['pass'])) {
            redirect($apiPath);
        }
    } else {
        render("login", array("url"=>$apiPath));
        exit();
    }
}
$user = $_SESSION['user'];

// setup posts format
if (isMethod('setup')) {
    if (!isPosted('format')) {
        render('setup', array('userName'=>$user['name'], 'url'=>$apiPath));
    } else {
        saveJSON($setupPath, json_decode($_POST['format'], true));
        redirect($apiPath);
    }
    exit();
} else if (!file_exists($setupPath)) {
    redirect($apiPath."?setup");
}
$setup = loadJSON($setupPath);

// setup posts file
if (!file_exists($postsPath)) {
    saveJSON($postsPath, array());
}
if (!is_dir($imagesPath)) {
    mkdir($imagesPath);
}
$posts = loadJSON($postsPath);

// show dashboard
if (isMethod('info')) {
    echo "<pre>";
    print_r($user);
    print_r($setup);
    print_r($posts);
    echo "</pre>";
} else {
    render('dashboard', array('userName'=>$user['name']));
}

?>