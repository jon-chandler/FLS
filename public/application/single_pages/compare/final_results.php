<?php
defined('C5_EXECUTE') or die("Access Denied.");
$c = Page::getCurrentPage();

$pLink = '';
if ($_SESSION['KARFU_user']['previousPage']) {
    $page = explode('?', $_SESSION['KARFU_user']['previousPage'])[0];
    if ($page === '/compare/filter') {
        $pLink = $_SESSION['KARFU_user']['previousPage'];
    }
}

// Hit the CapHpi image generator?
$generateImages = true;
?>
<div class="full-width">
    <?php $this->inc('components/back_button.php'); ?>
    <section class="final-results <?php echo $contentClass; ?>">
        <?php
        if ($vehicleCount) {
            $this->inc('components/vehicle_result_list.php', [
                'vehicles' => $vehicles,
                'vehicleCount' => $vehicleCount,
                'scrapedVehicleContentService' => $scrapedVehicleContentService,
                'generateImages' => $generateImages,
                'pagination' => $pagination
            ]);
        } else {
            $filterUrl = '/compare/filter';
            $queryStr = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if ($queryStr) {
                $filterUrl .= '?' . $queryStr;
            }
            $this->inc('components/no_journey_results.php', [
                'contentClass' => 'in-journey',
                'header' => '',
                'bodyText' => '',
                'linkText' => '',
                'link' => $filterUrl
            ]);
        }
        ?>
    </section>
</div>