<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * Hook result for redirects
 */
class HookResultRedirect implements HookResultInterface
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
     * @return HookResultRedirect
     */
    public function setData(array $data): HookResultRedirect
    {
        $this->data = $data;
        return $this;
    }
}
