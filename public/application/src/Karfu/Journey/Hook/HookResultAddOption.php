<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * Hook result for adding an option to a question
 */
class HookResultAddOption implements HookResultInterface
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
     * @return HookResultAddOption
     */
    public function setData(array $data): HookResultAddOption
    {
        $this->data = $data;
        return $this;
    }
}
