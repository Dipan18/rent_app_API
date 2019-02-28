<?php

Class Ad {

    private $conn;

    private $images;
    private $user_id;
    private $pro_title;
    private $pro_desc;
    private $cat_id;
    private $pro_price;
    private $rent_period;
    private $pincode;
    private $address;

    private $folder_path;
    private $image_names = [];


    public function __construct($db) {
        $this->conn = $db;

        $this->user_id = $_POST['user_id'];
        $this->pro_title = $_POST['pro_title'];
        $this->pro_desc = $_POST['pro_desc'];
        $this->cat_id = $_POST['cat_id'];
        $this->pro_price = $_POST['pro_price'];
        $this->rent_period = $_POST['rent_period'];
        $this->pincode = $_POST['pincode'];
        $this->address = $_POST['address'];
        $this->images = $_FILES['ProductImages'];
    }

    public function re_array_files(&$file_post) {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

    private function get_user_phone_number() {
        $query = 'SELECT phone_no FROM users WHERE id = :id';
        
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $this->user_id, PDO::PARAM_INT);

        $statement->execute();

        $phone_no = $statement->fetch(PDO::FETCH_ASSOC);

        return $phone_no['phone_no'];
    }

    private function create_folder() {
        date_default_timezone_set("Asia/Calcutta");
        // echo date("j"); // day
        // echo date("n"); // month
        // echo date("y"); // year
        // echo date("i"); // minutes
        // echo date("s"); // seconds
        $phone_no = $this->get_user_phone_number();

        $sub_folder = date('j') . date ('n') . date('y') . date('i') . date('s');
        $this->folder_path = '../../codeigniter/uploads/' . $phone_no . '/' . $sub_folder;

        // make directory if it does not exist
        if (!is_dir($this->folder_path)) { mkdir($this->folder_path, 0777, true); }
    }

    private function move_uploaded_files() {
        $files_moved_successfully = true;
        $this->create_folder();
        
        foreach ($this->images as $image) {
            if ($image['error'] === 0) {
                $tmp_name = $image['tmp_name'];
                $name = basename($image['name']);
        
                array_push($this->image_names, $name);
                $path = $this->folder_path . '/' . $name;
                
                if (!move_uploaded_file($tmp_name, $path)) {
                    $files_moved_successfully = false;
                    break;
                }
            }
        }
        return $files_moved_successfully;
    }

    private function insert_ad_data_db() {
        $query = 'INSERT into products 
                  (user_id, pro_title, pro_desc, pro_price, pro_cat, rent_period, pro_pincode, pro_address) VALUES
                  (:user_id, :pro_title, :pro_desc, :pro_price, :pro_cat, :rent_period, :pro_pincode, :pro_address)';

        $statement = $this->conn->prepare($query);

        $statement->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $statement->bindParam(':pro_title', trim($this->pro_title, '"'));
        $statement->bindParam(':pro_desc', trim($this->pro_desc, '"'));
        $statement->bindParam(':pro_price', $this->pro_price);
        $statement->bindParam(':pro_cat', $this->cat_id, PDO::PARAM_INT);
        $statement->bindParam(':rent_period', $this->rent_period, PDO::PARAM_INT);
        $statement->bindParam(':pro_pincode', $this->pincode, PDO::PARAM_INT);
        $statement->bindParam(':pro_address', trim($this->address, '"'));

        $statement->execute(); 
    }

    private function insert_product_images($pro_id) {
        $column_names = array('pro_id', 'img_name', 'img_path');
        $values = array();

        $absolute_path = str_replace('\\', '/', realpath($this->folder_path));

        foreach ($this->image_names as $image_name) {
            array_push($values, $pro_id, $image_name, $absolute_path);
        }

        $row_places = '(' . implode(', ', array_fill(0, count($column_names), '?')) . ')';
        $all_places = implode(', ', array_fill(0, count($this->image_names), $row_places));

        $sql = "INSERT INTO product_images (" . implode(', ', $column_names) . 
            ") VALUES ". $all_places;

        $statement = $this->conn->prepare($sql);
        $statement->execute($values);
    } 

    public function submit_ad() {
        $this->images = $this->re_array_files($this->images);

        // Moving files from temporary location to upload folder successful
        if ($this->move_uploaded_files()) {
            try {
                $this->conn->beginTransaction();

                //Query 1: Attempt to insert AD data in products table
                $this->insert_ad_data_db();

                // Get the id of newly inserted row in products table(for referencing pro_id inside images table)
                $id = $this->conn->lastInsertId();

                //Query 2: Attempt to insert images metadata inside product_images table
                $this->insert_product_images($id);

                //commit changes in case of no exception
                $this->conn->commit();
            } catch (PDOException $exception) {
                echo $exception->getMessage();
                //Exception occurred rollback changes
                $this->conn->rollback(); 

                //Remove images folder from uploads directory
                $this->rmdir_recursive($this->folder_path);
            }
        } else { 
            echo 'error moving files';
        }
    }
    
    public function rmdir_recursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }
}