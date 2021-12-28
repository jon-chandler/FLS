<?php

declare(strict_types=1);

namespace Application\Model;

/**
 * Model for score item
 */
class ScoreItem
{
    /**
     * @var string
     */
    private $title;

    /**
     * 0 = '-', 1 = '=', 2 = '+'
     * 
     * @var int|null
     */
    private $scoreSymbol;

    /**
     * @var int|null
     */
    private $score;

    /**
     * @var bool
     */
    private $isScoreContributable;

    /**
     * @var array|null
     */
    private $graphData;

    /**
     * @var bool
     */
    private $isHidden = false;

    /**
     * @var array
     */
    private $additionalData;

    /**
     * @var string
     */
    private $footerContent;

    /**
     * @var bool
     */
    private $isGraphHidden = false;

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @var string $title
     * 
     * @return ScoreItem
     */
    public function setTitle(string $title): ScoreItem
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getScoreSymbol()
    {
        return $this->scoreSymbol;
    }

    /**
     * @var int $scoreSymbol
     * 
     * @return ScoreItem
     */
    public function setScoreSymbol(int $scoreSymbol): ScoreItem
    {
        // Score symbol must be between 0 and 2
        if ($scoreSymbol < 0 || $scoreSymbol > 2) {
            $scoreSymbol = 0;
        }

        $this->scoreSymbol = $scoreSymbol;
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
     * @return ScoreItem
     */
    public function setScore(int $score): ScoreItem
    {
        $this->score = $score;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsScoreContributable()
    {
        return $this->isScoreContributable;
    }

    /**
     * @var bool $isScoreContributable
     * 
     * @return ScoreItem
     */
    public function setIsScoreContributable(bool $isScoreContributable): ScoreItem
    {
        $this->isScoreContributable = $isScoreContributable;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getGraphData()
    {
        return $this->graphData;
    }

    /**
     * @param array $graphData
     * 
     * @return ScoreItem
     */
    public function setGraphData(array $graphData): ScoreItem
    {
        $this->graphData = $graphData;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * @param bool $isHidden
     * 
     * @return ScoreItem
     */
    public function setIsHidden(bool $isHidden): ScoreItem
    {
        $this->isHidden = $isHidden;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param array $additionalData
     * 
     * @return ScoreItem
     */
    public function setAdditionalData(array $additionalData): ScoreItem
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFooterContent()
    {
        return $this->footerContent;
    }

    /**
     * @param string $footerContent
     * 
     * @return ScoreItem
     */
    public function setFooterContent(string $footerContent): ScoreItem
    {
        $this->footerContent = $footerContent;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsGraphHidden(): bool
    {
        return $this->isGraphHidden;
    }

    /**
     * @param bool $isGraphHidden
     * 
     * @return ScoreItem
     */
    public function setIsGraphHidden(bool $isGraphHidden): ScoreItem
    {
        $this->isGraphHidden = $isGraphHidden;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'scoreSymbol' => $this->scoreSymbol,
            'score' => $this->score,
            'isScoreContributable' => $this->isScoreContributable,
            'graphData' => $this->graphData,
            'isHidden' => $this->isHidden,
            'additionalData' => $this->additionalData,
            'footerContent' => $this->footerContent,
            'isGraphHidden' => $this->isGraphHidden
        ];
    }
}
