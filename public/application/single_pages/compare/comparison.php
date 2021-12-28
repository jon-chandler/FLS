<?php

    defined('C5_EXECUTE') or die("Access Denied.");
    $v = View::getInstance();

    $dealerFinder = new Application\Helper\DealerInfo;
    
    $dealerLeft = $dealerFinder->getRetailerDetails($left['vehicle']['ManName'], $left['vehicle']['RangeName'], $left['vehicle']['FuelType'], $left['vehicle']['Derivative']);
    $retailerLeft = $dealerLeft[0];

    $dealerRight = $dealerFinder->getRetailerDetails($right['vehicle']['ManName'], $right['vehicle']['RangeName'], $right['vehicle']['FuelType'], $right['vehicle']['Derivative']);
    $retailerRight = $dealerRight[0];

?>
<div class="full-width">
    <?php if ($left && $right) : ?>
    <?php
    $lVehicle = $left['vehicle'];
    $rVehicle = $right['vehicle'];
    $v->inc('components/comparison_header.php', [
        'lVehicleList' => $left['vehicleList'],
        'rVehicleList' => $right['vehicleList'],
        'lIdx' => $left['idx'],
        'rIdx' => $right['idx'],
        'vehicleCount' => $vehicleCount,
        'btnClass' => 'in-header'
    ]);
    $lClasses = ($left['isCurrentVehicle']) ? 'current-vehicle' : 'list-vehicle';
    $rClasses = ($right['isCurrentVehicle']) ? 'current-vehicle' : 'list-vehicle';
    $lCount = 0;
    $rlCount = 0;
    ?>
    <section class="vehicle-comparison">
        <div class="column <?php echo $lClasses; ?>">
            <div class="vehicle-header">
                <a <?php if (!$left['isCurrentVehicle']) echo 'href="' . $left['url'] . '"'; ?>><div class="vehicle-image <?php echo $left['imgClass']; ?>" style="background-image: url('<?php echo $left['imgPath']; ?>')" alt="<?php echo $lVehicle['ManName'] . ' ' . $lVehicle['ModelName']; ?>" title="<?php echo $lVehicle['ManName'] . ' ' . $lVehicle['ModelName']; ?>"></div></a>
                <h3><?php echo $left['vehicle']['ManName'] . ' ' . $left['vehicle']['RangeName']; ?></h3>
                <h4><?php echo $left['vehicle']['Trim']; ?></h4>
            </div>
            <?php foreach ($left['vehicleAttributes'] as $key => $vehicleAttributes) { ?>
                <div class="accordion <?php if($lCount == 0) { ?> open <?php } ?> accordion_<?php echo $key; ?> accordion_comparison" data-partner="<?php echo $key; ?>">
                    <div class="header"><?php echo $vehicleAttributes['category']; ?></div>
                    <div class="content-list">
                        <ul class="comp-list">
                            <?php foreach ($vehicleAttributes['attributes'] as $attributes) : ?>
                                <?php
                                $class = ($attributes['isSubHeader']) ? 'is-header' : 'is-content';
                                $val = ($key == 'highLevelOverview') ? $this->controller->responseMarkup($attributes['value']) : $attributes['value'];
                                echo '<li class="' . $class . '">' . $attributes['title'] . ': <span>' . $val . '</span></li>';
                                ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php 
                $lCount ++;
            } ?>

            <?php if($retailerLeft): ?>
                <button 
                    type="button"
                    data-dealer="<?php echo $retailerLeft['dealer_name']; ?>" 
                    data-website="<?php echo $retailerLeft['dealer_link']; ?>" 
                    data-phone="<?php echo $retailerLeft['dealer_number']; ?>"
                    data-address="<?php echo $retailerLeft['dealer_address']; ?>" 
                    data-more-info="<?php echo $retailerLeft['link']; ?>" 
                    value="Enquire" 
                    class="button-dark-green seller-detail-btn max-button-width">
                    <span>Enquire</span>
                </button>
            <?php elseif ($vehicle['VehicleMerchantLink']): ?>
                <button 
                    data-manufacturer="<?php echo $left['vehicle']['ManName']; ?>" 
                    data-dealer="<?php echo $left['vehicle']['VehicleMerchantLink']; ?>" 
                    data-product="<?php echo $left['vehicle']['VehicleProductLink']; ?>" 
                    data-range="<?php echo $left['vehicle']['RangeName']; ?>" 
                    data-trim="<?php echo $left['vehicle']['Trim']; ?>" 
                    class="button-dark-green btn-white-text alternative-seller-detail-btn max-button-width" 
                    title="<?php echo t('ENQUIRE'); ?>">
                    <span>Enquire</span>
                </button>
            <?php endif; ?>
        </div>

        <div class="column <?php echo $rClasses; ?>">
            <div class="vehicle-header">
                <a <?php if (!$right['isCurrentVehicle']) echo 'href="' . $right['url'] . '"'; ?>><div class="vehicle-image <?php echo $right['imgClass']; ?>" style="background-image: url('<?php echo $right['imgPath']; ?>')" alt="<?php echo $rVehicle['ManName'] . ' ' . $rVehicle['ModelName']; ?>" title="<?php echo $rVehicle['ManName'] . ' ' . $rVehicle['ModelName']; ?>"></div></a>
                <h3><?php echo $right['vehicle']['ManName'] . ' ' . $right['vehicle']['RangeName']; ?></h3>
                <h4><?php echo $right['vehicle']['Trim']; ?></h4>
            </div>
            <?php foreach ($right['vehicleAttributes'] as $key => $vehicleAttributes) { ?>
                <div class="accordion <?php if($rCount == 0) { ?> open <?php } ?> accordion_<?php echo $key; ?> accordion_comparison" data-partner="<?php echo $key; ?>">
                    <div class="header"><?php echo $vehicleAttributes['category']; ?></div>
                    <div class="content-list">
                        <ul class="comp-list">
                            <?php foreach ($vehicleAttributes['attributes'] as $attributes) : ?>
                                <?php
                                $class = ($attributes['isSubHeader']) ? 'is-header' : 'is-content';
                                $val = ($key == 'highLevelOverview') ? $this->controller->responseMarkup($attributes['value']) : $attributes['value'];
                                echo '<li class="' . $class . '">' . $attributes['title'] . ': <span>' . $val . '</span></li>';
                                ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php 
                $rCount ++;
            } ?>
            <?php if($retailerRight): ?>
                <button 
                    type="button"
                    data-dealer="<?php echo $retailerRight['dealer_name']; ?>" 
                    data-website="<?php echo $retailerRight['dealer_link']; ?>" 
                    data-phone="<?php echo $retailerRight['dealer_number']; ?>"
                    data-address="<?php echo $retailerRight['dealer_address']; ?>" 
                    data-more-info="<?php echo $retailerRight['link']; ?>" 
                    value="Enquire" 
                    class="button-dark-green seller-detail-btn max-button-width">
                    <span>Enquire</span>
                </button>
            <?php elseif ($vehicle['VehicleMerchantLink']): ?>
                <button 
                    data-manufacturer="<?php echo $right['vehicle']['ManName']; ?>" 
                    data-dealer="<?php echo $right['vehicle']['VehicleMerchantLink']; ?>" 
                    data-product="<?php echo $right['vehicle']['VehicleProductLink']; ?>" 
                    data-range="<?php echo $right['vehicle']['RangeName']; ?>" 
                    data-trim="<?php echo $right['vehicle']['Trim']; ?>" 
                    class="button-dark-green btn-white-text alternative-seller-detail-btn max-button-width" 
                    title="<?php echo t('ENQUIRE'); ?>">
                    <span>Enquire</span>
                </button>
            <?php endif; ?>
        </div>
    </section>

    <div class="comparison-options">
        <button class="print-pdf-button" title="<?php echo t('Export'); ?>"><span><?php echo t('Export'); ?></span></button>
    </div>

    <?php else : ?>
        <div class="no-results">
            <h1>NOTHING TO COMPARE</h1>
        </div>
    <?php endif; ?>

</div>


