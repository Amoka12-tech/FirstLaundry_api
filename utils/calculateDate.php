<?php 
    class ConvertDateToSec {
        public $registerDate;

        //Constructor to set incoming params
        function __construct($registerDate)
        {
            $this->registerDate = $registerDate;
        }

        function convert_date_to_seconds(){
            $date1 = new DateTime('now', new DateTimeZone('Africa/Lagos')); //new date
            $date2 = new DateTime($this->registerDate, new DateTimeZone('Africa/Lagos')); //old date
            
            $interval = date_diff($date1, $date2);
            

            $sec = (int)$interval->format("%S");
            $min = (((int)$interval->format("%I")) * 60);
            $hour = (((int)$interval->format("%H")) * 3600);

            $seconds = $sec + $min + $hour;

            return $seconds;

        }
    }
?>