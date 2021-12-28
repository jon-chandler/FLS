<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey\Hook;

/**
 * Hook is a model class that holds all the attributes for performing a hook
 */
class Hook
{
    /**
     * List of hook types, essentially these are trigger points for when the hooks can be executed
     */
    const SERVER_ON_QUESTION_LOAD = 'serverOnQuestionLoad';
    const SERVER_ON_QUESTION_SUBMIT = 'serverOnQuestionSubmit';
    const SERVER_ON_SUMMARY_QUESTION_LOAD = 'serverOnSummaryQuestionLoad';
    const SERVER_ON_OPTION_LOAD = 'serverOnOptionLoad';
    const SERVER_ON_OPTION_SUBMIT = 'serverOnOptionSubmit';

    /**
     * @var string $type The type of hook
     */
    private $type;

    /**
     * @var string $function The hook class & function to execute concatenated by double colon e.g HookFunction::getEstimatedMileage
     */
    private $function;
    
    /**
     * @var array $data The data required for the function
     */
    private $data;

    /**
     * @param string $type
     * @param string $function
     * @param array $data
     * 
     * @return void
     */
    public function __construct(string $type, string $function, array $data = [])
    {
        $this->type = $type;
        $this->function = $function;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * 
     * @return Hook
     */
    public function setType(string $type): Hook
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @param string $function
     * 
     * @return Hook
     */
    public function setFunction(string $function): Hook
    {
        $this->function = $function;
        return $this;
    }

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
     * @return Hook
     */
    public function setData(array $data): Hook
    {
        $this->data = $data;
        return $this;
    }
}
