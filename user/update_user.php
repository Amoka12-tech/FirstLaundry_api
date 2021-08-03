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
    || !isset($data->name)
    || empty(trim($data->userId))
    ){
        $returnData = messg(0, 400, 'Wrong parameter supplied');
    }else{
        try {
            //code...
            $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
            $name = mysqli_real_escape_string($dbConnect, trim($data->name));
            $picture = mysqli_real_escape_string($dbConnect, trim($data->picture));
            $checkUser = "SELECT * FROM `users` WHERE `id`='$userId'";
            $qryCheckUser = $dbConnect->query($checkUser);
            if($qryCheckUser->num_rows > 0){
                if(empty($data->picture)){
                    $updateUser = "UPDATE `users` SET `name`='$name' WHERE `id`='$userId'";
                    $qryUpdateUser = $dbConnect->query($updateUser);
                    if($qryCheckUser){
                        $returnData = messg(1, 200, 'User updated successfuly');
                    }else{
                        throw('Oops! user data not updated, try again.');
                    }
                }else{
                    $updateUser = "UPDATE `users` SET `name`='$name', `picture`='$picture' WHERE `id`='$userId'";
                    $qryUpdateUser = $dbConnect->query($updateUser);
                    if($qryCheckUser){
                        $returnData = messg(1, 200, 'User updated successfuly');
                    }else{
                        throw('Oops! user data not updated, try again.');
                    }
                }
            }else{
                throw('Oops! User with this ID not found contact admin.');
            }
        } catch (Exception $e) {
            //throw $e;
            $returnData = messg(0, 400, $e->getMessage());
        }
    }

    echo json_encode($returnData);
?>