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
    if (file_exists($setupPath) && file_exists($postsPath)) {
        $setup = loadJSON($setupPath);
        $results = loadJSON($postsPath);

        if (isMethod("postID")) {
            foreach($results as $id => $result) {
                if ($result['postID'] != $_GET['postID']) {
                    unset($results[$id]);
                }
            }
        } else {
            foreach($results as $id => $result) {
                if (isMethod('date')) {
                    if ($result['date'] != $_GET['date']) {
                        unset($results[$id]);
                    }
                } else if (isMethod('dateStart') && isMethod('dateEnd')) {
                    $start = strtotime($_GET['dateStart']);
                    $post = strtotime($result['date']);
                    $end = strtotime($_GET['dateEnd']);
                    if ($post < $start || $post > $end) {
                        unset($results[$id]);
                    }
                } else if (isMethod('dateStart')) {
                    $start = strtotime($_GET['dateStart']);
                    $post = strtotime($result['date']);
                    if ($post < $start) {
                        unset($results[$id]);
                    }
                } else if (isMethod('dateEnd')) {
                    $post = strtotime($result['date']);
                    $end = strtotime($_GET['dateEnd']);
                    if ($post > $end) {
                        unset($results[$id]);
                    }
                }
            }

            foreach($results as $id => $result) {
                if (isMethod('time')) {
                    if ($result['time'] != $_GET['time']) {
                        unset($results[$id]);
                    }
                } else if (isMethod('timeStart') && isMethod('timeEnd')) {
                    $start = strtotime($_GET['timeStart']);
                    $post = strtotime($result['time']);
                    $end = strtotime($_GET['timeEnd']);
                    if ($post < $start || $post > $end) {
                        unset($results[$id]);
                    }
                } else if (isMethod('timeStart')) {
                    $start = strtotime($_GET['timeStart']);
                    $post = strtotime($result['time']);
                    if ($post < $start) {
                        unset($results[$id]);
                    }
                } else if (isMethod('timeEnd')) {
                    $post = strtotime($result['time']);
                    $end = strtotime($_GET['timeEnd']);
                    if ($post > $end) {
                        unset($results[$id]);
                    }
                }
            }
    
            foreach($results as $id => $result) {
                if (isMethod('creatorName')) {
                    if ($result['creator']['name'] != $_GET['creatorName']) {
                        unset($results[$id]);
                    }
                }
            }

            foreach($results as $id => $result) {
                if (isMethod('creatorEmail')) {
                    if ($result['creator']['email'] != $_GET['creatorEmail']) {
                        unset($results[$id]);
                    }
                }
            }
            
            foreach($results as $id => $result) {
                if (isMethod('data')) {
                    // TODO:
                    // use setup to make a /regular/ search on data
                    // search on ...Parsed but using strip_tags($html)
                }
            }
        }
        print(json_encode($results));
    } else {
        redirect($apiPath);
    }
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
            'src="emoticons\/' => 'src="'.$apiPath.'emoticons/',
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
        if (isPosted('edit') && isset($posts[$_POST['edit']-1])) {
            $posts[$_POST['edit']-1]["data"] = $newPost;
            $posts[$_POST['edit']-1]["lastEdited"] = array(
                "editor" => $user,
                "date" => date("d.m.Y"),
                "time" => date("H:i")
            );
        } else {
            $posts[] = array(
                "data" => $newPost,
                "creator" => $user,
                "date" => date("d.m.Y"),
                "time" => date("H:i"),
                "postID" => $replacements["POST_ID"]
            );
        }
        saveJSON($postsPath, $posts);
        redirect($apiPath);
    } else {
        render("add", array("msg"=> "Add a new post","format"=>$setup, "url"=>$apiPath, "action"=>"Add"));
    }
} else if (isMethod('edit')) {
    if (isset($posts[$_GET['edit']-1])) {
        render("add", array("msg"=> "Edit post #".$_GET['edit'],"format"=>$setup, "url"=>$apiPath, "post"=>$posts[$_GET['edit']-1], "action"=>"Edit", "extra"=>array("name"=>"edit", "value"=>$_GET['edit'])));
    } else {
        redirect($apiPath);
    }
} else if (isMethod('delete')) {
    if (isset($posts[$_GET['delete']-1])) {
        if (!isMethod('confirmed')) {
            // todo: create in setting option to select specific $req to be displayed as highlighted
            $first = null;
            foreach($setup as $req) {
                $first = $req['id'];
                break;
            }
            render("delete", array("id"=>$_GET['delete'], "info"=>$posts[$_GET['delete']-1]['data'][$first]));
        } else {
            $posts[$_GET['delete']-1] = null; // todo write a funciton to deal with that
            saveJSON($postsPath, $posts);
            redirect($apiPath);
        }
    } else {
        redirect($apiPath);
    }
} else {
    // todo: create in setting option to select specific $req to be displayed as highlighted
    $first = null;
    foreach($setup as $req) {
        $first = $req['id'];
        break;
    }
    render('dashboard', array('userName'=>$user['name'], 'posts'=>$posts, 'first'=>$first));
}

?>