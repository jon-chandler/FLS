<?php

namespace Application\Helper;

class DVSAData
{
    /**
     * @param string $vrm
     * 
     * @return array|bool
     */
    public function getDVSAData(string $vrm = null)
    {
        $id = CONFIG_DVSA;
        $url = "https://beta.check-mot.service.gov.uk/trade/vehicles/mot-tests?registration={$vrm}";
        $header = array("Content-Type: application/json", "x-api-key: {$id}");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        return ($response) ? json_decode($response, true) : false;
    }
}
