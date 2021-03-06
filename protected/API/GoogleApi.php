<?php

/**
 * Created by PhpStorm.
 * User: kirichek
 * Date: 11/2/16
 * Time: 10:42 PM
 */

//require_once("../model/Location.php");
require_once("../protected/model/Location.php");


class GoogleApi
{
    public static function GetDrivingDistance($lat1, $lat2, $long1, $long2)
    {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$lat1.",".$long1."&destinations=".$lat2.",".$long2."&mode=driving&language=pl-PL";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response, true);
        $dist = $response_a['rows'][0]['elements'][0]['distance']['text'];
        $time = $response_a['rows'][0]['elements'][0]['duration']['text'];

        $strtime= strval($time);
         if($time && $dist) {
             $datetime = date_create_from_format('z \g\o\d\z. Y \m\i\n', $strtime);
             $nohours = false;
             if (!$datetime) {
                 $datetime = date_create_from_format('Y \m\i\n', $strtime);
                 $nohours = true;
             }
             if (!$datetime) {return array('distance' => "0", 'minutes' => "0", 'hours' => "0");}
             if (!$nohours) {
                 $hours = $datetime->format("z");
                 print $hours;
             } else {
                 $hours = "0";
             }
             $minutes = $datetime->format("Y");
             print $minutes;
             for ($var = 0; $var < 3; $var++) {
                 if ($minutes[0] == '0') {
                     $minutes = substr($minutes, 1);
                 }
             }
             $dist = preg_replace('/[^0-9]/', '', $dist);
             return array('distance' => $dist, 'minutes' => $minutes, 'hours' => $hours);
         } else {
             return array('distance' => "0", 'minutes' => "0", 'hours' => "0");
         }

    }

    // function to geocode address, it will return false if unable to geocode address
public static function geocode($address)
    {
        if(!$address){
            return false;
        }

        // url encode the address
        $address = urlencode($address);

        // google map geocode api url
        $url = "http://maps.google.com/maps/api/geocode/json?address={$address}";

        // get the json response
        $resp_json = file_get_contents($url);

        // decode the json
        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if ($resp['status'] == 'OK') {

            // get the important data
            $lati = $resp['results'][0]['geometry']['location']['lat'];
            $longi = $resp['results'][0]['geometry']['location']['lng'];
            $formatted_address = $resp['results'][0]['formatted_address'];

            // verify if data is complete
            if ($lati && $longi && $formatted_address) {

                // put the data in the array
                $data_arr = array();

                array_push(
                    $data_arr,
                    $lati,
                    $longi,
                    $formatted_address
                );
                $location = new Location();
                $location->lat = $data_arr[0];
                $location->lg = $data_arr[1];
                $location->name = $data_arr[2];

                return $location;

            } else {
                return false;
            }

        } else {
            return false;
        }
    }

}