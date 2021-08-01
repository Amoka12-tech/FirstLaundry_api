<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require '../utils/dbConfig.php';

    //Database connection
    $dbClass = new Database();
    $dbConnect = $dbClass->dbConnection();

    //Return Message
    function messg($success, $status, $message, $extra=[]){
        return array_merge([
            'success' => $success,
            'status' => $status,
            'message' => $message
        ],$extra);
    };

    //Get form data
    $data = json_decode(file_get_contents("php://input"));
    $returnData = [];
    
    if($_SERVER["REQUEST_METHOD"] != "POST"){
        $returnData = messg(0, 404, "Page Not Found");
    }elseif(
        !isset($data->userId)
    || !isset($data->password)
    || empty(trim($data->userId))
    || empty(trim($data->password))
    ){
        $returnData = messg(0, 422, 'All required field was not provided!');
    }else{
        try {
            //code...
            $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
            $password = mysqli_real_escape_string($dbConnect, trim($data->password));
            if(strlen($password) < 8){
                throw("password length must not be less than eight");
            }else{
                $hassPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateQry = "UPDATE `users` SET `password`='$hassPassword' WHERE `id`='$userId'";
                $updateResult = $dbConnect->query($updateQry);
                if($updateResult){
                    //Password change successful
                    $returnData = messg(1, 200, 'Success! your password had been updated');
                }else{
                    throw('Oops! something happened while trying to update your password, please try again or contact admin');
                }
            }
        } catch (Exception $e) {
            //throw $e;
            $returnData = messg(0, 400, $e->getMessage());
        }
    }

    echo json_encode($returnData);
?>