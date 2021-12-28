<?php

namespace Application\Helper;

class Map
{
    /**
     * @param string $origin
     * @param string $destination
     * 
     * @return array
     */
    public function getDistance(string $origin, string $destination)
    {

        $origin = str_replace(' ', '', $origin);
        $destination = str_replace(' ', '', $destination);

        $key = CONFIG_MAP_KEY;
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&mode=driving&language=en-GB&sensor=false&units=imperial&region=UK&key={$key}";
        $header = array("Content-Type: application/json");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ));
        $response = json_decode(curl_exec($curl));
        curl_close($curl);

        $journey = [];

        if($response->status == 'OK') {
            $data = $response->rows[0]->elements[0];
            $journey = ['miles' => explode(' ', $data->distance->text)[0], 'time' => $data->duration->text];
        }

        return $journey;
    }

}