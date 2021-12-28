<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

/**
 * HookResultInterface is the interface for hook responses
 */
interface HookResultInterface
{
    public function getData();
    public function setData(array $data);
}
