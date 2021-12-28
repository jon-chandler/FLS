<?php

declare(strict_types = 1);

namespace Application\Helper;

use Application\Service\ApiCacheService;
use Application\Service\SessionAnswerService;

/**
 * SnapshotAnswer takes a snapshot of answers in a key > value format for
 * saving against (but not limited to) a shortlisted vehicle in the database.
 * Not every answers gets snapshotted, only answers required to view vehicle
 * breakdown page.
 */
class SnapshotAnswer
{
    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @param SessionAnswerService $sessionAnswerService
     * @param ApiCacheService $apiCacheService
     */
    public function __construct(
        SessionAnswerService $sessionAnswerService,
        ApiCacheService $apiCacheService
    )
    {
        $this->sessionAnswerService = $sessionAnswerService;
        $this->apiCacheService = $apiCacheService;
    }

    /**
     * Take a snapshot of answers
     * 
     * @param array $answers
     * 
     * @return array
     */
    public function takeSnapshot(array $answers): array
    {
        $snapshotAnswers = [];
        $sessionKey = session_id();

        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            [
                'howLongTerm',
                'howMuchDoYouHave',
                'whatVehicleDoYouHave',
                'yourVehicleValuation',
                'journeyKind',
                'howOften',
                'whatIsYourEstimatedMileage',
                'wouldYouConsiderSharingYourCarOrDriveway'
            ],
            $answers
        );

        foreach ($tempAnswers as $key => $tempAnswer) {
            switch ($key) {
                case 'yourVehicleValuation':
                    $vehicleValuationType = $tempAnswers[$key][0]->getOption()->getOptionTitle();
                    $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($sessionKey, 'cap-hpi', 'vrms');
                    $carData = $apiCache->getData();
                    $privateValuation = (isset($carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'])) ?
                        (float) $carData['currentValuations']['valuations'][1]['valuationPoints'][0]['value'] : 0;

                    switch ($vehicleValuationType) {
                        case 'Private Sale':
                            $valuation = $privateValuation;
                            break;
                        case 'Part Exchange';
                            $valuation = (float) $privateValuation - $privateValuation / 20;
                            break;
                        case 'Car Buying Service':
                            $valuation = (float) $privateValuation - $privateValuation / 10;
                            break;
                    }

                    $snapshotAnswers[$key] = [
                        $vehicleValuationType,
                        $valuation
                    ];
                    break;
                case 'journeyKind':
                    foreach ($tempAnswer as $journeyKind) {
                        $snapshotAnswers[$key][] = [
                            $journeyKind->getValue(),
                            $journeyKind->getOption()->getOptionTitle()
                        ];
                    }
                    break;
                case 'howOften':
                    foreach ($tempAnswer as $howOften) {
                        $snapshotAnswers[$key][] = $howOften->getOption()->getOptionTitle();
                    }
                    break;
                case 'wouldYouConsiderSharingYourCarOrDriveway':
                    $snapshotAnswers[$key] = $tempAnswers[$key][0]->getOption()->getOptionTitle();
                    break;
                default:
                    $snapshotAnswers[$key] = $tempAnswer[0]->getValue();
            }
        }

        return $snapshotAnswers;
    }
}
