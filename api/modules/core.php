<?php
session_start();

class Core {
    private $db;
    public function __construct() {
        // sqlite db connection
        try {
            $this->db = new PDO("sqlite:api/database/db.sqlite3");
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public function search_recipes(string $search) {
        $search = '%' . $search . '%';
        // Searches for recepies by name or ingredients
        $sql = "SELECT * FROM recipes WHERE name LIKE :search OR ingredients LIKE :search";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search);

        $run = $stmt->execute();

        if($run === false) {
            return false;
        }

        $fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Removing /n from ingredients
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = str_replace("\n", "", $value['ingredients']);
        }

        // Removing /n from instructions
        foreach($fetch as $key => $value) {
            $fetch[$key]['instructions'] = str_replace("\n", "", $value['instructions']);
        }

        // Removing any 'comma' that comes before a 'dot'
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = str_replace(".,", ".", $value['ingredients']);
        }

        // Separating ingredients by comma as an array
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = explode(",", trim($value['ingredients']));
        }

        return $fetch;
    }

    public function get_recipes(int $limit, bool $random) {
        $random_limit = $limit;

        if($random) {
            $limit = 1000;
        }

        $query = "SELECT * FROM recipes LIMIT $limit";
        $stmt = $this->db->prepare($query);

        $run = $stmt->execute();

        if (!$run) {
            return false;
        }

        $fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Removing /n from ingredients
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = str_replace("\n", "", $value['ingredients']);
        }

        // Removing /n from instructions
        foreach($fetch as $key => $value) {
            $fetch[$key]['instructions'] = str_replace("\n", "", $value['instructions']);
        }

        // Removing any 'comma' that comes before a 'dot'
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = str_replace(".,", ".", $value['ingredients']);
        }

        // Separating ingredients by comma as an array
        foreach($fetch as $key => $value) {
            $fetch[$key]['ingredients'] = explode(",", trim($value['ingredients']));
        }

        if($random) {
            shuffle($fetch);
            $fetch = array_slice($fetch, 0, $random_limit);
        }
        
        return $fetch;
    }

    public function get_recipe(int $recipe_id) {
        if(empty($recipe_id)) {
            return false;
        }

        $query = "SELECT * FROM recipes WHERE id = :id";
        $stmt  = $this->db->prepare($query);
        $stmt->bindValue(':id', $recipe_id, PDO::PARAM_INT);
        $run = $stmt->execute();

        if(!$run) {
            return false;
        } else {
            $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
            $fetch['ingredients'] = explode(",", trim($fetch['ingredients']));
            return $fetch;
        }
    }  

    public function check_fields(array $data) {
        $fields = [
            "name", "category", "instructions", "ingredients",
            "measures", "thumb", "preptime"
        ];
    
        // Convert $fields to an associative array
        $fields_assoc = array_flip($fields);
        
        // Check if $data contains all the keys in $fields
        if (count(array_diff_key($fields_assoc, $data)) !== 0) {
            return false;
        } else {
            return true;
        }
    }

    public function add_recipe(array $data) {
        if (!$this->check_fields($data)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Verifique se todos os campos foram preenchidos corretamente!"
            ));
        }

        $sql    = "INSERT INTO recipes (name, category, instructions, ingredients, measures, thumb, preptime) VALUES (:name, :category, :instructions, :ingredients, :measures, :thumb, :preptime)";
        $stmt   = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':category', $data['category']);
        $stmt->bindValue(':instructions', $data['instructions']);
        $stmt->bindValue(':ingredients', $data['ingredients']);
        $stmt->bindValue(':measures', $data['measures']);
        $stmt->bindValue(':thumb', $data['thumb']);
        $stmt->bindValue(':preptime', $data['preptime']);

        if(!$stmt->execute()) {
            return json_encode(array(
                "status" => "error",
                "message" => "Error ao inserir receita no banco de dados!"
            ));
        }

        return json_encode(array(
            "status" => "ok",
            "message" => "Receita inserida no banco de dados"
        ));
    }

    public function edit_recipe(int $id, array $data) {
        if (!$this->check_fields($data)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Verifique se todos os campos foram preenchidos corretamente"
            ));
        }

        $sql = "UPDATE recipes SET = name = :name, category = :category, instructions = :instructions, ingredients = :ingredients, measures = :measures, thumb = :thumb, preptime = :preptime WHERE id = :id";
        $prepare = $this->db->prepare($sql);
        $prepare->bindValue(":name", $data['name']);
        $prepare->bindValue(":category", $data['category']);
        $prepare->bindValue(":instructions", $data['instructions']);
        $prepare->bindValue(":ingredients", $data['ingredients']);
        $prepare->bindValue(":measures", $data['measures']);
        $prepare->bindValue(":thumb", $data['thumb']);
        $prepare->bindValue(":preptime", $data['preptime']);

        if (!$prepare->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Ocorreu um error ao salvar as informações!",
                "sql"       => $prepare->errorInfo()
            ));
        }

        return json_encode(array(
            "status"    => "ok",
            "message"   => "Informações da receita foram alteradas com sucesso!"
        ));
    }

    public function remove_recipe(int $id) {
        if(empty($id)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar o ID"
            ));
        }

        if(!isset($_SESSION['admin_login'])) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Essa ação não é permitida"
            ));
        }

        $sql = "DELETE FROM recipes WHERE id = :id";
        $prepare = $this->db->prepare($sql);
        $prepare->bindValue(":id", $id);
        
        if (!$prepare->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível remover esta receita",
            ));
        }

        return json_encode(array(
            "status"    => "ok",
            "message"   => "A receita foi removida com sucesso!"
        ));
    }

    public function get_categories() {
        $sql    = "SELECT DISTINCT category FROM recipes";
        $stmt   = $this->db->prepare($sql);
        
        if ($stmt->execute()) {
            $fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $list = array();
            $list['categories'] = array();
            
            foreach($fetch as $key => $value) {
                array_push($list['categories'], str_replace("-", " ", $value['category']));
            }

            return json_encode($list);
        } else {
            return json_encode(array(
                "status"    => "error",
                "message"   => $stmt->errorInfo()
            ));
        }
    }

    public function rate_recipe(int $id, int $rate) {
        $user = new User();

        if (!$user->check_session()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Você precisa estar logado para avaliar uma receita!"
            ));
        }

        $recipe = $this->get_recipe($id);
        
        if ($rate > 5 || $rate < 1) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível avaliar a receita!"
            ));
        }

        if (empty($recipe) || !$recipe) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível avaliar esta receita!"
            ));
        }

        // Getting use rates
        $sql    = "SELECT rates FROM users WHERE id = :id";
        $stmt   = $this->db->prepare($sql);
        $stmt->bindValue(":id", $_SESSION['user_id']);

        if (!$stmt->execute()) {   
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível avaliar esta receita."
            ));
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $rates = explode(",", $fetch['rates']);

        if (in_array($id, $rates)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Você já avaliou esta receita!"
            ));
        }

        array_push($rates, $id);
        $rates = implode(",", $rates);

        $sql = "UPDATE users SET rates = :rates WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":rates", $rates);
        $stmt->bindValue(":id", $_SESSION['user_id']);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível avaliar esta receita!"
            ));
        }

        $sql = "SELECT rating FROM recipes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":id", $id);

        $stmt->execute();

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

        $rating = explode(",", $fetch['rating']);
        
        $rating[0] = (int) $rating[0] + $rate;
        $rating[1] = (int) $rating[1] + 1;

        $rating = implode(",", $rating);

        $update = "UPDATE recipes SET rating = :rating WHERE id = :id";
        $stmt = $this->db->prepare($update);
        $stmt->bindValue(":rating", $rating);
        $stmt->bindValue(":id", $id);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível avaliar esta receita!"
            ));
        }

        return json_encode(array(
            "status"    => "ok",
            "message"   => "Receita avaliada"
        ));
    }

    public function set_favourite(int $id) {
        if (empty($id)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar o ID"
            ));
        }

        $recipe = $this->get_recipe($id);

        if (empty($recipe) || !$recipe) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar a receita"
            ));
        }

        $user = new User();

        if (!$user->check_session()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Você precisa estar logado para adicionar uma receita aos seus favoritos!"
            ));
        }

        $sql = "SELECT favourites FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":id", $_SESSION['user_id']);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível adicionar esta receita aos seus favoritos!"
            ));
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $favourites = explode(",", $fetch['favourites']);

        if (in_array($id, $favourites)) {
            // Removing from the favourites
            $key = array_search($id, $favourites);
            unset($favourites[$key]);

            $favourites = implode(",", $favourites);

            $update_sql = "UPDATE users SET favourites = :favourite WHERE id = :id";
            $stmt = $this->db->prepare($update_sql);
            $stmt->bindValue(":favourite", $favourites);
            $stmt->bindValue(":id", $_SESSION['user_id']);

            if (!$stmt->execute()) {
                return json_encode(array(
                    "status"    => "error",
                    "message"   => "Não foi possível remover esta receita aos seus favoritos!"
                ));
            }

            return json_encode(array(
                "status"    => "ok",
                "message"   => "Receita removida dos seus favoritos!"
            ));
        }

        array_push($favourites, $id);

        $favourites = implode(",", $favourites);

        $update_sql = "UPDATE users SET favourites = :favourite WHERE id = :id";
        $stmt = $this->db->prepare($update_sql);
        $stmt->bindValue(":favourite", $favourites);
        $stmt->bindValue(":id", $_SESSION['user_id']);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível adicionar esta receita aos seus favoritos!"
            ));
        }

        return json_encode(array(
            "status"    => "ok",
            "message"   => "Receita adicionada aos seus favoritos!"
        ));
    }

    public function get_average(int $id) {
        if (empty($id)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar o ID"
            ));
        }

        $recipe = $this->get_recipe($id);

        if (empty($recipe) || !$recipe) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar a receita"
            ));
        }

        $rating = explode(",", $recipe['rating']);

        if ($rating[1] === 0) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Esta receita ainda não foi avaliada!"
            ));
        }

        $average = $rating[0] / $rating[1];

        return json_encode(array(
            "status"    => "ok",
            "message"   => round($average, 2)
        ));
    }

    public function get_best() {
        // Returns the top three most rated recipes
        $sql = "SELECT * FROM recipes ORDER BY rating DESC LIMIT 3";
        $stmt = $this->db->prepare($sql);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar as receitas mais bem avaliadas!"
            ));
        }

        $fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $fetch;
    }

    public function get_db() {
        return $this->db;
    }
}

