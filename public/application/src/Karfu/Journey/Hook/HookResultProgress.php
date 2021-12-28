<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * Hook result for getting progress question types progress
 */
class HookResultProgress implements HookResultInterface
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
     * @return HookResultProgress
     */
    public function setData(array $data): HookResultProgress
    {
        $this->data = $data;
        return $this;
    }
}
