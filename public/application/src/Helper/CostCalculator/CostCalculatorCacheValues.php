<?php

declare(strict_types=1);

namespace Application\Helper\CostCalculator;

use Application\Service\ApiCacheService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Session\SessionValidator;
use Core;

/**
 * CostCalculatorCacheValues fetches cached values for the cost calculator such as vehicle valuations
 */
class CostCalculatorCacheValues
{
    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @param SessionValidator $sessionValidator
     * @param SessionAnswerService $sessionAnswerService
     * @param ApiCacheService $apiCacheService
     */
    public function __construct(
        SessionValidator $sessionValidator,
        SessionAnswerService $sessionAnswerService,
        ApiCacheService $apiCacheService
    )
    {
        $this->sessionValidator = $sessionValidator;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->apiCacheService = $apiCacheService;
    }

    /**
     * Get the cached values
     * 
     * @param array $answers
     *
     * @return array
     */
    public function get(array $answers): array
    {
        $cacheValues = [];
        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [
                'whatVehicleDoYouHave',
                'yourVehicleValuation'
            ],
            $answers
        );

        if (
            array_key_exists('whatVehicleDoYouHave', $tempAnswers)
            && array_key_exists('yourVehicleValuation', $tempAnswers)
        ) {
            $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
            $sessionKey = ($session) ? $session->getId() : '';

            $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
            $carData = $apiCache->getData();
            $cacheValues['vehicleValuation'] = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                    (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;
        }

        return $cacheValues;
    }
}