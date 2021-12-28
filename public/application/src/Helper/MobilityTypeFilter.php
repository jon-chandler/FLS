<?php

declare(strict_types = 1);

namespace Application\Helper;

use Application\Service\ApiCacheService;
use Application\Service\KarfuAttributeMapService;
use Application\Service\PostcodeRegionService;
use Application\Service\SessionAnswerService;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Session\SessionValidator;
use Core;

/**
 * MobilityTypeFilter is used to create mobility type filters
 */
class MobilityTypeFilter
{
    /**
     * @var Connection
     */
    private $con;

    /**
     * @var SessionAnswerService
     */
    private $sessionAnswerService;

    /**
     * @var SessionValidator
     */
    private $sessionValidator;

    /**
     * @var ApiCacheService
     */
    private $apiCacheService;

    /**
     * @var PostcodeRegionService
     */
    private $postcodeRegionService;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService
     * @param SessionValidator $sessionValidator
     * @param ApiCacheService $apiCacheService
     * @param PostcodeRegionService $postcodeRegionService
     * @param KarfuAttributeMapService $karfuAttributeMapService
     */
    public function __construct(
        Connection $con,
        SessionAnswerService $sessionAnswerService,
        SessionValidator $sessionValidator,
        ApiCacheService $apiCacheService,
        PostcodeRegionService $postcodeRegionService,
        KarfuAttributeMapService $karfuAttributeMapService
    )
    {
        $this->con = $con;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->sessionValidator = $sessionValidator;
        $this->apiCacheService = $apiCacheService;
        $this->postcodeRegionService = $postcodeRegionService;
        $this->karfuAttributeMapService = $karfuAttributeMapService;

        $session = $this->sessionValidator->hasActiveSession() ? Core::make('session') : null;
        $this->sessionKey = ($session) ? $session->getId() : '';
    }

    /**
     * Generate the main filter
     * 
     * @param array $answers
     * 
     * @return array
     */
    public function getMobilityTypes(array $answers): array
    {
        $mobilityTypes = [];

        $createMobilityTypeFilter = $this->createMobilityTypeFilter($answers);
        if (count($createMobilityTypeFilter) > 0) {
            $mobilityTypes = $createMobilityTypeFilter;
        }

        $createWhereAreYouTypeFilter = $this->createWhereAreYouFilter($answers);
        if (count($createWhereAreYouTypeFilter) > 0) {
            $tempMobilityTypes = [];
            foreach ($mobilityTypes as $mobilityType) {
                if (in_array($mobilityType, $createWhereAreYouTypeFilter)) {
                    $tempMobilityTypes[] = $mobilityType;
                }
            }
            $mobilityTypes = $tempMobilityTypes;
        }

        return $mobilityTypes;
    }

    /**
     * Create filters from api cache call of 'mobility-choices'
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createMobilityTypeFilter(array $answers): array
    {
        $mobilityTypes = [];
        $sessionKey = null;
        $service = 'karfu';
        $call = 'mobility-choices';

        $apiCache = $this->apiCacheService->readBySessionKeyServiceCall($this->sessionKey, $service, $call);

        if ($apiCache) {
            $mobilityTypes = $apiCache->getData();
        }

        return $mobilityTypes;
    }

    /**
     * Create location filter for mobility types
     * 
     * @param array $answers
     * 
     * @return array
     */
    private function createWhereAreYouFilter(array $answers): array
    {
        $mobilityTypes = [];
        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles(
            ['whereAreYou'],
            $answers
        );

        if (isset($filteredAnswers['whereAreYou']) && count($filteredAnswers['whereAreYou']) === 1) {

            // Get region from postcode
            $postcodeArea = $filteredAnswers['whereAreYou'][0]->getValue();
            $postcodeRegion = $this->postcodeRegionService->readByPostcodeArea($postcodeArea);

            if ($postcodeRegion) {

                // Get partners by region
                $region = $postcodeRegion->getRegion();
                $results = $this->con->fetchAll(
                    'SELECT * FROM btPartnerManager WHERE active = ?',
                    [1]
                );

                if ($results) {

                    // Filter partner results by region
                    $results = array_filter($results, function ($result) use ($region) {
                        if (!empty($result['locations'])) {
                            $locations = explode(',', $result['locations']);
                            $locations = array_map(function ($location) {
                                return strtolower(trim($location));
                            }, $locations);
                            if (in_array(strtolower($region), $locations)) {
                                return true;
                            } else {
                                return false;
                            }
                        }
                        return true;
                    });
                    
                    $attributes = [];

                    foreach ($results as $result) {
                        $attributes[] = $result['partner_type'];
                    }

                    $attributes = array_unique($attributes);

                    // Map partner types to mobility types
                    // e.g. Car retailer > BUYING, Leasing provider > LEASING
                    if (count($attributes) > 0) {
                        $maps = $this->karfuAttributeMapService->mapToKarfuAttributes($attributes, 'partnerTypeToMobilityChoice');

                        $mobilityTypes = array_map(function($mobilityType) {
                            return $mobilityType['attribute_name'];
                        }, $maps);
                    }
                }
            }
        }

        return $mobilityTypes;
    }
}
