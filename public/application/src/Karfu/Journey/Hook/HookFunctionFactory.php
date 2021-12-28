<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Core;
use Exception;

/**
 * HookFunctionFactory creates the Hook class that will contain the executable hook function
 */
class HookFunctionFactory
{
    /**
     * Creates a hook class by name and returns it
     * 
     * @param string $className
     * 
     * @return mixed
     * @throws Exception
     */
    public function create(string $className)
    {
        $className = __NAMESPACE__ . '\\' . $className;
        if (class_exists($className)) {
            return Core::make($className);
        } else {
            throw new Exception('Error creating hook function class');
        }
    }
}
