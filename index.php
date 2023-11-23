<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/api/modules/core.php";

$worker = new Core();
$user   = new User();

$klein = new \Klein\Klein();

// Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/templates");
$twig   = new \Twig\Environment($loader, [
    'debug' => true,
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());

$klein->respond("GET", "/", function () use ($twig, $worker) { 
    $recipes = $worker->get_recipes(12, true);

    return $twig->render("home.twig", [
        "recipes" => $worker->get_best()
    ]);
});

$klein->respond("GET", "/receitas", function () use ($worker, $twig) {
    $recipes = $worker->get_recipes(12, true);

    return $twig->render("receitas.twig", [
        "recipes" => $recipes
    ]);
});

$klein->respond("GET", "/favoritos", function () use ($twig, $user) {
    return $twig->render("favoritos.twig", [
        "loggedIn" => $user->check_session(),
        "favourites" => $user->get_favourites()
    ]);
});

$klein->respond("GET", "/login", function () use ($twig) {
    return $twig->render("login.twig");
});

$klein->respond("GET", "/cadastro", function () use ($twig) {
    return $twig->render("cadastro.twig");
});

// API //

$klein->respond("GET", "/api/recipes/search/[:query]", function ($request) use ($twig, $worker) {
    $recipes = $worker->search_recipes($request->query);

    Header('Content-Type: application/json');
    return json_encode($recipes);
});

$klein->respond("GET", "/api/recipes/load", function ($request) use ($worker) {
    $recipes = $worker->get_recipes(12, true);

    Header('Content-Type: application/json');
    return json_encode($recipes);
});

$klein->respond("GET", "/api/recipe/[:query]", function ($request) use ($worker) {
    $recipe = $worker->get_recipe($request->query);

    Header("Content-Type: application/json");
    return json_encode($recipe);
});

$klein->respond("POST", "/login", function ($request) use ($user) {
    Header("Content-Type: application/json");

    $username = $request->param("username");
    $password = $request->param("password");

    return $user->login($username, $password);
});

$klein->respond("POST", "/cadastro", function ($request) use ($user) {
    Header("Content-Type: application/json");

    $username = $request->param("username");
    $password = $request->param("password");
    $email    = $request->param("email");

    return $user->register([
        "username" => $username,
        "password" => $password,
        "email"    => $email
    ]);
});

$klein->respond("POST", "/avaliar", function ($request) use ($worker) {
    Header("Content-Type: application/json");

    $recipe_id = $request->param("recipe_id");
    $rating    = $request->param("rating");

    return $worker->rate_recipe($recipe_id, $rating);
});

$klein->respond("POST", "/favoritar", function ($request) use ($worker) {
    Header("Content-Type: application/json");

    $recipe_id = $request->param("recipe_id");

    return $worker->set_favourite($recipe_id);
});

// DEBUG //

$klein->respond("GET", "/debug", function () use ($worker, $user) {
    session_destroy();
});

$klein->onHttpError(function ($code, $router) use ($twig) {
     if ($code === 404) {
        $router->response()->body($twig->render("404.twig"));
    }
});

$klein->dispatch();