<?php
defined('C5_EXECUTE') or die("Access Denied.");
$v = View::getInstance();
?>
<div class="full-width">
	<section class="journey journey-summary final-summary">
		<div class="summary-header-main">
			<h1>FINAL SUMMARY</h1>
			<p>Your key answers</p>
		</div>
		<?php 
		foreach ($summaries as $summary) {
			if (count($summary['summaries']) > 0) {
				$title = $summary['journey']->getJourneyTitle();
				$journeyType = (string) $summary['journey']->getJourneyType();
				if ($journeyType === 'Standard') {
					$link = '/compare/'
						. str_replace(' ', '-', strtolower(trim(($summary['journey']->getJourneyGroup()))))
						. '/' . str_replace(' ', '-', strtolower(trim(($title)))) . '-journey';
				} else {
					$link = '/compare/quick-search';
				}
				
				echo 
					"<div class='summary-container'>
						<div class='header'>" . $title . "</div>
						<div class='summary-breakdown'>
					";
				
					foreach ($summary['summaries'] as $item) {
						$this->inc('components/journey/answer.php', ['summary' => $item, 'urlPrefix' => $link]);
					}	
						
				echo '</div>
					<div class="button-wrapper">
						<a href="' . $link . '"><button type="submit" class="button-dark-green data-call-btn max-button-width"><span>Edit ' . $title . ' answers</span><div class="button-loader"></div></button></a>
					</div>
				</div>';
			}
		}
		?>
	</section>
	<?php if ($isJourneyComplete): ?>
	<div class="see-results-container">
		<div class="button-wrapper">
		<?php if ($processResults): ?>
			<button type="submit" class="button-dark-green data-call-btn max-button-width show-loader-content process-results"><span>Your results</span><div class="button-loader"></div></button>
		<?php else: ?>
			<a href="/compare/your_results" class="show-loader-content"><button type="submit" class="button-dark-green data-call-btn max-button-width show-loader-content"><span>Your results</span><div class="button-loader"></div></button></a>
		<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</div>   