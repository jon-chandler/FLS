<?php

declare(strict_types=1);

namespace Application\Helper;

/**
 * Calculate salary
 */
class SalaryCalculator
{
	/**
	 * Calculate salary from gross income & return breakdown
	 * 
	 * @param float $grossIncome
	 * 
	 * @return array
	 */
	public function calculate(float $grossIncome): array
	{
		if ($grossIncome > 125000) {
			$personalAllowance = 0;
		} elseif ($grossIncome > 100000) {
			$personalAllowance = (12500 - 100000) / 2;
		} elseif ($grossIncome > 12500) {
			$personalAllowance = 12500;
		} else {
			$personalAllowance = $grossIncome;
		}

		$taxableIncome = $grossIncome - $personalAllowance;

		if ($grossIncome > 50000) {
			$taxOn20 = ((50000 - 12500) / 100) * 20;
		} elseif ($grossIncome > 12500) {
			$taxOn20 = (($grossIncome - 12500) / 100) * 20;
		} else {
			$taxOn20 = 0;
		}

		if ($grossIncome > 150000) {
			$taxOn40 = ((150000 - 50000) / 100) * 40;
		} elseif ($grossIncome > 50000) {
			$taxOn40 = (($grossIncome - 50000 - $personalAllowance + 12500) / 100) * 40;
		} else {
			$taxOn40 = 0;
		}

		if ($grossIncome > 150000) {
			$taxOn45 = (($grossIncome - 150000) / 100) * 45;
		} else {
			$taxOn45 = 0;
		}

		$incomeTaxDue = $taxOn20 + $taxOn40 + $taxOn45;
		$niContribution = 0;

		if ($grossIncome <= 8632) {
			$niContribution = 0;
		} elseif ($grossIncome <= 50024) {
			$niContribution = (($grossIncome - 8632) / 100) * 12;
		} else {
			$niContribution = (((50024 - 8632) / 100) * 12) + (($grossIncome - 50024) / 100) * 2;
		}

		$netIncome = $grossIncome - $incomeTaxDue - $niContribution;

		$monthlyGrossIncome = $grossIncome / 12;
		$monthlyPersonalAllowance = $personalAllowance / 12;
		$monthlyTaxableIncome = $monthlyGrossIncome - $monthlyPersonalAllowance;
		$monthlyTaxOn20 = $taxOn20 / 12;
		$monthlyTaxOn40 = $taxOn40 / 12;
		$monthlyTaxOn45 = $taxOn45 / 12;
		$monthlyIncomeTaxDue = $monthlyTaxOn20 + $monthlyTaxOn40 + $monthlyTaxOn45;
		$monthlyNetIncome = $netIncome / 12;
		$monthlyNiContribution = $niContribution / 12;

		return [
			'yearly' => [
				'grossIncome' => $grossIncome,
				'personalAllowance' => $personalAllowance,
				'taxableIncome' => $taxableIncome,
				'taxOn20' => $taxOn20,
				'taxOn40' => $taxOn40,
				'taxOn45' => $taxOn45,
				'incomeTaxDue' => $incomeTaxDue,
				'niContribution' => $niContribution,
				'netIncome' => $netIncome
			],
			'monthly' => [
				'grossIncome' => $monthlyGrossIncome,
				'personalAllowance' => $monthlyPersonalAllowance,
				'taxableIncome' => $monthlyTaxableIncome,
				'taxOn20' => $monthlyTaxOn20,
				'taxOn40' => $monthlyTaxOn40,
				'taxOn45' => $monthlyTaxOn45,
				'incomeTaxDue' => $monthlyIncomeTaxDue,
				'niContribution' => $monthlyNiContribution,
				'netIncome' => $monthlyNetIncome
			]
		];
	}
}