class Admin {
    private $username = "admin";
    private $password = "admin";

    public function login(string $username, string $password) {
        if (empty($username) || empty($password)) {
            return json_encode(array(
                "status" => "error",
                "message" => "Senha e/ou usuário incorretos"
            ));
        }

        if (!isset($_SESSION['admin_login'])) {
            if ($username === $this->username && $password === $this->password) {
                $_SESSION['admin_login'] = 1;
                return json_encode(array(
                    "status" => "ok",
                    "message" => "password"
                ));
            } else {
                return json_encode(array(
                    "status" => "error",
                    "message" => "Usuário e/ou senha inválido!"
                ));
            }
        } else {
            return json_encode(array(
                "status" => "ok",
                "message" => "session"
            ));
        }
    }

    public function logout() {
        if (isset($_SESSION['admin_login'])) {
            session_destroy();
            return json_encode(array(
                "status" => "ok",
                "message" => null
            ));
        }
    }

    public function check_permission() {
        if(isset($_SESSION['admin_login'])) {
            return json_encode(array(
                "status" => "ok"
            ));
        } else {
            return json_encode(array(
                "status" => "error"
            ));
        }
    }
}

class User {
    private $username;
    private $email;
    private $db;
    
    public $core;

    public function __construct() {
        $this->core = new Core();
        $this->db = $this->core->get_db();
    }

