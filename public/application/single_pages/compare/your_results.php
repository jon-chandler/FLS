<?php
defined('C5_EXECUTE') or die("Access Denied.");
$v = View::getInstance();
$resultsPerRow = 10;
?>
<div class="full-width">
   <section class="context fetch-dynamic-score">
   	<?php
		$this->inc('components/filter_sort.php', ['totalResults' => $vehicleCount, 'hideFilters' => true]);
		$this->inc('components/mobility_toggle.php', [
			'pageType' => 'your_results',
			'labelA' => 'View by mobility',
			'labelB' => 'View by preferences',
			'pageA' => '/compare/your_results?view=mobility',
			'pageB' => '/compare/your_results?view=preference',
			'pageAClass' => 'toggleA',
			'pageBClass' => 'toggleB']
		);

		$subCount = 0;

		// remove. quick win needed to ensure we display 0 results when we still have categories in the result set, but they have 0 options
		if(count($vehicles)) {
			foreach ($vehicles as $groupedVehicle) {
				foreach ($groupedVehicle['vehicles'] as $vehicle) {
					$subCount ++;
				}
			}
		}

		if ($subCount) {

			foreach ($vehicles as $groupedVehicle) {
				$groupTitle = $groupedVehicle['groupTitle'];
				$groupUrl = $groupedVehicle['groupUrl'];
				$count = $this->controller->getCountForOption($groupTitle);
				$display = ($count != 1) ? $count . ' OPTIONS' : $count . ' OPTION';
				$displayCount = ($vehicleCount === $count) ? 'VIEW OPTIONS' : $display; 
				$headerClass = ($count > 0) ? 'has-options' : 'no-options';

				echo '<div class="context-header ' . $headerClass . '"><div class="filter-link"><a href="'. $groupUrl .'">' . $groupTitle . '</a></div>';
				echo '<div class="result-count"><a href="' . $groupUrl .'">' . $displayCount . '</a></div>';
				echo '</div>';
				echo '<div class="slider-wrapper ' . $headerClass . '">';

				$i = 0;
				foreach ($groupedVehicle['vehicles'] as $vehicle) {

					$i++;

					$detail = [
						'id' => $vehicle['ID'],
						'capID' => $vehicle['CapID'],
						'model' => $vehicle['ModelName'],
						'manName' => $vehicle['ManName'],
						'rangeName' => $vehicle['RangeName'],
						'fuelType' => $vehicle['FuelType'],
						'derivative' => $vehicle['Derivative'],
						'vehicleType' => $vehicle['VehicleType'],
						'imageLink' => $vehicle['ImageLink'],
						'mobilityChoice' => $vehicle['MobilityChoice'],
						'mobilityType' => $vehicle['mobilityChoice']['type'],
						'mobilityTypeHr' => $vehicle['mobilityChoice']['typeHr'],
						'totalCost' => $vehicle['TotalCost'],
						'totalMonthlyCost' => $vehicle['TotalMonthlyCost'],
						'enviroImpact' => $vehicle['EnviroImpact'],
						'priceNew' => $vehicle['priceNew'],
						'hideEmissions' => true,
						'viewType' => $viewType,
						'distance' => floor($vehicle['LocationDistance']),
						'co2' => $vehicle['CO2GKM'],
						'estMileage' => $estMileage,
						'suitabilityScore' => $vehicle['SuitabilityScore']
					];

					if ($i == $resultsPerRow) {
						$v->inc('components/see_more.php', ['url' => $groupUrl]);	
					} else {
						$v->inc('components/vehicle_result_list_item_scrollable.php', $detail);	
					}
				}
				echo '</div>';
			}
			echo '<a href="/compare/final_results?sort=' . urlencode($defaultSort) . '" class="show-loader-content"><button class="button-dark-green data-call-btn max-button-width show-loader-content"><span>View all '. $vehicleCount .' results</span><div class="button-loader"></div></button></a>';
			echo '<a href="/compare/final_summary?skipProcessResults=1" class="show-loader-content"><button class="button-green-pale data-call-btn max-button-width show-loader-content"><span>Change previous answers</span><div class="button-loader"></div></button></a>';
		} else {
			$link = '/compare/final_summary';
			if (
				isset($_SESSION['KARFU_user']['currentJourneyType'])
				&& $_SESSION['KARFU_user']['currentJourneyType'] === 'Quick'
			) {
				$link = '/compare/quick-search';
			}
			$v->inc('components/no_journey_results.php', ['link' => $link]);
		}
	?>
   	</section>
</div>