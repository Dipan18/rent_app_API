<?php

class Login {
    
    private $conn;
    private $table = 'users';

    public $email;
    public $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function authenticate() {
        // $data = json_decode(file_get_contents('php://input'));
        // $this->password = $data->password;
        
        $this->email = $_POST['email'];
        $this->password = $_POST['password'];        

        $query = 'SELECT * FROM ' . $this->table . ' WHERE email=' .  '\'' . $this->email . '\''; 
        $statement = $this->conn->prepare($query);
        $statement->execute();
        
        $user_data = $statement->fetch(PDO::FETCH_ASSOC);

        if (password_verify($this->password, $user_data['password'])) {
            return ['error' => False, 'message' => 'Login Successful'];
        } 

        return ['error' => True, 'message' => 'Invalid E-Mail OR Password'];
    }

    public function get_userdata() {
        $this->email = $_GET['email'];

        $query = 'SELECT * FROM ' . $this->table . ' WHERE email=' . '\'' . $this->email . '\'';
        $statement = $this->conn->prepare($query);
        $statement->execute();

        $user_data = $statement->fetch(PDO::FETCH_ASSOC);

        if (!empty($user_data)) {
            unset($user_data['password']);
            return $user_data; 
        } 
        
        return ['error' => TRUE, 'message' => 'No user found with this Email'];
    }
        
}