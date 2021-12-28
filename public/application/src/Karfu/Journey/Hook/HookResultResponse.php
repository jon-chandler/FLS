<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * Hook result for generic hook responses such as errors
 */
class HookResultResponse implements HookResultInterface
{
    /**
     * @var array
     */
    private $data;

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
     * @return HookResultResponse
     */
    public function setData(array $data): HookResultResponse
    {
        $this->data = $data;
        return $this;
    }
}
