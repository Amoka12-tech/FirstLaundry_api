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
        !isset($data->phone)
        || !isset($data->passWord)
        || empty(trim($data->phone))
        || empty(trim($data->passWord))
    ){
        $fields = ['fields' => ['phone','password']];
        $returnData = messg(0,422,'Please Fill in all Required Fields!',$fields);
    }else{
        $phone = mysqli_real_escape_string($dbConnect, trim($data->phone));
        $passWord = mysqli_real_escape_string($dbConnect, trim($data->passWord));

        if(strlen($phone) !== 11){
            $returnData = messg(0, 422, "Invalid phone number");
        }else{
            try {
                //code...
                $checkphoneQry = "SELECT `password` FROM `users` WHERE `phone`='$phone'";
                $qryResult = $dbConnect->query($checkphoneQry);
                if($qryResult->num_rows > 0){
                    $dataQry = $qryResult->fetch_assoc();
                    //Password verify
                    $newPassword = $dataQry['password'];
                    $verifyPassWord = password_verify($passWord, $newPassword);
                    if($verifyPassWord){
                        $qryUser = "SELECT * FROM `users` WHERE `phone`='$phone'";
                        $userResult = $dbConnect->query($qryUser);
                        if($userResult->num_rows > 0){
                            $userData = $userResult->fetch_assoc();
                            if($userData['status'] === 'activated'){
                                $returnData = messg(1, 200, $userData);
                            }else{
                                $returnData = messg(2, 200, $userData);
                            }
                        }else{
                            throw new Exception("Database error! ");
                        }
                    }else{}
                }else{
                    throw new Exception('User with this phone not found in our database');
                }
            } catch (Exception $e) {
                //throw $th;
                $returnData = messg(0, 401, $e->getMessage());
            }
        }
    }

    //Response
    echo json_encode($returnData);
?>