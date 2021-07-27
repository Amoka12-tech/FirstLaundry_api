<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require '../utils/uuidGenerator.php';
    require '../utils/dbConfig.php';
    require '../utils/generateOTP.php';
    require '../utils/sendSMS.php';

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
    ){
        $fields = ['fields' => ['email']];
        $returnData = messg(0, 422, 'Please Fill in all required filed');
    }else{
        $phone = mysqli_real_escape_string($dbConnect, trim($data->phone));
        if(strlen($phone) !== 11){
            $returnData = messg(0, 422, "Invalid phone number");
        }else{
            try {
                //code...
                $checkUser = "SELECT * FROM `users` WHERE `phone`='$phone'";
                $foundUser = $dbConnect->query($checkUser);
                if($foundUser->num_rows > 0){
                    //Generate otp and send it with userId to to otp db
                    $userData = $foundUser->fetch_assoc();
                    $newOtp = new OTP();
                    $otp = $newOtp->get_otp();
                    $message = "Your First Laundry reset code is ".$otp." ";
                    $newSMS = new SendSMS($userData['phone'], $message);
                    $sendSMS = $newSMS->send_sms();
                    if($sendSMS){
                        //Insert register otp in otp Database with userId
                        $userId = $userData['id'];
                        $insertOtp = "INSERT INTO `otpdb`(`userId`,`code`) VALUES('$userId', '$otp')";
                        $otpResult = $dbConnect->query($insertOtp);
                        if($otpResult){
                            $returnData = messg(1, 200, $userData); //Return user Data on successful
                        }else{
                            throw new Exception('Failed to reach your phone contact admin 08034329120');
                        }
                    }else{
                        throw new Exception('Otp can\'t reach your phone try again');
                    }
                }else{
                    throw new Exception('User with this phone number not found');
                }
            } catch (Exception $e) {
                $returnData = messg(0, 500, $e->getMessage());
            }
        }
    };

    //Returm Response
    echo json_encode($returnData);
?>