    public function check_session() {
        if (!isset($_SESSION['username']) || !isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
            return false;
        }

        return true;
    }

    public function login(string $username, string $password) {

        if (isset($_SESSION['username']) && isset($_SESSION['email']) && isset($_SESSION['user_id'])) {
            return json_encode(array(
                "status"    => "ok",
                "message"   => "session"
            ));
        }

        if (empty($username) || empty($password)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Preencha todos os campos!"
            ));
        }

        $sql_user = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($sql_user);
        $stmt->bindValue(":username", $username);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar o usuário!",
                "sqlError"  => $stmt->errorInfo()
            ));
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fetch) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Usuário e/ou senha incorretos!"
            ));
        }

        if (!password_verify($password, $fetch['password'])) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Usuário e/ou senha incorretos!"
            ));
        }

        $current_datetime = date('Y-m-d H:i:s');
        $update_sql = "UPDATE users SET last_login = :date WHERE id = :userid";

        $stmt = $this->db->prepare($update_sql);
        $stmt->bindValue(":date", $current_datetime);
        $stmt->bindValue(":userid", $fetch['id']);

        $stmt->execute();

        $_SESSION['username'] = $fetch['username'];
        $_SESSION['email']    = $fetch['email'];
        $_SESSION['user_id']  = $fetch['id'];

        return json_encode(array(
            "status"    => "ok",
            "message"   => null
        ));
    }

    public function register(array $data) {
        if (empty($data)) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Verifique se todos os campos foram preenchidos corretamente!"
            ));
        }

        $check = "SELECT username FROM users WHERE username = :username";
        $stmt = $this->db->prepare($check);
        $stmt->bindValue(":username", $data['username']);
        
        if($stmt->execute()) {
            $fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(count($fetch) > 0) {
                return json_encode(array(
                    "status"    => "error",
                    "message"   => "Este usuário já existe!"
                ));
            }
        }

        $sql = 
            "
                INSERT INTO users (username, email, password) 
                VALUES (:username, :email, :password) 
            ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":username", $data['username']);
        $stmt->bindValue(":password", password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(":email", $data['email']);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível efetuar o cadastro!",
                "sql"       => $stmt->errorInfo()
            ));
        }

        return json_encode(array(
            "status"    => "ok",
            "message"   => "Usuário cadastrado!"
        ));
    }

    public function get_favourites() {
        if (!$this->check_session()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "User not logged in!"
            ));
        }

        $sql = "SELECT favourites FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":id", $_SESSION['user_id']);

        if (!$stmt->execute()) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Não foi possível encontrar os favoritos!"
            ));
        }
        
        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $favourites = explode(",", $fetch['favourites']);

        $list = array();   

        if (count($favourites) === 1 && empty($favourites[0])) {
            return json_encode(array(
                "status"    => "error",
                "message"   => "Você não possui receitas favoritas!"
            ));
        }

        foreach($favourites as $key => $value) {
            $recipe = $this->core->get_recipe((int) $value);
            array_push($list, $recipe);
        }
        return $list;
    }

    public function get_rated_recipes() {

    }
}

