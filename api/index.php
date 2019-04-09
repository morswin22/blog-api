<?php

session_start();
require_once "helper.php";

// set up
$apiPath = "/api/";
$usersPath = "data/users.json";
$setupPath = "data/setup.json";
$postsPath = "data/posts.json";
$imagesPath = "data/images/";

// quickly deliver posts
if (isMethod('get')) {
    // check all things
    exit();
}

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

// check if user wants to logout 
if (isMethod('logout')) {
    unset($_SESSION['user']);
    redirect($apiPath);
}

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
} else if (isMethod("settings")) {

} else if (isMethod("add")) {
    $hasAll = true;
    foreach ($setup as $requirement) {
        if (!isPosted($requirement['id'])) {
            $hasAll = false;
        }
    }
    if ($hasAll) {
        $replacements = array(
            "POST_ID" => count($posts)+1, // because of the indexing
            "CURRENT_TIME&DATE" => date("d.m.Y H:i"),
            "CURRENT_DATE" => date("d.m.Y"),
            "CURRENT_TIME" => date("H:i"),
            "CURRENT_USER" => $user['name'],
        );
        $newPost = array();
        foreach ($setup as $requirement) {
            $value = $_POST[$requirement['id']]; 
            foreach ($replacements as $find => $replace) {
                $value = preg_replace("/$find/m", $replace, $value);
            }
            $newPost[$requirement['id']] = $value;
            if ($requirement['type'] == "textarea") {
                $value = $_POST[$requirement['id']."Parsed"]; 
                foreach ($replacements as $find => $replace) {
                    $value = preg_replace("/$find/m", $replace, $value);
                }
                $newPost[$requirement['id']."Parsed"] = $value;
            }
        }
        unset($user['pass']); // clear that
        $posts[] = array(
            "data" => $newPost,
            "creator" => $user,
            "date" => date("d.m.Y"),
            "time" => date("H:i"),
            "postID" => $replacements["POST_ID"]
        );
        saveJSON($postsPath, $posts);
        redirect($apiPath);
    } else {
        render("add", array("format"=>$setup, "url"=>$apiPath));
    }
} else {
    render('dashboard', array('userName'=>$user['name'], 'posts'=>$posts));
}

?>