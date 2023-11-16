<?php
require_once "modules/core.php";

$worker = new Core();

// API Router
$request_method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

if ($request_method != 'POST' && $request_method != 'GET') {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request method'));
    exit();
}

if ($request_method == 'GET') {
    $available_actions = ['get_recipe', 'get_recipes', 'search', 'check_permission'];

    if (!isset($_GET['action'])) {
        echo json_encode(array('status' => 'error', 'message' => 'No action specified'));
        exit();    
    }

    if (!in_array($_GET['action'], $available_actions)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid action'));
        exit();
    }

    $action = $_GET['action'];

    if($action == 'get_recipe') {
        if (!isset($_GET['value'])) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Missing value parameter'
            ));
            exit();
        }

        $recipe = $worker->get_recipe(intval($_GET['value']));

        if ($recipe == false) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Recipe not found!'
            ));
            exit();
        }

        echo json_encode($recipe);
    }

    if($action == 'search') {
        if (!isset($_GET['value'])) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Missing value parameter'
            ));
            exit();
        }

        $search = $worker->search_recipes($_GET['value']);

        if (empty($search)) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'No recipes found!'
            ));
            exit();
        }

        echo json_encode($search);
    }

    if($action == 'get_recipes') {
        $limit = 30;
        $random = false;

        if (!empty($_GET['value'])) {
            $limit = intval($_GET['value']);
        }

        if (!empty($_GET['random'])) {
            if (intval($_GET['random']) == 1) {
                $random = true;
            }
        }

        echo json_encode($worker->get_recipes($limit, $random));
    }

    if($action == 'check_permission') {
        $admin = new Admin();
        $permission = $admin->check_permission();

        echo $permission;
    }
}

if ($request_method === "POST") {
    $action = $_POST['action'];

    if ($action === "login") {
        if(!isset($_POST['username']) || !isset($_POST['password'])) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Verifique se todos os campos foram preenchidos corretamente!"
            ));
        } 

        $admin = new Admin();
        echo $admin->login($_POST['username'], $_POST['password']);
    }

    if ($action === "add_recipe") {
        $data = $_POST['values'];

        echo $worker->add_recipe($data);
    }

}