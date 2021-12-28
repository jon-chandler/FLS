<?php

declare(strict_types=1);

namespace Application\Helper;

/**
 * Pagination is used for creating pagination
 */
class Pagination
{
    /**
     * @var int current page
     */
    private $currentPage = 1;

    /**
     * @var int previous page
     */
    private $prevPage;

    /**
     * @var int next page
     */
    private $nextPage;

    /**
     * @var int current offset
     */
    private $offset = 0;

    /**
     * @var int count of result per page
     */
    private $count;

    /**
     * @var int result count
     */
    private $resultCount;

    /**
     * @var int total number of pages
     */
    private $totalPages;

    /**
     * @var int maximum of previous pages to include
     */
    private $maxPrevPages;

    /**
     * @var int maximum of next pages to include
     */
    private $maxNextPages;

    /**
     * @var string the page query string key
     */
    private $pageQueryParam = 'page';

    /**
     * @var array pages to include
     */
    private $pages;

    /**
     * @param int $resultCount
     * @param int $count
     * 
     * @return void
     */
    public function __construct(int $resultCount, int $count)
    {
        $this->resultCount = $resultCount;
        $this->count = $count;
        $this->totalPages = (int) ceil($resultCount / $this->count);
        $this->offset = $this->count * ($this->currentPage - 1);
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @param int $page
     * 
     * @return Pagination
     */
    public function setCurrentPage(int $page): Pagination
    {
        if ($page <= 0) {
            $this->currentPage = 1;
        } else {
            $this->currentPage = $page;
        }
        $this->offset = $this->count * ($this->currentPage - 1);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPrevPage()
    {
        return $this->prevPage;
    }

    /**
     * @return int|null
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    /**
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * @return int
     */
    public function getMaxPrevPages(): int
    {
        return $this->maxPrevPages;
    }

    /**
     * @param int $maxPrevPages
     * 
     * @return Pagination
     */
    public function setMaxPrevPages($maxPrevPages): Pagination
    {
        $this->maxPrevPages = $maxPrevPages;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxNextPages(): int
    {
        return $this->maxNextPages;
    }

    /**
     * @param int $maxNextPages
     * 
     * @return Pagination
     */
    public function setMaxNextPages(int $maxNextPages): Pagination
    {
        $this->maxNextPages = $maxNextPages;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageQueryParam(): string
    {
        return $this->pageQueryParam;
    }

    /**
     * @param string $pageQueryParam
     * 
     * @return Pagination
     */
    public function setPageQueryParam(string $pageQueryParam): Pagination
    {
        $this->pageQueryParam = $pageQueryParam;
        return $this;
    }

    /**
     * Build & set list of pages
     * 
     * @return array
     */
    public function getPages(): array
    {
        if ($this->pages === null) {
            $pages = [];

            if ($this->currentPage > 1) {
                $this->prevPage = $this->currentPage - 1;
            }

            if ($this->currentPage < $this->totalPages) {
                $this->nextPage = $this->currentPage + 1;
            }

            $startFrom = $this->currentPage - $this->maxPrevPages;
            $endAt = $this->currentPage + $this->maxNextPages;

            for ($i = $startFrom; $i < $this->currentPage; $i++) {
                if ($i > 0) {
                    $pages[] = $i;
                }
            }

            for ($i = $this->currentPage; $i <= $endAt; $i++) {
                if ($i <= $this->totalPages) {
                    $pages[] = $i;
                }
            }
            $this->pages = $pages;
        }

        return $this->pages;
    }
}
