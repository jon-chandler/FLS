<?php

declare(strict_types = 1);

namespace Application\Model;

/**
 * Model for post code region
 */
class PostcodeRegion
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $postcodeArea;

    /**
     * @var string
     */
    private $region;

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
     * @return PostcodeRegion
     */
    public function setId(int $id): PostcodeRegion
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostcodeArea(): string
    {
        return $this->postcodeArea;
    }

    /**
     * @param string $postcodeArea
     * 
     * @return PostcodeRegion
     */
    public function setPostcodeArea(string $postcodeArea): PostcodeRegion
    {
        $this->postcodeArea = $postcodeArea;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @param string $region
     * 
     * @return PostcodeRegion
     */
    public function setRegion(string $region): PostcodeRegion
    {
        $this->region = $region;
        return $this;
    }
}
