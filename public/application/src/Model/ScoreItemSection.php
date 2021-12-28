<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Model\ScoreItem;

/**
 * Model for Score Item Section
 */
class ScoreItemSection
{
    /**
     * @var string|null
     */
    private $title;

    /**
     * @var array
     */
    private $scoreItems = [];

    /**
     * @var int
     */
    private $score;

    /**
     * @var bool
     */
    private $isScoreContributable;

    /**
     * @var string
     */
    private $synopsis;

    /**
     * @var int
     */
    private $scoreWeighting = 0;

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @var string $title
     * 
     * @return ScoreItemSection
     */
    public function setTitle(string $title): ScoreItemSection
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @var int $score
     * 
     * @return ScoreItemSection
     */
    public function setScore(int $score): ScoreItemSection
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return array
     */
    public function getScoreItems(): array
    {
        return $this->scoreItems;
    }

    /**
     * @var array $scoreItems
     * 
     * @return ScoreItemSection
     */
    public function setScoreItems(array $scoreItems): ScoreItemSection
    {
        $this->scoreItems = $scoreItems;
        return $this;
    }

    /**
     * @var ScoreItem $scoreItem
     * 
     * @return ScoreItemSection
     */
    public function addScoreItem(ScoreItem $scoreItem): ScoreItemSection
    {
        $this->scoreItems[] = $scoreItem;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsScoreContributable(): bool
    {
        return $this->isScoreContributable;
    }

    /**
     * @var bool $isScoreContributable
     * 
     * @return ScoreItemSection
     */
    public function setIsScoreContributable(bool $isScoreContributable): ScoreItemSection
    {
        $this->isScoreContributable = $isScoreContributable;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSynopsis()
    {
        return $this->synopsis;
    }

    /**
     * @var string $synopsis
     * 
     * @return ScoreItemSection
     */
    public function setSynopsis(string $synopsis): ScoreItemSection
    {
        $this->synopsis = $synopsis;
        return $this;
    }

    /**
     * @return int
     */
    public function getScoreWeighting()
    {
        return $this->scoreWeighting;
    }

    /**
     * @var int $scoreWeighting
     * 
     * @return ScoreItemSection
     */
    public function setScoreWeighting(int $scoreWeighting) {
        $this->scoreWeighting = $scoreWeighting;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'scoreItems' => array_map(function ($scoreItem) {
                return $scoreItem->toArray();
            }, $this->scoreItems),
            'score' => $this->score,
            'isScoreContributable' => $this->isScoreContributable,
            'synopsis' => $this->synopsis,
            'scoreWeighting' => $this->scoreWeighting
        ];
    }
}
