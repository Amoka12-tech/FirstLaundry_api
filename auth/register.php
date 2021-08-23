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

    date_default_timezone_set("Africa/Lagos");
    $registerDate = date(DATE_ATOM);

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
        $uuid = new UUID_V4();
        $newUuidv4 = $uuid->guidv4();

        if(strlen($phone) !== 11 ){
            $returnData = messg(0, 422, "Invalid phone number expected eleven(11) digit");
        }elseif(strlen($passWord) < 8){
            $returnData = messg(0, 422, "Password length must not be less than eight(8) characters");
        }else{
            try {
                //code...
                $checkUser = "SELECT `phone` FROM `users` WHERE `phone`='$phone'";
                $result = $dbConnect->query($checkUser);
                if($result->num_rows > 0){
                    $returnData = messg(0, 422, "User Already Exist Please click the signIn");
                }else{
                    $hashPassWord = password_hash($passWord, PASSWORD_DEFAULT);
                    $insertIntoUsers = "INSERT INTO `users`(`id`, `phone`, `password`, `date`) VALUES('$newUuidv4','$phone','$hashPassWord','$registerDate')";
                    $insertQuery = $dbConnect->query($insertIntoUsers);
                    if($insertQuery === TRUE){
                        //Here user detail inserted succesfully now send generate otp
                        $newOtp = new OTP();
                        $otp = $newOtp->get_otp(); //Get new otp code
                        $smsMessage = "Welcome to First Laundry verify your account with this OTP: ".$otp." 
                        this code is valid for 3 minutes";
                        $newSMS = new SendSMS($phone, $smsMessage);
                        $sendSMS = $newSMS->send_sms(); //Send SMS with otp
                        if($sendSMS){
                            $insertOtp = "INSERT INTO `otpdb`(`userId`, `code`) VALUES('$newUuidv4','$otp')";
                            $otpResult = $dbConnect->query($insertOtp);
                            if($otpResult){
                                $insertDiscount = "INSERT INTO `discountdb`(`userId`, `discount`, `date`) VALUES('$newUuidv4','40','$registerDate')";
                                $dbConnect->query($insertDiscount);
                                $selectNewUser = "SELECT * FROM `users` WHERE `id`='$newUuidv4'";
                                $newUserResult = $dbConnect->query($selectNewUser);
                                $newUserData = $newUserResult->fetch_assoc();
                                $returnData = messg(1, 200, $newUserData);
                            }else{
                                throw new Exception('Sever Error please contact admin to activate account');
                            }
                        }else{
                            throw new Exception('Can\'t reach your phone, contact admin +2348034329120 to activate your account');
                        }
                    }else{
                        throw new Exception('Database error, failed to register');
                    }
                }
            } catch (Exception $e) {
                //throw $e;
                $returnData = messg(0, 500, $e->getMessage());
            }
        }
    };

    //Get Incoming params

    echo json_encode($returnData);
    
?>