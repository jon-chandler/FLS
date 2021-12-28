<?php

declare(strict_types = 1);

namespace Application\Karfu\Journey;

use Application\Service\SessionAnswerService;
use Application\Service\VehicleTempService;

/**
 * DefaultSort provides default sorting of vehicle results
 */
class DefaultSort
{
    /**
	 * @var SessionAnswerService
	 */
	private $sessionAnswerService;

    /**
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @param SessionAnswerService $sessionAnswerService
     * @param VehicleTempService $vehicleTempService
     */
    public function __construct(
        SessionAnswerService $sessionAnswerService,
        VehicleTempService $vehicleTempService
    )
	{
		$this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleTempService = $vehicleTempService;
	}

    /**
     * Get default sorting in string format
     * 
     * @param array $answers
     * 
     * @return string
     */
    public function getAsString(array $answers)
    {
        $importantMatters = $this->sessionAnswerService->getAnswersByQuestionHandle('importantMatters', $answers);

        if (count($importantMatters) > 0) {
            $tempImportantMatters = [];

            foreach ($importantMatters as $importantMatter) {
                $option = $importantMatter->getOption();

                if ($option) {
                    $tempImportantMatters[$importantMatter->getValue()] = $option->getOptionTitle();
                }
            }

            ksort($tempImportantMatters);

            foreach ($tempImportantMatters as $tempImportantMatter) {
                switch (strtoupper($tempImportantMatter)) {
                    case 'THE ENVIRONMENT':
                        return 'Enviro Impact LOW';
                        break;
                    case 'CONVENIENCE':
                        return 'TCU LOW';
                        break;
                    case 'PRICE':
                        return 'TCU LOW';
                        break;
                    default:
                        return 'TCU LOW';
                }
            }
        }
        
        return'TCU LOW';
    }

    /**
     * Get default sorting in sort array format
     *     ['column' => 'value', 'ascDesc' => 'value']
     * 
     * @param array $answers
     * 
     * @return string
     */
    public function getAsSortArray(array $answers): array
    {
        $importantMatters = $this->sessionAnswerService->getAnswersByQuestionHandle('importantMatters', $answers);

        if (count($importantMatters) > 0) {
            $tempImportantMatters = [];

            foreach ($importantMatters as $importantMatter) {
                $option = $importantMatter->getOption();

                if ($option) {
                    $tempImportantMatters[$importantMatter->getValue()] = $option->getOptionTitle();
                }
            }

            if (count($tempImportantMatters) > 0) {
                ksort($tempImportantMatters);

                foreach ($tempImportantMatters as $tempImportantMatter) {
                    $tempMap = $this->vehicleTempService->mapSort($tempImportantMatter);

                    if (count($tempMap) > 0) {
                        return $tempMap;
                    }

                    break;
                }
            }
        }

        return $this->vehicleTempService->mapSort('Price');
    }
}
