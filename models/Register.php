<?php

class Register {

    private $conn;

    private $table = 'users';
    private $email;
    private $password;
    private $first_name;
    private $last_name;
    private $phone_no;
    private $pincode;
    private $address;
    private $id;

    public function __construct($db) {
        $this->conn = $db;
        // $this->get_post_data();
    }

    public function init_register_post() {
        // $data = json_decode(file_get_contents('php://input'));
        
        $this->email = $_POST['email'];
        $this->password = $_POST['password'];
        $this->first_name = $_POST['first_name'];
        $this->last_name = $_POST['last_name'];
        $this->phone_no = $_POST['phone_no'];
        // $this->pincode = $_POST['pincode'];
        // $this->address = $_POST['address'];
    }

    public function init_profile_update_post() {
        parse_str(file_get_contents("php://input"), $_PUT);

        foreach ($_PUT as $key => $value) {
            unset($_PUT[$key]);
            $_PUT[str_replace('amp;', '', $key)] = $value;
        }

        $this->id = $_PUT['id'];
        $this->first_name = $_PUT['first_name'];
        $this->last_name = $_PUT['last_name'];
        $this->pincode = $_PUT['pincode'];
        $this->address = $_PUT['address'];
    }

    public function register_user() {
        $this->init_register_post();

        $email_status = $this->is_email_unique();
        $phone_status = $this->is_phone_unique();

        if (!$email_status) {
            return ['error' => True, 'message' => 'E-Mail is already Taken!'];
        }

        if (!$phone_status) {
            return ['error' => True, 'message' => 'Phone Number is already Taken!'];
        }

        $query = 'INSERT INTO ' . $this->table . ' 
                  (email, password, first_name, last_name, phone_no) VALUES 
                  (:email, :password, :first_name, :last_name, :phone_no)';

        $statement = $this->conn->prepare($query);

        $statement->bindParam(':email', $this->email);
        $statement->bindParam(':password', password_hash($this->password, PASSWORD_DEFAULT));
        $statement->bindParam(':first_name', $this->first_name);
        $statement->bindParam(':last_name', $this->last_name);
        $statement->bindParam(':phone_no', $this->phone_no);

        try {
            $statement->execute(); 
        } catch (PDOException $exception) {
            return  ['error' => True, 'message' => $exception->getMessage()];
        }
        
        return ['error' => False, 'message' => 'Signup Successful!']; 
    }

    public function update_user_details() {
        $this->init_profile_update_post();

        $query = 'UPDATE users SET first_name = :first_name, last_name = :last_name,
                  pincode = :pincode, address = :address
                  WHERE id = :id';

        $statement = $this->conn->prepare($query);

        $statement->bindParam(':first_name', $this->first_name);
        $statement->bindParam(':last_name', $this->last_name);
        $statement->bindParam(':pincode', $this->pincode);
        $statement->bindParam(':address', $this->address);
        $statement->bindParam(':id', $this->id);

        try {
            $statement->execute();
        } catch (PDOException $exception) {
            return  ['error' => True, 'message' => $exception->getMessage()];
        }

        return ['error' => False, 'message' => 'Profile Updated Successful!'];
    }

    public function is_email_unique() {
        $query = 'SELECT * FROM users WHERE email = :email';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':email', $this->email);
        $statement->execute();

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return (empty($user)) ? True : False;
    }

    public function is_phone_unique() {
        $query = 'SELECT * FROM users WHERE phone_no = :phone_no';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':phone_no', $this->phone_no);
        $statement->execute();

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return (empty($user)) ? True : False;
    }
}