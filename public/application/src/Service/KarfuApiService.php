<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Karfu\Journey\FormatJourneyData;

/**
 * Service class for the karfu api
 */
class KarfuApiService
{
    const HOST = 'https://api.karfu.com';

    /**
     * @var FormatJourneyData
     */
    private $formatJourneyData;

    /**
     * @param FormatJourneyData $formatJourneyData
     */
    public function __construct(FormatJourneyData $formatJourneyData)
    {
        $this->formatJourneyData = $formatJourneyData;
    }

    /**
     * Get mobility types
     * 
     * @return array|false
     */
    public function getMobilityTypes()
    {
        $data = '{"questions": ' . $this->formatJourneyData->getJourneyData() . '}';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::HOST . '/mobility-choices',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        $close = curl_close($curl);

        if ($response) {
            $data = json_decode($response, true);

            if ($data['statusCode'] === 200) {
                return $data['data']['mobilityTypes'];
            }
        }
        
        return false;
    }

    /**
     * Get mobility sub types
     * 
     * @return array|false
     */
    public function getMobilityChoices()
    {
        $data = '{"questions": ' . $this->formatJourneyData->getJourneyData() . '}';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::HOST . '/vehicle-types-mobility-choices/mobility',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        $close = curl_close($curl);

        if ($response) {
            $data = json_decode($response, true);

            if ($data['statusCode'] === 200) {
                return $data['data']['mobilityChoices'];
            }
        }
        
        return false;
    }

    /**
     * Get vehicle types
     * 
     * @param bool $includeTypes
     * 
     * @return array|false
     */
    public function getVehicleTypes(bool $includeTypes = true)
    {
        $data = '{"questions": ' . $this->formatJourneyData->getJourneyData() . '}';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::HOST . '/vehicle-types-mobility-choices/vehicle',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        $close = curl_close($curl);

        if ($response) {
            $data = json_decode($response, true);

            if ($data['statusCode'] === 200) {
                return ($includeTypes === true)
                    ? $data['data']['vehicleTypes']
                    : array_keys($data['data']['vehicleTypes']);
            }
        }
        
        return false;
    }

    /**
     * Get vehicle types with mobility choices
     * 
     * @return array|false
     */
    public function getVehicleTypesMobilityChoices()
    {
        $data = '{"questions": ' . $this->formatJourneyData->getJourneyData() . '}';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::HOST . '/vehicle-types-mobility-choices',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ]);
        $response = curl_exec($curl);
        $close = curl_close($curl);

        if ($response) {
            $data = json_decode($response, true);

            if ($data['statusCode'] === 200) {
                return $data['data']['vehicleTypesMobilityChoices'];
            }
        }
        
        return false;
    }
}
