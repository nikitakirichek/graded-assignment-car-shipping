<?php
/**
 * Created by PhpStorm.
 * User: kirichek
 * Date: 10/26/16
 * Time: 7:10 PM
 */

require_once("User.php");
require_once("Location.php");
require_once("Passenger.php");
require_once("dbconnect.php");

class Ride
{
    public $db_id;
    public $user;
    public $start_point;
    public $end_point;
    public $start_time;
    public $end_time;
    public $price;
    public $note;
    public $reservation_places;
    public $weekly;
    public $exceptions_days;

    function add_day($string)
    {
        echo $string;
        $format = "m/d/Y";
        $datetime = date_create_from_format($format, $string);
        if ($datetime) {
            array_push($this->exeptions_days, $datetime);
        }
    }

    public static function save_to_DB($conn, $ride)
    {
        $start_time = $ride->start_time->getTimestamp();
        $end_time = $ride->end_time->getTimestamp();
        $user_id = $ride->user->db_id;
        $start_point_id = $ride->start_point->db_id;
        $end_point_id = $ride->end_point->db_id;
        if ($ride->weekly != 1) {
            $ride->weekly = 0;
        }

        $query = "INSERT INTO `ride`( `user_id`, `start_point_id`, `end_point_id`, `start_time`, `end_time`,`price`, `note`, `reservation_places`, `weekly`)
                  VALUES ($user_id,$start_point_id, $end_point_id,FROM_UNIXTIME($start_time),FROM_UNIXTIME($end_time), $ride->price, '$ride->note',$ride->reservation_places, $ride->weekly)";
        $res = mysqli_query($conn, $query);

        $query = "SELECT LAST_INSERT_ID();";
        $res = mysqli_query($conn, $query);
        $row = mysqli_fetch_array($res);
        $ride_id =  intval($row['LAST_INSERT_ID()']);

        if ($ride->exeptions_days) {
            foreach ($ride->exeptions_days as $exeption_day) {
                $mysqldate = date('Y-m-d', $exeption_day->getTimestamp());
                $query = "INSERT INTO `exception_dates`(  `ride_id`, `date`) VALUES ($ride_id,'$mysqldate')";
                $res = mysqli_query($conn, $query);
            }
        }

        return $res ? true : false;
    }

    public static function get_by_id($id, $conn)
    {
        $res = mysqli_query($conn, "SELECT * FROM `ride` WHERE `id` ='$id'");
        $row = mysqli_fetch_array($res);
        if ($row) {
            $ride = Ride::row_to_object($row, $conn);
            return $ride;
        }

        return null;
    }

    public static function get_rides_for_user($user_id, $conn)
    {
        $query = "SELECT DISTINCT * FROM `ride` WHERE `user_id` ='$user_id'";
        $res = mysqli_query($conn, $query);

        $arr = array();
        while ($row = mysqli_fetch_array($res)) {
            array_push($arr, Ride::row_to_object($row, $conn));
        }

        $query = "SELECT * FROM `passenger` WHERE `user_id` = $user_id";
        $res = mysqli_query($conn, $query);
        while ($row = mysqli_fetch_array($res)) {
            $ride_id = $row["ride_id"];
            $query = "SELECT * FROM `ride` WHERE `id` = $ride_id";
            $res_rides = mysqli_query($conn, $query);
            if ($res_rides) {
                $row_ride = mysqli_fetch_array($res_rides);
                array_push($arr, Ride::row_to_object($row_ride, $conn));
            }
        }
        return $arr;
    }

    private static function row_to_object($row, $conn)
    {
        $ride = new Ride();
        $start_point_id = $row["start_point_id"];
        $end_point_id = $row["end_point_id"];
        $ride->db_id = $row["id"];
        $ride->start_point = Location::get_by_id($start_point_id, $conn);
        $ride->end_point = Location::get_by_id($end_point_id, $conn);
        $ride->start_time = new DateTime();
        $ride->start_time->setTimestamp(strtotime($row["start_time"]));
        $ride->end_time = new DateTime();
        $ride->end_time->setTimestamp(strtotime($row["end_time"]));
        $ride->start_time->format("Y-m-d H:i:s");
        $ride->price = $row["price"];
        $ride->note = $row["note"];
        $ride->weekly = boolval($row["weekly"]);
        $ride->reservation_places = $row["reservation_places"];
        $ride->user = User::get_by_id($row["user_id"], $conn);
        return $ride;
    }

