<?php

declare(strict_types = 1);

namespace Application\Model;

use Application\Model\ApiCache;
use DateTime;

/**
 * Model for a shortlisted vehicle
 */
class Shortlist
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int|null
     */
    private $vehicleId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $mobilityChoice;

    /**
     * @var string
     */
    private $mobilityChoiceType;

    /**
     * @var DateTime
     */
    private $savedDate;

    /**
     * @var array
     */
    private $answers;

    /**
     * @var array|null
     */
    private $vehicleTempData;

    /**
     * @var string|null
     */
    private $vrm;

    /**
     * @var ApiCache|null
     */
    private $apiCache;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * 
     * @return Shortlist
     */
    public function setId(int $id): Shortlist
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getVehicleId()
    {
        return $this->vehicleId;
    }

    /**
     * @param int|null $vehicleId
     * 
     * @return Shortlist
     */
    public function setVehicleId($vehicleId): Shortlist
    {
        $this->vehicleId = $vehicleId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * 
     * @return Shortlist
     */
    public function setUserId(int $userId): Shortlist
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getMobilityChoice(): string
    {
        return $this->mobilityChoice;
    }

    /**
     * @param string $mobilityChoice
     * 
     * @return Shortlist
     */
    public function setMobilityChoice(string $mobilityChoice): Shortlist
    {
        $this->mobilityChoice = $mobilityChoice;
        return $this;
    }

    /**
     * @return string
     */
    public function getMobilityChoiceType(): string
    {
        return $this->mobilityChoiceType;
    }

    /**
     * @param string $mobilityChoiceType
     * 
     * @return Shortlist
     */
    public function setMobilityChoiceType(string $mobilityChoiceType): Shortlist
    {
        $this->mobilityChoiceType = $mobilityChoiceType;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSavedDate(): DateTime
    {
        return $this->savedDate;
    }

    /**
     * @param DateTime $savedDate
     * 
     * @return Shortlist
     */
    public function setSavedDate(DateTime $savedDate): Shortlist
    {
        $this->savedDate = $savedDate;
        return $this;
    }

    /**
     * @return array
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * @param array $answers
     * 
     * @return Shortlist
     */
    public function setAnswers(array $answers): Shortlist
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getVehicleTempData()
    {
        return $this->vehicleTempData;
    }

    /**
     * @param array|null $vehicleTempData
     * 
     * @return Shortlist
     */
    public function setVehicleTempData($vehicleTempData): Shortlist
    {
        $this->vehicleTempData = $vehicleTempData;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVrm()
    {
        return $this->vrm;
    }

    /**
     * @param string|null $vrm
     * 
     * @return Shortlist
     */
    public function setVrm($vrm): Shortlist
    {
        $this->vrm = $vrm;
        return $this;
    }

    /**
     * @return ApiCache|null
     */
    public function getApiCache()
    {
        return $this->apiCache;
    }

    /**
     * @param ApiCache|null
     * 
     * @return Shortlist
     */
    public function setApiCache($apiCache): Shortlist
    {
        $this->apiCache = $apiCache;
        return $this;
    }
}
