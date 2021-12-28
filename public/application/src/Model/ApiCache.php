<?php

declare(strict_types = 1);

namespace Application\Model;

/**
 * Model for api cache
 */
class ApiCache
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var string service provider name
     */
    private $service;

    /**
     * @var string service call path
     */
    private $call;

    /**
     * @var array the data to cache
     */
    private $data;

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
     * @return ApiCache
     */
    public function setId(int $id): ApiCache
    {
        $this->id = $id;
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
     * @return ApiCache
     */
    public function setSessionKey(string $sessionKey): ApiCache
    {
        $this->sessionKey = $sessionKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @param string $service
     * 
     * @return ApiCache
     */
    public function setService(string $service): ApiCache
    {
        $this->service = $service;
        return $this;
    }

    /**
     * @return string
     */
    public function getCall(): string
    {
        return $this->call;
    }

    /**
     * @param string $call
     * 
     * @return ApiCache
     */
    public function setCall(string $call): ApiCache
    {
        $this->call = $call;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * 
     * @return ApiCache
     */
    public function setData(array $data): ApiCache
    {
        $this->data = $data;
        return $this;
    }
}