    public static function get_indentical_locations($lat1, $lat2, $lg1, $lg2, $start_time, $end_time, $conn)
    {
        $sql_format = "Y-m-d H:i:s";
        $sql_time_1 = $start_time->format($sql_format);
        $sql_time_2 = $end_time->format($sql_format);
        $radious_for_start_point = 50;
        $radious_for_end_point = 50;
        $query = "SELECT * FROM `ride` R 
                    WHERE (R.start_point_id
                    IN (SELECT id FROM location WHERE ( 3959 * acos( cos( radians($lat1) ) * cos( radians( lat ) ) * cos( radians( lg ) - radians($lg1) ) + sin( radians($lat1) ) * sin( radians( lat ) ) ) ) < $radious_for_start_point))
                           AND (R.end_point_id IN 
                           (SELECT id FROM location WHERE ( 3959 * acos( cos( radians($lat2) ) * cos( radians( lat ) ) * cos( radians( lg ) - radians($lg2) ) + sin( radians($lat2) ) * sin( radians( lat ) ) ) ) < $radious_for_end_point)) 
                      AND ((R.start_time >='$sql_time_1' AND R.start_time <= '$sql_time_2') OR R.weekly = 1)";
        $res = mysqli_query($conn, $query);
        $arr = array();
        while ($row = mysqli_fetch_array($res)) {
            array_push($arr, Ride::row_to_object($row, $conn));
        }

        //filter array for weekly results
        $diff = abs($start_time->getTimestamp() - $end_time->getTimestamp());
        $numDays = $diff/60/60/24;
        foreach ($arr as $key => $item) {
            if($item->weekly ){

                $day_of_week = intval($item->start_time->format("N"));
                $time_of_item = intval($item->start_time->format("Gis"));
                $day_of_item = intval($item->start_time->format("N"));
                $start_time_copy = clone $start_time;
                $is_sutable = false;

                    $diff = 0;
                    do{
                        $sql_item_time = $start_time_copy->format("Y-m-d");;
                        $query = "SELECT * FROM `exception_dates`
                          WHERE  ride_id = $item->db_id AND date = '$sql_item_time'";
                        $res = mysqli_query($conn, $query);
                        $exception_day_for_item = null;
                        if($res){
                            $row = mysqli_fetch_array($res);
                            $exception_day_for_item =  $row["date"];
                        }
                        if(intval($start_time_copy->format("N")) == $day_of_week && (
                            $day_of_item != intval($end_time->format("N"))
                            || $time_of_item < intval($end_time->format("Gis")))
                            && $exception_day_for_item != $start_time_copy->format("Y-m-d")){
                            $is_sutable = true;
                            break;
                        }
                        $diff = $end_time->getTimestamp() - $start_time_copy->getTimestamp();
                        $start_time_copy->add(new DateInterval('P1D'));
                    }while($diff > 0);

                    if($item->start_time->getTimeStamp() > $end_time->getTimeStamp()){
                        $is_sutable = false;
                    }
                    if(!$is_sutable){
                        unset($arr[$key]);
                    }
                }
            }

        return $arr;
    }

    public static function join_ride($user_id, $ride_id, $conn)
    {
        $newride = Ride::get_by_id($ride_id, $conn);
        if($newride->reservation_places > 0 && $newride->user->db_id != $user_id
            && ! (Ride::ride_has_user($user_id, $ride_id, $conn))) {

            $query = "INSERT INTO `passenger`(`user_id`, `ride_id`) VALUES ($user_id, $ride_id)";
            $res = mysqli_query($conn, $query);
            if ($res) {
                $ride = Ride::get_by_id($ride_id, $conn);

                $places = $ride->reservation_places - 1;
                if ($places < 0) {
                    return false;
                }
                $query = "UPDATE `ride` SET `reservation_places`=$places WHERE id = $ride_id";
                $res = mysqli_query($conn, $query);
            }
        }
    }

    public static function ride_has_user($user_id, $ride_id, $conn){
        $query = "SELECT * FROM `passenger` WHERE `user_id` = $user_id AND `ride_id` = $ride_id";
        $res = mysqli_query($conn, $query);
        $row = mysqli_fetch_row($res);
        return $row?true:false;
    }

    public static function delete_by_id($ride_id, $conn)
    {
        $query = "DELETE FROM `ride` WHERE `id` = $ride_id";
        $res = mysqli_query($conn, $query);
        return $res ? true : false;
    }

    public static function unjoin($user_id, $ride_id, $conn)
    {
        Passenger::delete_by_user_and_ride($user_id, $ride_id, $conn);
        $ride = Ride::get_by_id($ride_id, $conn);
        $places = $ride->reservation_places + 1;
        $query = "UPDATE `ride` SET `reservation_places`=$places WHERE id = $ride_id";
        $res = mysqli_query($conn, $query);
    }

    public static function parse_time($string)
    {
        $time = DateTime::createFromFormat("m/d/Y h:i A", $string);
        return $time;
    }

}