<?php 

class Products {
    
    private $conn;
    private $categories;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function get_categories() {
        $query = 'SELECT * FROM categories';

        $statement = $this->conn->prepare($query);
        $statement->execute();

        $this->categories = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->categories;
    }

    public function get_products() {
        $query =   'select products.pro_id, pro_title, img_name, img_path
                    from product_images 
                    inner join products
                    on product_images.pro_id = products.pro_id
                    GROUP BY products.pro_id';

        $statement = $this->conn->prepare($query);
        $statement->execute();

        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

        return (isset($products)) ? $products : ['error' => 'No products found'];
    }

    public function products_by_category() {
        $category_id = $_GET['category_id'];

        $query = 'select products.pro_id, pro_cat, pro_price, pro_title, img_name, img_path
                 from product_images 
                 inner join products
                 on product_images.pro_id = products.pro_id
                 WHERE products.pro_cat = ' . $category_id .
                 ' GROUP BY products.pro_id';
    
        $statement = $this->conn->prepare($query);
        $statement->execute();

        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($products)) ? $products : ['error' => True, 'message' => 'No products with this category'];
    }

    public function product_details() {
        $product_id = $_GET['product_id'];
      
        $query =  'SELECT products.pro_id, products.user_id, products.pro_title, products.pro_desc, 
                   products.pro_price, products.rent_period, products.pro_pincode, products.pro_address,
                   products.created_at, categories.cat_name, users.first_name, users.last_name,
                   users.email, users.phone_no from products 
                   INNER JOIN categories 
                   ON products.pro_cat = categories.cat_id
                   INNER JOIN users
                   ON products.user_id = users.id
                   WHERE pro_id = ' . $product_id;

        $statement = $this->conn->prepare($query);
        $statement->execute();

        $product_details = $statement->fetchAll(PDO::FETCH_ASSOC);
        $images = ['img_path' =>  $this->product_images($product_id)];
        $user = ['first_name' => $product_details[0]['first_name'],
                 'last_name' => $product_details[0]['last_name'],
                 'email' => $product_details[0]['email'],
                 'phone_no' => $product_details[0]['phone_no']];

        unset($product_details[0]['first_name']);
        unset($product_details[0]['last_name']);
        unset($product_details[0]['email']);
        unset($product_details[0]['phone_no']);

        $product = array_merge($product_details[0], $images);
        $product['user'] = $user;
      
        return (!empty($product_details)) ? $product : ['error' => True, 'message' => 'No product found'];
    }

    public function product_images($pro_id) {
      $query = 'SELECT img_name, img_path FROM product_images
                WHERE pro_id = :pro_id';

      $statement = $this->conn->prepare($query);
      $statement->bindParam(':pro_id', $pro_id);

      $statement->execute();

      $product_images = $statement->fetchAll(PDO::FETCH_ASSOC);
      $product_images = $this->fix_img_path($product_images);
        
      return (!empty($product_images)) ? $product_images : [];
    }

    public function search() {
        $search_query = $_GET['query'];

        $query = 'SELECT products.pro_id, products.pro_title, products.pro_price,
                  product_images.img_name, product_images.img_path
                  FROM products 
                  INNER JOIN product_images
                  ON products.pro_id = product_images.pro_id
                  WHERE products.pro_title LIKE ' . '\'%' . $search_query . '%\'' . 'OR 
                  products.pro_desc LIKE ' . '\'%' . $search_query . '%\'' .
                  'GROUP BY (products.pro_id)';

        // return $query;
        $statement = $this->conn->prepare($query);
        $statement->execute();

        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($products)) ? $products : ['message' => 'No Products Found!'];
    }

    public function user_ads() {
        $user_id = $_GET['id'];

        $query = 'SELECT product_images.img_name, product_images.img_path,
                  products.pro_id, products.pro_title, products.created_at
                  FROM product_images
                  INNER JOIN products
                  ON products.pro_id = product_images.pro_id
                  WHERE user_id = ' . $user_id .
                  ' GROUP BY products.pro_id';

        $statement = $this->conn->prepare($query);
        $statement->execute();

        $products_by_user = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products_by_user)) {
          return ['message' => 'No products by this user'];
        } 
      
        foreach ($products_by_user as &$product) {
          $img[0] = array('img_name' => $product['img_name'], 'img_path' => $product['img_path']);
          $product['img_path'] = $this->fix_img_path($img);
          unset($product['img_name']);
        }
        return $products_by_user;
    }

    public function fix_img_path($img_array) {
      $images = [];
      $ip = 'http://192.168.31.150:8181';
      
      foreach ($img_array as $img) {
        $path_with_ip = str_replace('C:/xampp/htdocs', $ip, $img['img_path']);
        $img_name = $img['img_name'];
        array_push($images, $path_with_ip . $img_name);
      }
      return $images;
    }

    public function get_img_folder_path($id) {
      $query = 'SELECT img_path FROM product_images 
                WHERE pro_id = :pro_id';

      $statement = $this->conn->prepare($query);
      $statement->bindParam(':pro_id', $id);

      $statement->execute();
      $path = $statement->fetch(PDO::FETCH_ASSOC);

      return $path;
    }

    public function remove_ad() {
      $id = $_GET['id'];
      $path = $this->get_img_folder_path($id);

      $query = 'DELETE FROM products WHERE
                pro_id = :pro_id';

      $statement = $this->conn->prepare($query);
      $statement->bindParam(':pro_id', $id);

      $statement->execute();

      if ($statement->rowCount() <= 0) { 
        return ['error' => true, 'message' => 'Error occurred during deleting ad']; 
      }
      
      $this->rmdir_recursive($path['img_path']);

      return ['error' => false, 'message' => 'Ad deleted Successfully!'];
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