<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

use Exception;
use Application\Karfu\Journey\Hook\Hook;
use Application\Karfu\Journey\Hook\HookFunctionFactory;
use Application\Karfu\Journey\Hook\HookResultInterface;

/**
 * HookExecuter executes hooks and returns the response
 */
class HookExecuter
{
    /**
     * @var HookFunctionFactory
     */
    private $hookFunctionFactory;

    /**
     * @param HookFunctionFactory $hookFunctionFactory
     */
    public function __construct(HookFunctionFactory $hookFunctionFactory)
    {
        $this->hookFunctionFactory = $hookFunctionFactory;
    }

    /**
     * Execute a hook
     * 
     * @param Hook $hook
     * 
     * @return mixed
     * @throws Exception
     */
    public function execute(Hook $hook)
    {
        $hookMap = array_combine(
            ['class', 'method'],
            explode('::', $hook->getFunction())
        );
        if ($hookMap) {
            $class = $this->hookFunctionFactory->create($hookMap['class']);
            $method = $hookMap['method'];

            if (method_exists($class, $method)) {
                return $class->$method($hook->getData());
            } else {
                throw new Exception('Error executing hook function');
            }
        } else {
            throw new Exception('Error executing hook function');
        }
    }
}
