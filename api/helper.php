<?php

function loadJSON($path) {
    return json_decode(file_get_contents($path), true);
}

function saveJSON($path, $content) {
    return file_put_contents($path, json_encode($content));
}

function isMethod($email) {
    return isset($_GET[$email]) || isset($_POST[$email]);
}

function noMethod() {
    return (count($_GET)+count($_POST)) == 0;
}

function redirect($path) {
    header("Location: ".$path);
    exit();
}

function isPosted(...$args) {
    foreach ($args as $arg) {
        if (!isset($_POST[$arg])) {
            return false;
        }
    }
    return true;
}

function isPost($postID) {
    global $posts;
    return (isset($posts[$postID-1]) && $posts[$postID-1] != null);
}

function login($email, $pass) {
    global $usersPath;
    $users = loadJSON($usersPath);
    foreach ($users as $user) {
        if ($user["email"] == $email && $user["pass"] == $pass) {
            $_SESSION["user"] = $user;
            return true;
        }
    }
    return false;
}

function isLogged() {
    return (isset($_SESSION["user"]) && login($_SESSION["user"]["email"], $_SESSION["user"]["pass"]));
}

function render($name, $variables = array()) {
    foreach ($variables as $variableName => $variable) {
        $$variableName = $variable;
    }
    require "pages/templateStart.html";
    require "pages/$name.html";
    require "pages/templateEnd.html";
}

function shortenStr($str, $max, $replacement = "...") {
    if (strlen($str) > $max) {
        return substr($str, 0, $max-strlen($replacement)) . $replacement;
    } else {
        return $str;
    }
}

?>