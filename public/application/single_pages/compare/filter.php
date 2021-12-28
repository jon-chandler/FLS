<?php
defined('C5_EXECUTE') or die("Access Denied.");
use Application\Service\SessionAnswerService;

$v = View::getInstance();
$c = Page::getCurrentPage();
    
$budgetOpts = $this->controller->getBudgetBounds();
$monthlyBudgetOpts =  $this->controller->getMonhtlyBudgetBounds();
$vehicleTypes = $this->controller->getVehicleOptions();
$vehicleBrandList =  $this->controller->getVehicleTypes(); // temp - this needs to sit in the above call
$brandList = $this->controller->getBrandList($vehicleBrandList);
$mobOptions = $this->controller->getMobilityOptions();
$emissionsBounds = $this->controller->getEmissionsBounds();
$enviroImpactBounds = $this->controller->getEnviroImpactBounds();
$distanceOpts = $this->controller->getDistanceBounds();
$mileageOpts = $this->controller->getMileageBounds();
$vehicleAgeCats = $this->controller->getVehicleAgeCats();
$vehicleYears = $this->controller->getVehicleYearsRange();
$numSeats = $this->controller->getVehicleSeatCount();
$fuelTypes = $this->controller->getFuelTypes();
$essentials = $this->controller->getEssentials();

$queryStrs = $_GET;
$filteredSeatNum = $queryStrs['no-of-seats'] ? $queryStrs['no-of-seats'] : $numSeats['min'];

unset(
    $queryStrs[$c->getCollectionPath()],
    $queryStrs['page'],
    $queryStrs['mobility-options'],
    $queryStrs['min-val'],
    $queryStrs['max-val'],
    $queryStrs['min-month-val'],
    $queryStrs['max-month-val'],
    $queryStrs['min-emissions-val'], 
    $queryStrs['max-emissions-val'],
    $queryStrs['min-env-val'],
    $queryStrs['max-env-val'],
    $queryStrs['min-mileage'],
    $queryStrs['max-mileage'],
    $queryStrs['min-distance'],
    $queryStrs['max-distance'],
    $queryStrs['no-of-seats'],
    $queryStrs['min-vehicle-age-val'],
    $queryStrs['max-vehicle-age-val'],
    $queryStrs['vehicle-essentials']
);

