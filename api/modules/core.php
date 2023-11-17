<?php
session_start();
class Core {
    private $db;
    public function __construct() {
        // sqlite db connection
        try {
            $this->db = new PDO("sqlite:database/db.sqlite3");
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