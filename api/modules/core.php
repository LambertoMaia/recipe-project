<?php

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
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}