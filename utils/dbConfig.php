<?php 
    class Database{
        // CHANGE THE DB INFO ACCORDING TO YOUR DATABASE
        private $db_host = 'localhost';
        private $db_name = 'laundrydb';
        private $db_username = 'root';
        private $db_password = '';

        public function dbConnection(){
            try {
                //connection
                $conn = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);

                if($conn->connect_error){
                    throw new Exception($conn->connect_error);
                }else{
                    return $conn;
                }
            } catch (Exception $th) {
                //throw $th;
                echo "Connection ".$th->getMessage();
                exit;
            }
        }
    }

?>