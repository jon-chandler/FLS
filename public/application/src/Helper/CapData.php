<?php

namespace Application\Helper;

use Core;
use Database;
use DateTime;
use Exception;
use Page;

class CapData
{
    public function getAccessToken()
    {
        $token_url = "https://identity.cap-hpi.com/connect/token";
        $client_id = "e9ca4b4577144cb19faadf248d92315c";
        $client_secret = "#(^sdHbz1M%u+d$?rm}LFK!hY4m$(wNK";

        $content = "grant_type=client_credentials&scope=CapHpi.UK.PublicApi&version=2";
        $authorization = base64_encode("$client_id:$client_secret");
        $header = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $token_url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $content
        ));
        $response = curl_exec($curl);

        curl_close($curl);

        $token = json_decode($response)->access_token;
        return $token;
    }

    /**
     * @param string $registrationDate
     * 
     * @return float
     */
    private function approximateMileage(string $registrationDate)
    {
        $today = new DateTime('NOW');
        $manufacturerDate = new DateTime($registrationDate);
        $ageInMonths = (($manufacturerDate->diff($today)->y*12) + $manufacturerDate->diff($today)->m);

        return ceil((CONFIG_AVG_DOM_ANNUAL_MILEAGE * $ageInMonths)/12);
    }

    /**
     * @param string $vrm
     * @param int|null $mileage
     * @param string|null $registrationDate
     * 
     * @return void
     * 
     * @throws Exception
     */
    public function getCarData(string $vrm, int $mileage = null, string $registrationDate = null)
    {
        $vrm = str_replace(' ', '', $vrm);

        if (!$mileage) {
            if (!empty($registrationDate)) {
                $miles = $this->approximateMileage($registrationDate);
            } else {
                $miles = 1001;
            }
        } else {
            $miles = $mileage;
        }

        $date =  new DateTime('NOW');
        $today = $date->format('Y-m-d');
 
        if ($access_token = $this->getAccessToken()) {

            $apiUrl = "https://api.cap-hpi.co.uk/v1/vrms/{$vrm}/data";
            $request = [
                'derivativeDetails' => true,
                'vehicleDetails' => true,
                'dvla' => true,
                'currentValuations' => true,
                'currentValuationRequest' => [
                    'valuationPoints' => [
                        [
                            'mileage' => $miles,
                            'publicationDate' => $today
                        ]
                    ],
                    'valuationTypes' => [
                        'TradeClean',
                        'TradeAverage',
                        'TradeBelow'
                    ]
                ]
            ];

            $body = json_encode($request);
            $length = strlen($body);

            $header = [
                "Authorization: Bearer {$access_token}",
                "Content-type: application/json; charset=utf-8",
                "Accept: application/xml",
                "Content-Length: $length",
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
            ]);
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_close($curl);

            $res = json_decode($response, true);

            if (!empty($res['errors']) || empty($res['currentValuations']['vin'])) {
                throw new Exception('Cap Error');
            }

            $licencePlate = strtoupper($vrm);
            $file = "application/files/vehicles/{$licencePlate}.jpg";

            if (!file_exists($file)) {
                $this->getImage($licencePlate, $file, '', $access_token);
            }

            $this->updateVehicleData($res);

            return $res;
            
        } else {
            echo 'Auth failed';
        }
    }

    /**
     * @param string $vrm
     * @param string $file
     * @param $imagePath
     * @param $token
     * 
     * @return void
     */
    public function getImage(string $vrm, string $file, $imagePath = null, $token = null)
    {
        if (empty($token)) {
            $token = $this->getAccessToken();
        }

        $header = [
            "Authorization: Bearer {$token}",
            "Content-type: image/jpeg",
            "Accept: application/xml"
        ];

        $imgURL = "https://api.cap-hpi.co.uk/v1/vrms/{$vrm}/derivative/image?width=700";

        $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL            => $imgURL,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);

        curl_close($curl);

        file_put_contents($file, $response);
        chmod($file, 0777);

        // push the local path in??
        $this->updateVehicleImageData($vrm, $imagePath);
    }

    /**
     * @param string $vrm
     * @param $imagePath
     * 
     * @return void
     */
    public function updateVehicleImageData($vrm, $imagePath)
    {
        $db = Database::connection();
        $r = $db->executeQuery('UPDATE scraped_vehicle_content SET model_image = ? WHERE vrm = ?', array($imagePath, $vrm));

        // Update page attribute if we're on a vehicle type view
        $p = Page::getCurrentPage();
        if ($p->getCollectionTypeName() != "JourneyPageType") {
            $p->setAttribute('content_image_src', $imagePath);
        }
    }

    /**
     * @param array $response
     * 
     * @return void
     */
    private function updateVehicleData(array $response)
    {
        $db = Database::connection();
        $sets = ['VRM = ?', 'ManufactureDate = ?', 'Colour = ?'];
        $vrm = $response['vehicleDetails']['vrm'];
        $manufacturerDate = $response['vehicleDetails']['dates']['manufactured'];
        $colour = $response['vehicleDetails']['colours']['current']['name'];
        $capId = $response['derivativeDetails']['capId'];

        $binds = [
            $vrm,
            $manufacturerDate,
            $colour
        ];

        if (isset($response['valuations'][0]['valuationsPoints'][0]['mileage'])) {
            $sets[] = 'Milage = ?';
            $binds[] = $response['valuations'][0]['valuationsPoints'][0]['mileage'];
        }
        if (isset($response['valuations'][0]['valuationsPoints'][0]['value'])) {
            $sets[] = 'UsedPriceVRM = ?';
            $binds[] = $response['valuations'][0]['valuationsPoints'][0]['value'];
        }

        $binds[] = $capId;

        $set = implode(', ', $sets);

        $query = 'UPDATE karfu_vehicle SET ' . $set . ' WHERE CapID = ?';

        $r = $db->executeQuery($query, $binds);
    }
}
