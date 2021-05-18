<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

/**
 * Rest API
 */
class Rest
{
    protected $db_host = 'sql11.freemysqlhosting.net';
    protected $db_name = 'sql11412895';
    protected $db_username = 'sql11412895';
    protected $db_password = 'aQW7FSeW2K';

    protected $success = false;
    protected $error = 'An unknown error occured.';
    protected $payload = [];

    /**
     * __construct()
     *
     * @return void
     */
    public function __construct()
    {
        $this->db = $this->db_connection();

        $action = strtolower($_SERVER['REQUEST_METHOD'] . '_' . $_GET['action']);

        if (isset($_GET['action']) && method_exists($this, $action)) {
            $this->{$action}();
        } else {
            $this->error = 'Action not implemented.';
        }
    }

    /**
     * __destruct()
     * 
     * @return void
     */
    public function __destruct()
    {
        $output = [
            'success' => $this->success,
        ];

        if ($this->success) {
            if(!empty($this->payload)) {
                $output['payload'] = $this->payload;
            }
        } else {
            $output['error'] = $this->error;
        }

        http_response_code(200);

        // header("Access-Control-Allow-Origin: *");
        // header("Access-Control-Allow-Headers: access");
        // header("Access-Control-Allow-Methods: POST");
        // header("Access-Control-Allow-Credentials: true");
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * Connect to DB
     *
     * @return object PDO DB Connection
     */
    public function db_connection()
    {
        try {
            $conn = new PDO('mysql:host=' . $this->db_host . ';dbname=' . $this->db_name, $this->db_username, $this->db_password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            $this->success = false;
            $this->error = "Connection error " . $e->getMessage();
        }
    }

    // Users

    /**
     * Check if user/email exists
     *
     * @return void
     */
    public function get_user()
    {
        if(!isset($_GET['email'])) {
            $this->success = false;
            $this->error = 'There are missing parameters.';

            return;
        }

        if($this->_email_exists($_GET['email'])) {
            $this->success = true;
            $this->payload = [
                'exists' => true
            ];
        } else {
            $this->success = false;
            $this->error = 'No such user exist.';
        }
    }

    /**
     * Create new user
     *
     * @return void
     */
    public function post_registration()
    {
        if(!isset($_POST['email']) || !isset($_POST['phone']) || !isset($_POST['password'])) {
            $this->success = false;
            $this->error = 'There are missing parameters.';

            return;
        }

        if ($this->_email_exists($_POST['email'])) {
            $this->success = false;
            $this->error = 'A user already exists.';

            return;
        }

        $phone = str_replace('+', '', $_POST['phone']);

        $user_id = $this->_create_user($_POST['email'], $phone, $_POST['password']);
        $verification_code_temp = $this->_random_number();
        $verification_code = $this->_set_verification_code($user_id, $phone, $verification_code_temp);
        $message = $this->_send_message($user_id, $phone, 'Your verification code is ' . $verification_code_temp . '.');

        if($user_id && $verification_code && $message) {
            $this->success = true;
            $this->payload = [
                'message' => 'User created successfully & verification code sent.'
            ];
        } else {
            $this->success = false;
            $this->error = 'User was not created. Unknown error occured.';
        }
    }

    /**
     * _create_user
     *
     * @param string $email
     * @param string $phone
     * @param string $password
     * @return int|bool
     */
    protected function _create_user(string $email, string $phone, string $password) {
        $insert_query = "INSERT INTO `users` (email,password,phone) VALUES (:email,:password,:phone);";
        $insert_stmt = $this->db->prepare($insert_query);

        $insert_stmt->bindValue(':email', htmlspecialchars(strip_tags($email)), PDO::PARAM_STR);
        $insert_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
        $insert_stmt->bindValue(':password', htmlspecialchars(strip_tags($password)), PDO::PARAM_STR);
        
        if ($insert_stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Check if email exist
     *
     * @param string $email
     * @return bool
     */
    protected function _email_exists(string $email)
    {
        $sql = "SELECT * FROM `users` WHERE email='" . $email . "';";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    // Verifications

    /**
     * post_verification
     *
     * @return void
     */
    public function post_verification()
    {
        if(!isset($_POST['phone']) || !isset($_POST['verification_code'])) {
            $this->success = false;
            $this->error = 'There are missing parameters.';

            return;
        }

        $phone = str_replace('+', '', $_POST['phone']);

        $verification = $this->_verify_verification_code($phone, $_POST['verification_code']);
     
        if($verification) {
            $this->success = true;
            $this->payload = [
                'verification' => true
            ];
        } else {
            $this->success = false;
            $this->error = 'Verification was unsuccessfull.';
        }
    }

    /**
     * post_verification_renew
     *
     * @return void
     */
    public function post_verification_renew()
    {
        // TODO
        $this->success = false;
        $this->error = 'Verification code not sent.';
    }

    /**
     * _set_verification_code
     *
     * @param integer $user_id
     * @param string $phone
     * @param integer $verification_code
     * @return integer|bool
     */
    protected function _set_verification_code(int $user_id, string $phone, int $verification_code)
    {
        $insert_query = "INSERT INTO `verifications` (user_id,phone,verification_code,timestamp) VALUES (:user_id,:phone,:verification_code,NOW());";
        $insert_stmt = $this->db->prepare($insert_query);

        $insert_stmt->bindValue(':user_id', htmlspecialchars(strip_tags($user_id)), PDO::PARAM_INT);
        $insert_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
        $insert_stmt->bindValue(':verification_code', htmlspecialchars(strip_tags($verification_code)), PDO::PARAM_INT);
        
        if ($insert_stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * _verify_verification_code
     *
     * @param string $phone
     * @param integer $verification_code
     * @return void
     */
    protected function _verify_verification_code(string $phone, int $verification_code)
    {
        $insert_query = "INSERT INTO `verifications_log` (phone,verification_code,timestamp) VALUES (:phone,:verification_code,NOW());";
        $insert_stmt = $this->db->prepare($insert_query);

        $insert_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
        $insert_stmt->bindValue(':verification_code', htmlspecialchars(strip_tags($verification_code)), PDO::PARAM_INT);
        
        $insert_stmt->execute();

        $update_query = "UPDATE `verifications` SET verified=1 WHERE (phone=:phone AND verification_code=:verification_code);";
        $update_stmt = $this->db->prepare($update_query);

        $update_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
        $update_stmt->bindValue(':verification_code', htmlspecialchars(strip_tags($verification_code)), PDO::PARAM_INT);
        
        $update_stmt->execute();

        if($update_stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * _random_number
     *
     * @return int
     */
    protected function _random_number(int $min = 100000, int $max = 999999)
    {
        return mt_rand($min, $max);
    }

    // Messages

    /**
     * _send_message
     *
     * @param integer $user_id
     * @param string $phone
     * @param string $message
     * @return void
     */
    protected function _send_message(int $user_id, string $phone, string $message)
    {
        // messages - phone, message
        $insert_query = "INSERT INTO `messages` (user_id,phone,message,timestamp) VALUES (:user_id,:phone,:message,NOW());";
        $insert_stmt = $this->db->prepare($insert_query);

        $insert_stmt->bindValue(':user_id', htmlspecialchars(strip_tags($user_id)), PDO::PARAM_INT);
        $insert_stmt->bindValue(':phone', htmlspecialchars(strip_tags($phone)), PDO::PARAM_STR);
        $insert_stmt->bindValue(':message', htmlspecialchars(strip_tags($message)), PDO::PARAM_STR);
        
        if ($insert_stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
}


$rest = new Rest();
