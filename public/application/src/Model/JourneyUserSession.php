<?php

declare(strict_types = 1);

namespace Application\Model;

use DateTime;

/**
 * Model for a saved user journey session
 */
class JourneyUserSession
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var DateTime
     */
    private $created;

    /**
     * @var DateTime
     */
    private $lastUpdated;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var string
     */
    private $sessionStartUrl;

    /**
     * @var array
     */
    private $progress;

    /**
     * @var bool
     */
    private $saved;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $journeyType;

    /**
     * @var string
     */
    private $journeyGroup;

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
     * @return JourneyUserSession
     */
    public function setId(int $id): JourneyUserSession
    {
        $this->id = $id;
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
     * @return JourneyUserSession
     */
    public function setUserId(int $userId): JourneyUserSession
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * 
     * @return JourneyUserSession
     */
    public function setCreated(DateTime $created): JourneyUserSession
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastUpdated(): DateTime
    {
        return $this->lastUpdated;
    }

    /**
     * @param DateTime $lastUpdated
     * 
     * @return JourneyUserSession
     */
    public function setLastUpdated(DateTime $lastUpdated): JourneyUserSession
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * @param string $sessionKey
     * 
     * @return JourneyUserSession
     */
    public function setSessionKey(string $sessionKey): JourneyUserSession
    {
        $this->sessionKey = $sessionKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionStartUrl(): string
    {
        return $this->sessionStartUrl;
    }

    /**
     * @param string $sessionStartUrl
     * 
     * @return JourneyUserSession
     */
    public function setSessionStartUrl(string $sessionStartUrl): JourneyUserSession
    {
        $this->sessionStartUrl = $sessionStartUrl;
        return $this;
    }

    /**
     * @return array
     */
    public function getProgress(): array
    {
        return $this->progress;
    }

    /**
     * @param array $progress
     * 
     * @return JourneyUserSession
     */
    public function setProgress(array $progress): JourneyUserSession
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSaved(): bool
    {
        return $this->saved;
    }

    /**
     * @param bool $saved
     * 
     * @return JourneyUserSession
     */
    public function setSaved(bool $saved): JourneyUserSession
    {
        $this->saved = $saved;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * 
     * @return JourneyUserSession
     */
    public function setLabel(string $label = null): JourneyUserSession
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return array
     */
    public function getDescription(): array
    {
        return $this->description;
    }

    /**
     * @param array $description
     * 
     * @return JourneyUserSession
     */
    public function setDescription(array $description): JourneyUserSession
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getJourneyType()
    {
        return $this->journeyType;
    }

    /**
     * @param string|null $journeyType
     * 
     * @return JourneyUserSession
     */
    public function setJourneyType($journeyType): JourneyUserSession
    {
        $this->journeyType = $journeyType;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getJourneyGroup()
    {
        return $this->journeyGroup;
    }

    /**
     * @param string|null $journeyGroup
     * 
     * @return JourneyUserSession
     */
    public function setJourneyGroup($journeyGroup): JourneyUserSession
    {
        $this->journeyGroup = $journeyGroup;
        return $this;
    }
}