$qsExclusions = ['excluded-brands', 'vehicle-types', 'mobility-sub-options', 'vehicle-sub-types', 'vehicle-age', 'fuel-types', 'vehicle-years'];
?>
<div class="full-width">
   <section class="filter-page">
        <?php
            // fugly as. 
            if (
                $_SESSION['KARFU_user']['completed'][2] == 3
                || $_SESSION['KARFU_user']['currentJourneyType'] === 'Quick'
            ) : 
        ?>
            <?php $this->inc('components/filter_header.php'); ?>

            <div class="filter-container">
            <div class="header">
                <h2>Results filter</h2>
            </div>

            <div class="filter-content">
                <form action="/compare/final_results" method="GET">
                   
                    <?php foreach ($queryStrs as $key => $queryStr) { 
                            if (in_array($key, $qsExclusions)) {
                                continue;
                            }
                            echo '<input type="hidden" name="'. htmlspecialchars($key) . '" value="' . htmlspecialchars($queryStr) . '" />';
                        }
                    ?>

                     <div class="accordion open filter-accordion">
                        <div class="header">Usage</div>
                            <div class="content-list no-padding">
                                <?php 
                                    foreach ($mobOptions as $key => $mobOpt) {
                                        echo "<div class='filter-cats'>
                                                <div class='filter-header'>
                                                    <input id='mobility-option-{$key}' type='checkbox' class='cb-choice cb-choice-mobility' name='mobility-options[]' value='{$key}' checked>
                                                    <label for='mobility-option-{$key}' class='checkbox'>{$key}</label>
                                                </div>
                                                    <div class='filter-opt-types flex-check-list'>";

                                                    foreach ($mobOpt as $sub => $opt) {
                                                        $id = strtolower(str_replace(' ', '', $opt));
                                                        $id = "{$id}-{$key}";
                                                        echo "<div class='option'>
                                                            <input id='mobility-option-{$id}' type='checkbox' class='cb-choice cb-choice-mobility-sub' name='mobility-sub-options[]' value='{$opt}' checked>
                                                            <label for='mobility-option-{$id}' class='checkbox'>{$opt}</label>
                                                        </div>"; 
                                                    }

                                        echo "</div>
                                        </div>";
                                    }
                                ?>
                        </div>
                    </div>


                    <div class="accordion open filter-accordion">
                        <div class="header">Budget</div>
                        <div class="content-list">
                            <h3>Total cost of use</h3>
                            <div class="budget-range budget-range__tcu currency">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'tcu-budget', 'minName' => 'min-val', 'maxName' => 'max-val',  'minValBottom' => $budgetOpts['minVal'], 'maxValBottom' => $budgetOpts['maxVal'], 'minValTop' => $budgetOpts['minVal'], 'maxValTop' => $budgetOpts['maxVal'], 'step' => $budgetOpts['step'], 'type' => 'currency']); ?>       
                            </div>
                            <h3>Monthly budget</h3>
                            <div class="budget-range budget-range__monthly currency">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'monthly-budget', 'minName' => 'min-month-val', 'maxName' => 'max-month-val',  'minValBottom' => $monthlyBudgetOpts['minVal'], 'maxValBottom' => $monthlyBudgetOpts['maxVal'], 'minValTop' => $monthlyBudgetOpts['minVal'], 'maxValTop' => $monthlyBudgetOpts['maxVal'], 'step' => $monthlyBudgetOpts['step'], 'type' => 'currency']); ?>       
                            </div>
                        </div>
                    </div>

                    <div class="accordion open filter-accordion">
                        <div class="header">Environmental impact</div>
                        <div class="content-list">
                            <h3>Emissions (CO2 G/KM)</h3>
                            <div class="budget-range emissions-range">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'emissions-impact', 'minName' => 'min-emissions-val', 'maxName' => 'max-emissions-val',  'minValBottom' => $emissionsBounds['MinCO2GKM'], 'maxValBottom' => $emissionsBounds['MaxCO2GKM'], 'minValTop' => $emissionsBounds['MinCO2GKM'], 'maxValTop' => $emissionsBounds['MaxCO2GKM'], 'step' => 1, 'type' => 'int']); ?>
                            </div>
                            <h3>Impact (Trees destroyed)</h3>
                            <div class="budget-range env-range">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'env-impact', 'minName' => 'min-env-val', 'maxName' => 'max-env-val',  'minValBottom' => $enviroImpactBounds['MinEnviroImpact'], 'maxValBottom' => $enviroImpactBounds['MaxEnviroImpact'], 'minValTop' => $enviroImpactBounds['MinEnviroImpact'], 'maxValTop' => $enviroImpactBounds['MaxEnviroImpact'], 'step' => 1, 'type' => 'int']); ?>
                            </div>
                        </div>
                    </div>


                     <div class="accordion open filter-accordion">
                        <div class="header">Vehicle</div>
                            <div class="content-list no-padding">
                                <?php 
                                    foreach ($vehicleTypes as $key => $vehicleOpt) {
                                        echo "<div class='filter-cats'>
                                                <div class='filter-header'>
                                                    <input id='vehicle-option-{$key}' type='checkbox' class='cb-choice cb-choice-vehicle' name='vehicle-types[]' value='{$key}' checked>
                                                    <label for='vehicle-option-{$key}' class='checkbox'>{$key}</label>
                                                </div>
                                                    <div class='filter-opt-types flex-check-list'>";
                                                     foreach ($vehicleOpt['option'] as $sub => $opt) {
                                                        $id = strtolower(str_replace(' ', '', $opt));
                                                        $id = "{$id}-{$key}";
                                                        echo "<div class='option'>
                                                            <input id='vehicle-option-{$id}' type='checkbox' class='cb-choice cb-choice-vehicle-sub' name='vehicle-sub-types[]' value='{$opt}' checked>
                                                            <label for='vehicle-option-{$id}' class='checkbox'>{$opt}</label>
                                                        </div>"; 
                                                    }

                                        echo "</div>
                                        </div>";
                                    }
                                ?>
                            <div class="nested-wrapper">
                                <h3>Fuel types</h3>
                                <div class="cb-options flex-check-list">
                                <?php 
                                    foreach ($fuelTypes as $fuel) {
                                        echo <<< FUEL
                                        <div class='option'>
                                                <input id='$fuel' type='checkbox' class='cb-choice cb-fuel-types' name='fuel-types[]' value='$fuel' checked>
                                                <label for='$fuel' class='checkbox'>$fuel</label>
                                        </div>
FUEL;
                                    }
                                ?>
                                </div>
                            </div>

                        </div>
                    </div>


                    <div class="accordion filter-accordion">
                        <div class="header">Brands</div>
                        <div class="content-list">
                            <div class="brandlist"> 
                            <?php 
                                foreach ($brandList as $i => $brand) {
                                    $logo = File::getByID($brand['partner_logo']);
                                    if(is_object($logo)) {
                                        $partner_logo = $logo->getRelativePath();
                                    } else {
                                        $partner_logo = $view->getThemePath() . '/images/icons/environment.svg';
                                    }

                                    $brandName = $brand['partner'];
                                    $checked = $active = '';

                                    if($queryStrs['excluded-brands']) {
                                       if(in_array($brandName, $queryStrs['excluded-brands'])) {
                                            $checked = 'checked';
                                            $active = 'active';
                                        } 
                                    }
                                    
                                    echo <<<CHECKBOX
                                             <div class="brand-option $checked" title="$brandName">
                                                <label for="Option$optionID" class="$disabled">
                                                    <img src="$partner_logo" class="brand-logo $active" />
                                                    <input class="filter-option filter-option-cb" data-option="$brandName" type="checkbox" $checked name="excluded-brands[]" value="$brandName" title="$brandName">
                                                    <span class="$active">$brandName</span>
                                                </label>
                                            </div>    
CHECKBOX;
                                }
                            ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Status</div>
                        <div class="content-list">
                            <h3>Vehicle status</h3>
                            <div class="cb-options flex-check-list">
                                <?php 
                                    foreach ($vehicleAgeCats as $age) {
                                        echo <<< AGE
                                        <div class='option'>
                                                <input id='$age' type='checkbox' class='cb-choice cb-vehicle-age' name='vehicle-age[]' value='$age' checked>
                                                <label for='$age' class='checkbox'>$age</label>
                                        </div>
AGE;
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Essentials</div>
                        <div class="content-list">
                            <h3>Vehicle essentials</h3>
                            <div class="cb-options flex-check-list">
                                <?php 
                                    foreach ($essentials as $essential) {
                                        echo <<< ESSENTIAL
                                        <div class='option'>
                                                <input id='$essential' type='checkbox' class='cb-choice cb-vehicle-essentials' name='vehicle-essentials[]' value='$essential'>
                                                <label for='$essential' class='checkbox'>$essential</label>
                                        </div>
ESSENTIAL;
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Age</div>
                        <div class="content-list">
                            <h3>Vehicle age (YEARS)</h3>
                            <div class="budget-range vehicle-age-range">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'vehicle-years', 'minName' => 'min-vehicle-age-val', 'maxName' => 'max-vehicle-age-val',  'minValBottom' => $vehicleYears['min'], 'maxValBottom' => $vehicleYears['max'], 'minValTop' => $vehicleYears['min'], 'maxValTop' => $vehicleYears['max'], 'step' => 1, 'type' => 'int']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Mileage</div>
                        <div class="content-list">
                            <h3>Mileage</h3>
                            <div class="budget-range mileage-range int">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'vehicle-mileage', 'minName' => 'min-mileage', 'maxName' => 'max-mileage',  'minValBottom' => $mileageOpts['minMiles'], 'maxValBottom' => $mileageOpts['maxMiles'], 'minValTop' => $mileageOpts['minMiles'], 'maxValTop' => $mileageOpts['maxMiles'], 'step' => $mileageOpts['step'], 'type' => 'int']); ?>       
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Seats</div>
                        <div class="content-list">
                            <h3>Minimum number of seats</h3>
                            <div class="filter-range int">
                                <?php $this->inc('components/journey/filter_range_single.php', ['rangeName' => 'no-of-seats', 'val' => $filteredSeatNum, 'maxVal' => $numSeats['max'] ]); ?>       
                            </div>
                        </div>
                    </div>

                    <div class="accordion filter-accordion">
                        <div class="header">Distance to vehicle</div>
                        <div class="content-list">
                            <h3>Distance prepared to travel (MILES)</h3>
                            <div class="budget-range distance-range int">
                                <?php $this->inc('components/journey/double_range.php', ['rangeName' => 'distance-to-vehicle', 'minName' => 'min-distance', 'maxName' => 'max-distance',  'minValBottom' => $distanceOpts['minDistance'], 'maxValBottom' => $distanceOpts['maxDistance'], 'minValTop' => $distanceOpts['minDistance'], 'maxValTop' => $distanceOpts['maxDistance'], 'step' => $distanceOpts['step'], 'type' => 'int']); ?>       
                            </div>
                        </div>
                    </div>

                    <div class="button-wrapper">
                        <button type="submit" class="button-dark-green data-call-btn max-button-width"><span>Filter</span><div class="button-loader"></div></button>
                    </div>
                </form>
                <div class="button-wrapper">
                    <a href="/compare/filter" title="Reset"><button class="reset button-reset">Reset</button></a>
                </div>
            </div>
        </div>

        <?php else : ?>
        <div class="start-journey-prompt">
            <p>Please complete at least one Karfu search, in order to enable these filters.</p>
            <p>Thank you.</p>
            <a href="/compare/full" title="Start your Karfu search"><button class="button-dark-green data-call-btn max-button-width"><span>Start your Karfu search</span><div class="button-loader"></div></button></a>
        </div>
        <?php endif; ?>

    </section>
</div>