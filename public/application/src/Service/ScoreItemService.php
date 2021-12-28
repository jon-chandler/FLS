<?php

declare(strict_types = 1);

namespace Application\Service;

use Application\Helper\CostCalculator\CostCalculator;
use Application\Helper\CostCalculator\CostCalculatorFactory;
use Application\Helper\VehicleFormula;
use Application\Model\ScoreItem;
use Application\Model\ScoreItemSection;
use Application\Model\VehicleCost;
use Application\Service\KarfuAttributeMapService;
use Application\Service\SessionAnswerService;
use Application\Service\VehicleTempService;
use Concrete\Core\Database\Connection\Connection;

/**
 * Service class for score item
 */
class ScoreItemService
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
     * @var VehicleTempService
     */
    private $vehicleTempService;

    /**
     * @var CostCalculatorFactory
     */
    private $costCalculatorFactory;

    /**
     * @var KarfuAttributeMapService
     */
    private $karfuAttributeMapService;

    /**
     * @var VehicleFormula
     */
    private $vehicleFormula;

    /**
     * @param Connection $con
     * @param SessionAnswerService $sessionAnswerService,
     * @param VehicleTempService $vehicleTempService,
     * @param CostCalculatorFactory $costCalculatorFactory,
     * @param KarfuAttributeMapService $karfuAttributeMapService,
     * @param VehicleFormula $vehicleFormula
     */
    public function __construct(
        Connection $con,
        SessionAnswerService $sessionAnswerService,
        VehicleTempService $vehicleTempService,
        CostCalculatorFactory $costCalculatorFactory,
        KarfuAttributeMapService $karfuAttributeMapService,
        VehicleFormula $vehicleFormula
    )
    {
        $this->con = $con;
        $this->sessionAnswerService = $sessionAnswerService;
        $this->vehicleTempService = $vehicleTempService;
        $this->costCalculatorFactory = $costCalculatorFactory;
        $this->karfuAttributeMapService = $karfuAttributeMapService;
        $this->vehicleFormula = $vehicleFormula;
    }

    /**
     * Score & build array of score items
     * Main function for suitability scoring
     * 
     * @param string $sessionKey
     * @param array $answers
     * @param array $vehicle
     * @param string $mobilityChoice
     * @param string $mobilityChoice
     * @param array|null $ownedVehicle
     * @param CostCalculator|null $ovCostCalculator
     * @param array|null $ovCosts
     * @param array $minMaxAvgs
     * @param array $quartiles
     * 
     * @return array
     */
    public function build(
        string $sessionKey,
        array $answers,
        array $vehicle,
        string $mobilityChoice,
        string $mobilityType,
        $ownedVehicle,
        $ovCostCalculator,
        $ovCosts,
        array $minMaxAvgs,
        array $quartiles
    ): array
    {
        // Get mapped vehicle type
        $map = $this->karfuAttributeMapService->mapToKarfuAttribute($vehicle['VehicleType'], 'vehicleTableToHuman');

        // Get cost calculator class
        $costCalculator = $this->costCalculatorFactory->create($map, $mobilityChoice, $mobilityType);

        // Calculate & get costs
        $vehicleCosts = $costCalculator->calculateCosts($vehicle, $answers);

        // Get score item section for each sections
        $breakdownScore = $this->breakdownScore($vehicle, $minMaxAvgs);
        $thisSelection = $this->thisSelection(
            $sessionKey,
            $answers,
            $vehicle,
            $vehicleCosts,
            $costCalculator,
            $ownedVehicle,
            $ovCostCalculator,
            $ovCosts
        );
        $bestOverall = $this->bestOverall($vehicle, $minMaxAvgs);
        $bestRelativetoOtherResults = $this->bestRelativetoOtherResults($vehicle, $minMaxAvgs, $quartiles);
        $yourEssentials = $this->yourEssentials($vehicle, $answers);

        // Calculate score from all score item sections
        $karfuScore = 0;
        $scorableCount = 0;
        $maxKarfuScore = 0;
        foreach ([$thisSelection, $bestRelativetoOtherResults, $yourEssentials, $bestOverall] as $scoreItemSection) {
            if ($scoreItemSection->getIsScoreContributable() === true) {
                $weighting = $scoreItemSection->getScoreWeighting();
                $maxKarfuScore += $weighting;
                $karfuScore += ($weighting > 0) ? ($scoreItemSection->getScore() / 100) * $weighting : 0;
                $scorableCount++;
            }
        }

        // Round the score
        $karfuScore = round($karfuScore);

        // Generate dynamic text for display next to suitability score
        $karfuScoreCapText = ($this->isQuickSearch() ?
            'The maximum score for a Quick Search is ' . $maxKarfuScore . '%'
            : ($maxKarfuScore === 100 ?
                'The maximum score for a Detailed Search is 100%'
                : 'The maximum score for a Detailed Search with your given answers is ' . $maxKarfuScore . '%')
            );

        // Start build of function response
        $return = [
            'karfuScore' => $karfuScore,
            'maxKarfuScore' => $maxKarfuScore,
            'breakdownScoreItems' => array_map(
                function ($scoreItem) {
                    return $scoreItem->toArray();
                },
                $breakdownScore->getScoreItems()
            ),
            'sections' => [],
            'karfuScoreCapText' => $karfuScoreCapText
        ];

        // Add best to overall section to response
        if (count($bestOverall->getScoreItems()) > 0) {
            $key = count($return['sections']);
            $return['sections'][] = [
                'header' => 'Compared To Best In Search Results',
                'subSections' => [
                    $bestOverall->toArray()
                ]
            ];
        }

        // Add best relative to other results section to response
        if (count($bestRelativetoOtherResults->getScoreItems()) > 0) {
            $return['sections'][] = [
                'header' => 'Compared To Average In Search Results',
                'subSections' => [
                    $bestRelativetoOtherResults->toArray()
                ]
            ];
        }

        // Add nice to haves section to response
        if (count($yourEssentials->getScoreItems()) > 0) {
            $return['sections'][] = [
                'header' => 'Based on your Nice to Haves',
                'subSections' => [
                    $yourEssentials->toArray()
                ]
            ];
            $return['niceToHaves'] = true;
        } else {
            $return['niceToHaves'] = false;
        }

        // Add current vehicle section to response
        if (count($thisSelection->getScoreItems()) > 0) {
            $return['sections'][] = [
                'header' => 'Compared to your Current Vehicle',
                'subSections' => [
                    $thisSelection->toArray()
                ]
            ];
        }

        return $return;
    }

    /**
     * Build score item section for current vehicle section
     * 
     * @param string $sessionKey
     * @param array $answers
     * @param array $vehicle
     * @param array $vehicleCosts
     * @param CostCalculator $costCalculator
     * @param array|null $ownedVehicle
     * @param CostCalculator|null $ovCostCalculator
     * @param array|null $ovCosts
     * 
     * @return ScoreItemSection
     */
    private function thisSelection(
        string $sessionKey,
        array $answers,
        array $vehicle,
        array $vehicleCosts,
        CostCalculator $costCalculator,
        $ownedVehicle,
        $ovCostCalculator,
        $ovCosts
    ): ScoreItemSection
    {
        $scoreItems = [];
        $scoreItemSection = new ScoreItemSection();

        // Set section score weighting
        $scoreItemSection->setScoreWeighting(10);

        // Get answers to questions
        $tempAnswers = $this->sessionAnswerService->getAnswersByQuestionHandles([
            'whatVehicleDoYouHave',
            'howLongTerm',
            'whatIsYourEstimatedMileage'
        ], $answers);
        $scorableCount = 0;

        if (array_key_exists('whatVehicleDoYouHave', $tempAnswers)) {
            $ownershipPeriod = 0;
            $annualMileage = 0;

            if (array_key_exists('howLongTerm', $tempAnswers)) {
                $ownershipPeriod = (int) $tempAnswers['howLongTerm'][0]->getValue();
            }
            
            if (array_key_exists('whatIsYourEstimatedMileage', $tempAnswers)) {
                $annualMileage = (int) $tempAnswers['whatIsYourEstimatedMileage'][0]->getValue();
            }

            if ($ownedVehicle) {
                // Set the section title with vehicle details
                $scoreItemSection->setTitle($ownedVehicle['ManName'] . ' ' . $ownedVehicle['ModelName']);

                // Get costs
                $ovTotalRunningCost = $ovCostCalculator->getTotalCostByCategory(VehicleCost::CAT_NET_RUNNING, $ovCosts, $answers);
                $ovTotalCarbon = $this->vehicleFormula->calcAnnualCarbonCost($annualMileage, (int) $ownedVehicle['CO2GKM']) * $ownershipPeriod;
                $totalRunningCost = $costCalculator->getTotalCostByCategory(VehicleCost::CAT_NET_RUNNING, $vehicleCosts, $answers);
                $totalCarbon = $this->vehicleFormula->calcAnnualCarbonCost($annualMileage, (int) $vehicle['CO2GKM']) * $ownershipPeriod;
                $locationDistance = (float) $vehicle['LocationDistance'];
                $ovLocationDistance = 0;

                if ($totalRunningCost > $ovTotalRunningCost) {
                    $score = 0;
                    $scoreSymbol = 0;
                    $title = 'More Expensive To Run';
                } else if ($totalRunningCost < $ovTotalRunningCost) {
                    $score = 2;
                    $scoreSymbol = 2;
                    $title = 'Less Expensive To Run';
                } else {
                    $score = 1;
                    $scoreSymbol = 1;
                    $title = 'Equally as Expensive To Run';
                }
                $scoreItem = new ScoreItem();
                $scoreItem->setTitle($title)
                    ->setScore($score)
                    ->setScoreSymbol($scoreSymbol)
                    ->setIsScoreContributable(true)
                    ->setAdditionalData([
                        ['Your Vehicle', '£' . number_format($ovTotalRunningCost)],
                        ['This Vehicle', '£' . number_format($totalRunningCost)],
                        ['Difference', '£' . number_format(($ovTotalRunningCost - $totalRunningCost))]
                    ])
                    ->setFooterContent('Based on running costs over period (£)')
                    ->setIsGraphHidden(true);
                $scoreItemSection->addScoreItem($scoreItem);
                $scorableCount++;

                if ($totalCarbon > $ovTotalCarbon) {
                    $score = 0;
                    $scoreSymbol = 0;
                    $title = 'More Harmful';
                } else if ($totalCarbon < $ovTotalCarbon) {
                    $score = 2;
                    $scoreSymbol = 2;
                    $title = 'Less Harmful';
                } else {
                    $score = 1;
                    $scoreSymbol = 1;
                    $title = 'Equally as Harmful';
                }
                $scoreItem = new ScoreItem();
                $scoreItem->setTitle($title)
                    ->setScore($score)
                    ->setScoreSymbol($scoreSymbol)
                    ->setIsScoreContributable(true)
                    ->setAdditionalData([
                        ['Your Vehicle', number_format($ovTotalCarbon, 2) . ' tonnes'],
                        ['This Vehicle', number_format($totalCarbon, 2) . ' tonnes'],
                        ['Difference', number_format(($ovTotalCarbon -  $totalCarbon), 2) . ' tonnes']
                    ])
                    ->setFooterContent('Based on CO2 Emissions over use (Tonnes)')
                    ->setIsGraphHidden(true);
                $scoreItemSection->addScoreItem($scoreItem);
                $scorableCount++;

                if ($locationDistance > $ovLocationDistance) {
                    $score = 0;
                    $scoreSymbol = 0;
                    $title = 'Less Convenient';
                } else if ($locationDistance < $ovLocationDistance) {
                    $score = 2;
                    $scoreSymbol = 2;
                    $title = 'More Convenient';
                } else {
                    $score = 1;
                    $scoreSymbol = 1;
                    $title = 'Equally as Convenient';
                }
                $scoreItem = new ScoreItem();
                $scoreItem->setTitle($title)
                    ->setScore($score)
                    ->setScoreSymbol($scoreSymbol)
                    ->setIsScoreContributable(true)
                    ->setAdditionalData([
                        ['Your Vehicle', number_format($ovLocationDistance) . ' miles'],
                        ['This Vehicle', number_format($locationDistance) . ' miles'],
                        ['Difference', number_format(($ovLocationDistance - $locationDistance)) . ' miles']
                    ])
                    ->setFooterContent('Based on Distance (Miles)')
                    ->setIsGraphHidden(true);
                $scoreItemSection->addScoreItem($scoreItem);
                $scorableCount++;
            }
        }

        // Sum score & max score
        $scoreItemsSum = 0;
        $maxScoreValue = 0;
        foreach ($scoreItemSection->getScoreItems() as $scoreItem) {
            if ($scoreItem->getIsScoreContributable() === true) {
                $scoreItemsSum += $scoreItem->getScore();
                $maxScoreValue += 2;
            }
        }

        if ($scorableCount > 0) {
            // Calculate score out of 100
            $scoreItemSectionScore = ($maxScoreValue > 0) ? round(($scoreItemsSum * 100) / $maxScoreValue) : 0;
            $scoreItemSection->setIsScoreContributable(true);
        } else {
            $scoreItemSectionScore = 0;
            $scoreItemSection->setIsScoreContributable(false);
        }

        // Set score
        $scoreItemSection->setScore((int) $scoreItemSectionScore);

        // Sort score items in +, =, - order
        $scoreItems = $scoreItemSection->getScoreItems();
        usort($scoreItems, function ($a, $b) {
            return ($b->getScoreSymbol() < $a->getScoreSymbol()) ? -1 : 1;
        });
        $scoreItemSection->setScoreItems($scoreItems);

        return $scoreItemSection;
    }

    /**
     * Build score item section for best overall section
     * 
     * @param array $vehicle
     * @param array $minMaxAvgs
     * 
     * @return ScoreItemSection
     */
    private function bestOverall(array $vehicle, array $minMaxAvgs): ScoreItemSection
    {
        $scoreItems = [];
        $scoreItemSection = new ScoreItemSection();
        $minTotalCost = (float) $minMaxAvgs['MinTotalCost'];
        $minEnviroImpact = (float) $minMaxAvgs['MinEnviroImpact'];
        $minLocationDistance = (float) $minMaxAvgs['MinLocationDistance'];
        $totalCost = (float) $vehicle['TotalCost'];
        $enviroImpact = (int) $vehicle['EnviroImpact'];
        $locationDistance = (float) $vehicle['LocationDistance'];

        if ($totalCost > $minTotalCost) {
            $score = 0;
            $scoreSymbol = 0;
            $title = 'Not Lowest Total Cost of Use';
        } else if ($totalCost < $avgTotalCost) {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Lowest Total Cost of Use';
        } else {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Lowest Total Cost of Use';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(false)
            ->setIsGraphHidden(true);
        $scoreItemSection->addScoreItem($scoreItem);

        if ($enviroImpact > $minEnviroImpact) {
            $score = 0;
            $scoreSymbol = 0;
            $title = 'Not Lowest Impact on Environment';
        } else if ($enviroImpact < $minEnviroImpact) {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Lowest Impact on Environment';
        } else {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Lowest Impact on Environment';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(false)
            ->setIsGraphHidden(true);
        $scoreItemSection->addScoreItem($scoreItem);

        if ($locationDistance > $minLocationDistance) {
            $score = 0;
            $scoreSymbol = 0;
            $title = 'Not Most Convenient';
        } else if ($locationDistance < $minLocationDistance) {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Most Convenient';
        } else {
            $score = 2;
            $scoreSymbol = 2;
            $title = 'Most Convenient';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(false)
            ->setIsGraphHidden(true);
        $scoreItemSection->addScoreItem($scoreItem);

        // This seciton is not to be scored
        $scoreItemSectionScore = 0;
        $scoreItemSection->setScore($scoreItemSectionScore);
        $scoreItemSection->setIsScoreContributable(false);

        // Sort score items in +, =, - order
        $scoreItems = $scoreItemSection->getScoreItems();
        usort($scoreItems, function ($a, $b) {
            return ($b->getScoreSymbol() < $a->getScoreSymbol()) ? -1 : 1;
        });
        $scoreItemSection->setScoreItems($scoreItems);

        return $scoreItemSection;
    }

    /**
     * Build score item section for breakdown section
     * (Section next to the final karfu score)
     * 
     * @param array $vehicle
     * @param array $minMaxAvgs
     * 
     * @return ScoreItemSection
     */
    private function breakdownScore(array $vehicle, array $minMaxAvgs): ScoreItemSection
    {
        $scoreItems = [];
        $scoreItemSection = new ScoreItemSection();
        $scoreItemSection->setTitle('Compared To Best In Search Results:');
        $minTotalCost = (float) $minMaxAvgs['MinTotalCost'];
        $minEnviroImpact = (float) $minMaxAvgs['MinEnviroImpact'];
        $minLocationDistance = (float) $minMaxAvgs['MinLocationDistance'];
        $maxTotalCost = (float) $minMaxAvgs['MaxTotalCost'];
        $maxEnviroImpact = (float) $minMaxAvgs['MaxEnviroImpact'];
        $maxLocationDistance = (float) $minMaxAvgs['MaxLocationDistance'];
        $totalCost = (float) $vehicle['TotalCost'];
        $enviroImpact = (float) $vehicle['EnviroImpact'];
        $locationDistance = (float) $vehicle['LocationDistance'];

        if ($totalCost === $maxTotalCost) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Highest Total Cost of Use')
                ->setScore(0)
                ->setScoreSymbol(0)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }
        if ($totalCost === $minTotalCost) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Lowest Total Cost of Use')
                ->setScore(2)
                ->setScoreSymbol(2)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }

        if ($enviroImpact === $maxEnviroImpact) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Highest Impact on Environment')
                ->setScore(0)
                ->setScoreSymbol(0)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }
        if ($enviroImpact === $minEnviroImpact) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Lowest Impact on Environment')
                ->setScore(2)
                ->setScoreSymbol(2)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }

        if ($locationDistance === $maxLocationDistance) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Least Convenient')
                ->setScore(0)
                ->setScoreSymbol(0)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }
        if ($locationDistance === $minLocationDistance) {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Most Convenient')
                ->setScore(2)
                ->setScoreSymbol(2)
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }

        // This section is not to be scored
        $scoreItemSection->setScore(0);
        $scoreItemSection->setIsScoreContributable(false);

        // Sort score items in +, =, - order
        $scoreItems = $scoreItemSection->getScoreItems();
        usort($scoreItems, function ($a, $b) {
            return ($b->getScoreSymbol() < $a->getScoreSymbol()) ? -1 : 1;
        });
        $scoreItemSection->setScoreItems($scoreItems);

        return $scoreItemSection;
    }

    /**
     * Build score item section for best relative to other results section
     * 
     * @param array $vehicle
     * @param array $minMaxAvgs
     * @param array $quartiles
     * 
     * @return ScoreItemSection
     */
    private function bestRelativetoOtherResults(array $vehicle, array $minMaxAvgs, array $quartiles): ScoreItemSection
    {
        $scoreItems = [];
        $scoreItemSection = new ScoreItemSection();

        // Set section score weighting
        $scoreItemSection->setScoreWeighting(50);

        $minTotalCost = (float) $minMaxAvgs['MinTotalCost'];
        $minTotalCo2 = (float) $minMaxAvgs['MinTotalCO2'];
        $minLocationDistance = (float) $minMaxAvgs['MinLocationDistance'];
        $maxTotalCost = (float) $minMaxAvgs['MaxTotalCost'];
        $maxTotalCo2 = (float) $minMaxAvgs['MaxTotalCO2'];
        $maxLocationDistance = (float) $minMaxAvgs['MaxLocationDistance'];
        $avgTotalCost = (float) $minMaxAvgs['AvgTotalCost'];
        $avgTotalCo2 = (float) $minMaxAvgs['AvgTotalCO2'];
        $avgLocationDistance = (float) $minMaxAvgs['AvgLocationDistance'];
        $totalCost = (float) $vehicle['TotalCost'];
        $totalCo2 = (float) $vehicle['TotalCO2'];
        $locationDistance = (float) $vehicle['LocationDistance'];

        if ($totalCost > $avgTotalCost) {
            $score = 0;
            $scoreSymbol = 0;
            $title = 'More Expensive';
        } else if ($totalCost < $avgTotalCost) {
            $scoreSymbol = 2;
            $title = 'Less Expensive';

            // Use quartiles to score to get a more varied set of scores
            if ($totalCost < $quartiles['TotalCostQuartile4']) {
                $score = 5;
            } else if ($totalCost < $quartiles['TotalCostQuartile3']) {
                $score = 4;
            } else if ($totalCost < $quartiles['TotalCostQuartile2']) {
                $score = 3;
            } else {
                $score = 2;
            }
        } else {
            $score = 1;
            $scoreSymbol = 1;
            $title = 'Equally as Expensive';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(true)
            ->setGraphData([
                'title' => null,
                'dataType' => 'currency',
                'preFix' => '£',
                'data' => [
                    ['value' => round($minTotalCost)],
                    ['value' => round($maxTotalCost)],
                    ['value' => round($totalCost)]
                ]
            ])
            ->setAdditionalData([
                ['Results Average', '£' . number_format($avgTotalCost)],
                ['This Vehicle', '£' . number_format($totalCost)],
                ['Difference', '£' . number_format(($totalCost - $avgTotalCost))]
            ])
            ->setFooterContent('Based on Total Cost of Use (TCU)')
            ->setIsGraphHidden(false);
        $scoreItemSection->addScoreItem($scoreItem);

        if ($totalCo2 > $avgTotalCo2) {
            $score = 0;
            $scoreSymbol = 0;
            $title = 'More Harmful';
        } else if ($totalCo2 < $avgTotalCo2) {
            $scoreSymbol = 2;
            $title = 'Less Harmful';

            // Use quartiles to score to get a more varied set of scores
            if ($totalCo2 < $quartiles['TotalCO2Quartile4']) {
                $score = 5;
            } else if ($totalCo2 < $quartiles['TotalCO2Quartile3']) {
                $score = 4;
            } else if ($totalCo2 < $quartiles['TotalCO2Quartile2']) {
                $score = 3;
            } else {
                $score = 2;
            }
        } else {
            $score = 1;
            $scoreSymbol = 1;
            $title = 'Equally as Harmful';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(true)
            ->setGraphData([
                'title' => null,
                'dataType' => 'number',
                'postFix' => 'tonnes',
                'data' => [
                    ['value' => number_format($minTotalCo2, 2)],
                    ['value' => number_format($maxTotalCo2, 2)],
                    ['value' => number_format($totalCo2, 2)]
                ]
            ])
            ->setAdditionalData([
                ['Results Average', number_format($avgTotalCo2, 2) . ' tonnes'],
                ['This Vehicle', number_format($totalCo2, 2) . ' tonnes'],
                ['Difference', number_format(($totalCo2 - $avgTotalCo2), 2) . ' tonnes']
            ])
            ->setFooterContent('Based on CO2 Emissions Over Period (Tonnes)')
            ->setIsGraphHidden(false);
        $scoreItemSection->addScoreItem($scoreItem);

        if ($locationDistance > $avgLocationDistance) {
            $scoreSymbol = 0;
            $title = 'Less Convenient';
        } else if ($locationDistance < $avgLocationDistance) {
            $scoreSymbol = 2;
            $title = 'More Convenient';

            // Use quartiles to score to get a more varied set of scores
            if ($locationDistance < $quartiles['LocationDistanceQuartile4']) {
                $score = 5;
            } else if ($locationDistance < $quartiles['LocationDistanceQuartile3']) {
                $score = 4;
            } else if ($locationDistance < $quartiles['LocationDistanceQuartile2']) {
                $score = 3;
            } else {
                $score = 2;
            }
        } else {
            $scoreSymbol = 1;
            $title = 'Equally as Convenient';
        }
        $scoreItem = new ScoreItem();
        $scoreItem->setTitle($title)
            ->setScore($score)
            ->setScoreSymbol($scoreSymbol)
            ->setIsScoreContributable(true)
            ->setGraphData([
                'title' => null,
                'dataType' => 'number',
                'postFix' => 'm',
                'data' => [
                    ['value' => $minLocationDistance],
                    ['value' => $maxLocationDistance],
                    ['value' => $locationDistance]
                ]
            ])
            ->setAdditionalData([
                ['Results Average', number_format($avgLocationDistance) . ' miles'],
                ['This Vehicle', number_format($locationDistance) . ' miles'],
                ['Difference', number_format(($locationDistance - $avgLocationDistance)) . ' miles']
            ])
            ->setFooterContent('Based on Distance (Miles)')
            ->setIsGraphHidden(false);
        $scoreItemSection->addScoreItem($scoreItem);

        // Sum score & max score
        $scoreItemsSum = 0;
        $maxScoreValue = 0;
        foreach ($scoreItemSection->getScoreItems() as $scoreItem) {
            if ($scoreItem->getIsScoreContributable() === true) {
                $scoreItemsSum += $scoreItem->getScore();
                $maxScoreValue += 5;
            }
        }

        // Calculate score out of 100
        $scoreItemSectionScore = ($maxScoreValue > 0) ? round(($scoreItemsSum * 100) / $maxScoreValue) : 0;
        $scoreItemSection->setScore((int) $scoreItemSectionScore);
        $scoreItemSection->setIsScoreContributable(true);

        // Sort score items in +, =, - order
        $scoreItems = $scoreItemSection->getScoreItems();
        usort($scoreItems, function ($a, $b) {
            return ($b->getScoreSymbol() < $a->getScoreSymbol()) ? -1 : 1;
        });
        $scoreItemSection->setScoreItems($scoreItems);

        return $scoreItemSection;
    }

    /**
     * Build score item section for nive to haves section
     * 
     * @param array $vehicle
     * @param array $answers
     * 
     * @return ScoreItemSection
     */
    public function yourEssentials(array $vehicle, array $answers): ScoreItemSection
    {
        $scoreItems = [];
        $scoreItemSection = new ScoreItemSection();

        // Set section score weighting
        $scoreItemSection->setScoreWeighting(40);

        $scoreItemSection->setTitle('Compared To Your Selection:');
        $filteredAnswers = $this->sessionAnswerService->getAnswersByQuestionHandle(
            'whatAreYourEessentialsThatYouWantFromACar',
            $answers
        );
        $scorableCount = 0;

        if (count($filteredAnswers) > 0) {

            $answerValues = array_map(
                function ($answer) {
                    return strtoupper($answer);
                },
                $this->getAnswerValuesFromAnswers($filteredAnswers)
            );

            if (!in_array('I DON\'T CARE', $answerValues)) {
                if (in_array('LOW CO2', $answerValues)) {
                    $scoreSymbol = (
                        is_numeric($vehicle['CO2GKM'])
                        && $this->vehicleFormula->calcLowCo2((int) $vehicle['CO2GKM']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('Low CO2')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scoreItemSection->addScoreItem($scoreItem);
                    $scorableCount++;
                }

                if (in_array('5 STAR SAFETY', $answerValues)) {
                    $scoreSymbol = (
                        is_numeric($vehicle['NCAPOverall'])
                        && $this->vehicleFormula->calcSafety((int) $vehicle['NCAPOverall']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('5 Star Safety')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                if (in_array('LOW INSURANCE', $answerValues)) {
                    $scoreSymbol = (
                        isset($vehicle['InsuranceGroup'])
                        && $this->vehicleFormula->calcLowInsurance((int) $vehicle['InsuranceGroup']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('Low Insurance')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                if (in_array('MANUAL GEARBOX', $answerValues)) {
                    $scoreSymbol = (
                        isset($vehicle['Transmission'])
                        && $this->vehicleFormula->calcManualGearbox($vehicle['Transmission']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('Manual Gearbox')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                if (in_array('TOWING CAPACITY', $answerValues)) {
                    $scoreSymbol = (
                        is_numeric($vehicle['TowingWeightBraked'])
                        && $this->vehicleFormula->calcTowing((int) $vehicle['TowingWeightBraked']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('Towing Capacity')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                if (in_array('HIGH MPG', $answerValues)) {
                    $scoreSymbol = (
                        is_numeric($vehicle['CombinedMPG'])
                        && $this->vehicleFormula->calcHighMpg((int) $vehicle['CombinedMPG']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('High MPG')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                if (in_array('LOW TAX', $answerValues)) {
                    $scoreSymbol = (
                        is_numeric($vehicle['CO2GKM'])
                        && $this->vehicleFormula->calcLowTax((int) $vehicle['CO2GKM']) === true
                    ) ? 2 : 0;
                    $scoreItem = new ScoreItem();
                    $scoreItem->setTitle('Low Tax')
                        ->setIsGraphHidden(true)
                        ->setScore($scoreSymbol)
                        ->setScoreSymbol($scoreSymbol)
                        ->setIsScoreContributable(true);
                    $scorableCount++;
                    $scoreItemSection->addScoreItem($scoreItem);
                }

                // Sum score
                $scoreItemsSum = 0;
                foreach ($scoreItemSection->getScoreItems() as $scoreItem) {
                    if ($scoreItem->getIsScoreContributable() === true) {
                        $scoreItemsSum += $scoreItem->getScore();
                    }
                }

                if ($scorableCount > 0) {
                    $scorableCountValue = ($scorableCount * 2);
                    $scoreItemSectionScore = round(($scoreItemsSum * 100) / $scorableCountValue);
                    $scoreItemSection->setIsScoreContributable(true);
                } else {
                    $scoreItemSectionScore = 0;
                    $scoreItemSection->setIsScoreContributable(false);
                }
                $scoreItemSection->setScore((int) $scoreItemSectionScore);

                // Sort score items in +, =, - order
                $scoreItems = $scoreItemSection->getScoreItems();
                usort($scoreItems, function ($a, $b) {
                    return ($b->getScoreSymbol() < $a->getScoreSymbol()) ? -1 : 1;
                });
                $scoreItemSection->setScoreItems($scoreItems);
            } else {
                $scoreItemSection->setIsScoreContributable(false);
            }
        } else {
            $scoreItemSection->setIsScoreContributable(false);
        }

        return $scoreItemSection;
    }

    /**
     * Build scor item section for additional nice to haves section
     * 
     * @param array $vehicle
     * @param array $answers
     * 
     * @return ScoreItemSection
     */
    public function additionalNiceToHaves(array $vehicle, array $answers): ScoreItemSection
    {
        $scoreItemSection = new ScoreItemSection();
        $scoreItemSection->setTitle('Additional Nice To Haves:');

        $depreciationRate = $this->con->fetchAssoc(
            'SELECT karfu_group FROM insurance_price WHERE insurance_group = ?',
            [$vehicle['InsuranceGroup']]
        );
        if (
            $depreciationRate
            && isset($depreciationRate['karfu_group'])
            && $this->vehicleFormula->calcLowDepreciation($depreciationRate['karfu_group']) === true
        )
        {
            $scoreItem = new ScoreItem();
            $scoreItem->setTitle('Low Depreciation')
                ->setIsScoreContributable(false)
                ->setIsGraphHidden(true);
            $scoreItemSection->addScoreItem($scoreItem);
        }

        if (
            is_numeric($vehicle['ZeroTo62'])
            && is_numeric($vehicle['TopSpeed'])
            && is_numeric($vehicle['MinimumKerbweight'])
        )
        {
            $zeroTo62Score = $this->vehicleFormula->calc0To62Score((int) $vehicle['ZeroTo62']);
            $topSpeedScore = $this->vehicleFormula->calcTopSpeedScore((int) $vehicle['TopSpeed']);
            $powerToWeightScore = $this->vehicleFormula->calcPowerToWeightScore((float) $vehicle['MinimumKerbweight']);
            $totalPerformanceScore = $this->vehicleFormula->calcTotalPerformanceScore($powerToWeightScore, $topSpeedScore, $zeroTo62Score);
            if ($this->vehicleFormula->calcHighPerformance($totalPerformanceScore) === true) {
                $scoreItem = new ScoreItem();
                $scoreItem->setTitle('High Performance')
                    ->setIsScoreContributable(false)
                    ->setIsGraphHidden(true);
                $scoreItemSection->addScoreItem($scoreItem);
            }
        }

        // This section is not to be scored
        $scoreItemSection->setScore(0);
        $scoreItemSection->setIsScoreContributable(false);

        return $scoreItemSection;
    }

    /**
     * Find answer value in answers
     * 
     * @param mixed $value
     * @param array $answers
     * 
     * @return bool
     */
    private function isValueInAnswers($value, array $answers): bool
    {
        if (count($answers) > 0) {
            if (is_string($value) === true) {
                $value = strtoupper($value);
            }

            foreach ($answers as $answer) {
                $option = $answer->getOption();

                if ($option) {
                    $answerValue = $option->getOptionTitle();

                    if (strtoupper($answerValue) === $value) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get all answer values from answers
     * 
     * @param array $answers
     * 
     * @return array
     */
    public function getAnswerValuesFromAnswers(array $answers): array
    {
        $returnAnswers = [];
        foreach ($answers as $answer) {
            $option = $answer->getOption();

            if ($option) {
                $returnAnswers[] = $option->getOptionTitle();
            }
        }
        return $returnAnswers;
    }

    /**
     * Check if the current search type is 'Quick'
     * 
     * @return bool
     */
    private function isQuickSearch(): bool
    {
        return (isset($_SESSION['KARFU_user']['currentJourneyType']) && $_SESSION['KARFU_user']['currentJourneyType'] === 'Quick');
    }
}
