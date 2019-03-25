<?php
include('Products.php');

class Rent {
    private $conn;
    private $user_id;

    public function __construct($db) {
        $this->conn = $db;
        $this->user_id = $_GET['id'];
    }

    public function user_rented_products() {
        $id = $_GET['id']; // user id 

        $query = "SELECT rented_on, products_on_hold.pro_id, products_on_hold.pro_title, rent_status.status,
                  product_images_on_hold.img_name, 
                  product_images_on_hold.img_path, users.last_name, users.first_name, users.id
                  FROM rent_requests 
                  INNER JOIN products_on_hold ON products_on_hold.pro_id = rent_requests.pro_id
                  INNER JOIN rent_status ON rent_status.id = rent_requests.status
                  INNER JOIN product_images_on_hold ON products_on_hold.pro_id  = product_images_on_hold.pro_id
                  INNER JOIN users ON users.id = products_on_hold.user_id 
                  WHERE  buyer_id = :buyer_id AND rent_requests.status = 2
                  GROUP BY products_on_hold.pro_id";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':buyer_id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
        for ($i = 0; $i < sizeof($result); $i++) {
            $img[0] = array('img_name' => $result[$i]['img_name'], 'img_path' => $result[$i]['img_path']);
            $result[$i]['img_path'] = Products::fix_img_path($img);

            $result[$i]['days_remaining'] = $this->days_remaining($result[$i]['pro_id'])[0];
            $result[$i]['user']['first_name'] = $result[$i]['first_name'];
            $result[$i]['user']['last_name'] = $result[$i]['last_name'];
            $result[$i]['user']['id'] = $result[$i]['id'];

            unset($result[$i]['img_name']);
            unset($result[$i]['first_name']);
            unset($result[$i]['last_name']);
            unset($result[$i]['id']);
        }
        return $result;
    }

    private function days_remaining($pro_id) {
        $query = 'call days_remaining(:pro_id)';
                
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':pro_id', $pro_id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch(PDO::FETCH_NUM);
    }

