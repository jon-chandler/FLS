<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * Hook result for injecting content into templates
 */
class HookResultTemplateInject implements HookResultInterface
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
     * @return HookResultTemplateInject
     */
    public function setData(array $data): HookResultTemplateInject
    {
        $this->data = $data;
        return $this;
    }
}
