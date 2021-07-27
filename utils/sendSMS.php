<?php 
    class SendSMS {
        public $phoneNumber;
        public $message;

        //Constructor to set incoming parameters
        function __construct($phoneNumber, $message)
        {
            $this->phoneNumber = $phoneNumber;
            $this->message = $message;
        }

        //Send message function
        function send_sms() {
            //SMS Operation here
            $ownerEmail = "amokamutalibfut@gmail.com";
            $subAcct = "AMOKA30";
            $subAcctPwd = "Mutalib12";
            $sendTo = $this->phoneNumber;
            $sender = "Thank You";
            $message = $this->message;

            //Create the required URL
            $url = "http://www.smslive247.com/http/index.aspx?"
            . "cmd=sendquickmsg"
            . "&owneremail=" . UrlEncode($ownerEmail)
            . "&subacct=" . UrlEncode($subAcct)
            . "&subacctpwd=" . UrlEncode($subAcctPwd)
            . "&sendto=" . UrlEncode($sendTo)
            . "&message=" . UrlEncode($message)
            . "&sender=" . UrlEncode($sender);

            //call url
            $time_time = microtime(true);
            if($f = @fopen($url, "r")){
                fgets($f, 255);
            }else{
                return false;
            }
            return true;
        }
    }
?>