    // These will fetch the requests that you as a buyer made on someone else's product
    public function get_my_pending_requests() {
        $query = 'SELECT requested_on, products_on_hold.pro_id, products_on_hold.pro_title, rent_status.status,
                product_images_on_hold.img_name, product_images_on_hold.img_path,
                users.first_name, users.last_name
                FROM rent_requests
                INNER JOIN products_on_hold
                ON products_on_hold.pro_id = rent_requests.pro_id
                INNER JOIN product_images_on_hold
                ON products_on_hold.pro_id = product_images_on_hold.pro_id
                INNER JOIN rent_status
                ON rent_status.id = rent_requests.status
                INNER JOIN users
                ON products_on_hold.user_id = users.id
                WHERE buyer_id = :id AND rent_requests.status = 1
                GROUP BY products_on_hold.pro_id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $this->user_id);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC); 
    }

    public function get_my_rejected_requests() {
        $query = 'SELECT requested_on, products.pro_id, products.pro_title, rent_status.status,
                product_images.img_name, product_images.img_path,
                users.first_name, users.last_name
                FROM products
                INNER JOIN rejected_requests 
                ON rejected_requests.pro_id = products.pro_id
                INNER JOIN product_images
                ON product_images.pro_id = products.pro_id
                INNER JOIN rent_status
                ON rent_status.id = rejected_requests.status
                INNER JOIN users
                ON users.id = products.user_id
                WHERE buyer_id = :id
                GROUP BY products.pro_id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $this->user_id);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function merge_requests_with_rented_items() {
        $requests = array_merge($this->get_my_pending_requests(), $this->get_my_rejected_requests());

        for ($i = 0; $i < sizeof($requests); $i++) {
            $img[0] = array('img_name' => $requests[$i]['img_name'], 'img_path' => $requests[$i]['img_path']);
            $requests[$i]['img_path'] = Products::fix_img_path($img);

            $requests[$i]['user']['first_name'] = $requests[$i]['first_name'];
            $requests[$i]['user']['last_name'] = $requests[$i]['last_name'];

            unset($requests[$i]['img_name']);
            unset($requests[$i]['first_name']);
            unset($requests[$i]['last_name']);
        }

        return array_merge($requests, $this->user_rented_products());
    }

    // These will fetch the requests other users have made on my products
    public function get_requests_on_my_products() {
        $query = 'SELECT products_on_hold.pro_id, products_on_hold.pro_title,
                rent_requests.buyer_id, rent_requests.status, rent_requests.requested_on, rent_requests.rented_on, rent_requests.expiry_date,
                rent_status.status,
                users.first_name, users.last_name,
                product_images_on_hold.img_name, product_images_on_hold.img_path
                FROM products_on_hold
                INNER JOIN rent_requests
                ON products_on_hold.pro_id = rent_requests.pro_id
                INNER JOIN product_images_on_hold
                ON product_images_on_hold.pro_id = products_on_hold.pro_id
                INNER JOIN rent_status
                ON rent_requests.status = rent_status.id
                INNER JOIN users
                ON rent_requests.buyer_id = users.id
                WHERE products_on_hold.user_id = :id
                GROUP BY products_on_hold.pro_id
                ORDER BY rent_requests.status ASC';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $this->user_id);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rejected_requests_by_me() {
        $query = 'SELECT products.pro_id, products.pro_title,
                rejected_requests.buyer_id, rejected_requests.requested_on,
                rent_status.status,
                users.first_name, users.last_name,
                product_images.img_name, product_images.img_path
                FROM products
                INNER JOIN rejected_requests
                ON products.pro_id = rejected_requests.pro_id
                INNER JOIN product_images
                ON product_images.pro_id = products.pro_id
                INNER JOIN rent_status
                ON rejected_requests.status = rent_status.id
                INNER JOIN users
                ON rejected_requests.buyer_id = users.id
                WHERE products.user_id = :id
                GROUP BY products.pro_id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $this->user_id);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_all_requests_on_my_products() {
        $requests = array_merge($this->get_requests_on_my_products(), $this->rejected_requests_by_me());

        for ($i = 0; $i < sizeof($requests); $i++) {
            $img[0] = array('img_name' => $requests[$i]['img_name'], 'img_path' => $requests[$i]['img_path']);
            $requests[$i]['img_path'] = Products::fix_img_path($img);
            
            $requests[$i]['user']['id'] = $requests[$i]['buyer_id'];
            $requests[$i]['user']['first_name'] = $requests[$i]['first_name'];
            $requests[$i]['user']['last_name'] = $requests[$i]['last_name'];
            
            unset($requests[$i]['first_name']);
            unset($requests[$i]['last_name']);
            unset($requests[$i]['img_name']);
            unset($requests[$i]['buyer_id']);
        }

        return $requests;
    }

    public function accept_rent_request() {
        $pro_id = $_GET['id'];

        $rent_period = 'SELECT rent_period FROM products_on_hold
                        WHERE pro_id= :pro_id';
            
        $statement = $this->conn->prepare($rent_period);
        $statement->bindParam(':pro_id', $pro_id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_NUM);

        $query = 'UPDATE rent_requests
                SET rented_on = CURRENT_TIMESTAMP,
                status = 2,
                expiry_date = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ' . $result[0] .
                ' DAY) WHERE pro_id = :id';

        $stmnt = $this->conn->prepare($query);
        $stmnt->bindParam('id', $pro_id);
        $stmnt->execute();

        return ($stmnt->rowCount() < 1) ? ['error'=>True, 'message'=>'Failed To Accept Request'] : 
                                          ['error'=>False, 'message'=>'Request Accepted Successfully!'];
    }

    public function reject_rent_request() {
        $pro_id = $_GET['id'];
        try {
            $this->conn->beginTransaction();

            $this->move_request_to_rejected($pro_id);

            $this->move_product_on_hold_to_products($pro_id);
    
            $this->move_images_on_hold_to_images($pro_id);
    
            $this->delete_product_from_hold($pro_id);
         
            //commit changes in case of no exception
            $this->conn->commit();

            return ['error' => false, 'message' => 'Request Rejected Successfully!'];
        } catch (PDOException $exception) {
            //Exception occurred rollback changes
            $this->conn->rollback(); 

            return ['error' => true, 'message' => 'Failed To Reject Request!'];
        }        
    }

    private function move_request_to_rejected($pro_id) {
        $insert_data = 'SELECT pro_id, buyer_id, requested_on
                        FROM rent_requests
                        WHERE pro_id = :id';

        $statement = $this->conn->prepare($insert_data);
        $statement->bindParam('id', $pro_id, PDO::PARAM_INT);
        $statement->execute();
        
        $data = $statement->fetch(PDO::FETCH_ASSOC);

        $query = 'INSERT INTO rejected_requests
                  (buyer_id, pro_id, requested_on) VALUES
                  (:buyer_id, :pro_id, :requested_on)';

        $stmnt = $this->conn->prepare($query);
        $stmnt->bindParam(':buyer_id', $data['buyer_id']);
        $stmnt->bindParam('pro_id', $data['pro_id']);
        $stmnt->bindParam('requested_on', $data['requested_on']);

        $stmnt->execute();
    }

    private function move_product_on_hold_to_products($pro_id) {
        $query = 'INSERT INTO products
                  SELECT * FROM products_on_hold
                  WHERE products_on_hold.pro_id = :id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $pro_id);
        $statement->execute();
    }

    private function move_images_on_hold_to_images($pro_id) {
        $query = 'INSERT INTO product_images
                  SELECT * FROM product_images_on_hold
                  WHERE product_images_on_hold.pro_id = :id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $pro_id);
        $statement->execute();
    }

    private function delete_product_from_hold($pro_id) {
        $query = 'DELETE FROM products_on_hold
                  WHERE pro_id = :id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $pro_id);
        $statement->execute();
    }

    public function rent_item() {
        $item_id = $_GET['id'];
        $buyer_id = $_GET['buyer_id'];

        try {
            $this->conn->beginTransaction();

            $this->move_product_to_products_on_hold($item_id);

            $this->move_images_to_images_on_hold($item_id);
    
            $this->insert_rent_request($item_id, $buyer_id);
    
            $this->remove_product($item_id);

            $this->conn->commit();

            return ['error' => False, 'message' => 'Rent Request Sent Successfully To Owner!'];
        } catch (PDOException $exception) {
            $this->conn->rollback();

            return ['error' => True, 'message' => 'Failed To Send Rent Request To Owner'];
        }
    }

    private function move_product_to_products_on_hold($item_id) {
        $query = 'INSERT INTO products_on_hold
                  SELECT * FROM products
                  WHERE products.pro_id = :id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $item_id, PDO::PARAM_INT);
        $statement->execute();
    }

    private function move_images_to_images_on_hold($item_id) {
        $query = 'INSERT INTO product_images_on_hold
                  SELECT * FROM product_images
                  WHERE product_images.pro_id = :id';
        
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $item_id, PDO::PARAM_INT);
        $statement->execute();
    }

    private function insert_rent_request($item_id, $buyer_id) {
        $query = 'INSERT INTO rent_requests (pro_id, buyer_id)
                  VALUES (:pro_id, :buyer_id)';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':pro_id', $item_id, PDO::PARAM_INT);
        $statement->bindParam(':buyer_id', $buyer_id, PDO::PARAM_INT);
        $statement->execute();
    }

    private function remove_product($item_id) {
        $query = 'DELETE FROM products WHERE pro_id = :id';

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':id', $item_id, PDO::PARAM_INT);
        $statement->execute();
    }
}