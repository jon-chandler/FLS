<?php
use Concrete\Core\Attribute\Key\Key;
use Concrete\Core\Http\ResponseAssetGroup;

defined('C5_EXECUTE') or die('Access denied.');

$r = ResponseAssetGroup::get();
$r->requireAsset('javascript', 'jquery');
$r->requireAsset('javascript', 'underscore');
$r->requireAsset('javascript', 'core/events');

$form = Loader::helper('form');

if (isset($authType) && $authType) {
    $active = $authType;
    $activeAuths = array($authType);
} else {
    $active = null;
    $activeAuths = AuthenticationType::getList(true, true);
}
if (!isset($authTypeElement)) {
    $authTypeElement = null;
}
if (!isset($authTypeParams)) {
    $authTypeParams = null;
}
$image = date('Ymd') . '.jpg';

/* @var Key[] $required_attributes */

$attribute_mode = (isset($required_attributes) && count($required_attributes));
?>

<div class="login-page">
    <h1><?= !$attribute_mode ? t('Sign In.') : t('Required Attributes') ?></h1>

    <?php
    $disclaimer = new Area('Disclaimer');
    if ($disclaimer->getTotalBlocksInArea($c) || $c->isEditMode()) { ?>
        <div class="ccm-login-disclaimer">
            <?=$disclaimer->display($c);?>
        </div>
    <?php } ?>
    <div class="col-sm-6 col-sm-offset-3 login-form">
        <div class="row">
            <div class="visible-xs ccm-authentication-type-select form-group text-center">
                <?php
                if ($attribute_mode) {
                    ?>
                    <i class="fa fa-question"></i>
                    <span><?= t('Attributes') ?></span>
                <?php

                } elseif (count($activeAuths) > 1) {
                    ?>
                    <select class="form-control col-xs-12">
                        <?php
                        foreach ($activeAuths as $auth) {
                            ?>
                            <option value="<?= $auth->getAuthenticationTypeHandle() ?>">
                                <?= $auth->getAuthenticationTypeDisplayName() ?>
                            </option>
                        <?php

                        }
                    ?>
                    </select>

                    <?php

                }
                ?>
                <label>&nbsp;</label>
            </div>
        </div>
        <div class="row login-row">
            <div <?php if (count($activeAuths) < 2) {
    ?>style="display: none" <?php 
} ?> class="types col-sm-4 hidden-xs">
                <ul class="auth-types">
                    <?php
                    if ($attribute_mode) {
                        ?>
                        <li data-handle="required_attributes">
                            <i class="fa fa-question"></i>
                            <span><?= t('Attributes') ?></span>
                        </li>
                        <?php

                    } else {
                        /** @var AuthenticationType[] $activeAuths */
                        foreach ($activeAuths as $auth) {
                            ?>
                            <li data-handle="<?= $auth->getAuthenticationTypeHandle() ?>">
                                <?= $auth->getAuthenticationTypeIconHTML() ?>
                                <span><?= $auth->getAuthenticationTypeDisplayName() ?></span>
                            </li>
                        <?php

                        }
                    }
                    ?>
                </ul>
            </div>
            <div class="controls <?php if (count($activeAuths) < 2) {
    ?>col-sm-12<?php 
} else {
    ?>col-sm-8<?php 
} ?> col-xs-12">
                <?php
                if ($attribute_mode) {
                    $attribute_helper = new Concrete\Core\Form\Service\Widget\Attribute();
                    ?>
                    <form action="<?= View::action('fill_attributes') ?>" method="POST">
                        <div data-handle="required_attributes"
                             class="authentication-type authentication-type-required-attributes">
                            <div class="ccm-required-attribute-form"
                                 style="height:340px;overflow:auto;margin-bottom:20px;">
                                <?php
                                foreach ($required_attributes as $key) {
                                    echo $attribute_helper->display($key, true);
                                }
                    ?>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary pull-right data-call-btn"><?= t('Submit') ?><div class="button-loader"></div></button>
                            </div>

                        </div>
                    </form>
                    <?php

                } else {
                    /** @var AuthenticationType[] $activeAuths */
                    foreach ($activeAuths as $auth) {
                        ?>
                         <div data-handle="<?= $auth->getAuthenticationTypeHandle() ?>"
                             class="authentication-type authentication-type-<?= $auth->getAuthenticationTypeHandle() ?>">
                            <?php $auth->renderForm($authTypeElement ?: 'form', $authTypeParams ?: array()) ?>
                        </div>
                    <?php

                    }
                }
                ?>
            </div>
        </div>
    </div>
    

     <div style="clear:both"></div>

    <script type="text/javascript">
        (function ($) {
            "use strict";

            var forms = $('div.controls').find('div.authentication-type').hide(),
                select = $('div.ccm-authentication-type-select > select');
            var types = $('ul.auth-types > li').each(function () {
                var me = $(this),
                    form = forms.filter('[data-handle="' + me.data('handle') + '"]');
                me.click(function () {
                    select.val(me.data('handle'));
                    if (typeof Concrete !== 'undefined') {
                        Concrete.event.fire('AuthenticationTypeSelected', me.data('handle'));
                    }

                    if (form.hasClass('active')) return;
                    types.removeClass('active');
                    me.addClass('active');
                    if (forms.filter('.active').length) {
                        forms.stop().filter('.active').removeClass('active').fadeOut(250, function () {
                            form.addClass('active').fadeIn(250);
                        });
                    } else {
                        form.addClass('active').show();
                    }
                });
            });

            select.change(function() {
                types.filter('[data-handle="' + $(this).val() + '"]').click();
            });
            types.first().click();

            $('ul.nav.nav-tabs > li > a').on('click', function () {
                var me = $(this);
                if (me.parent().hasClass('active')) return false;
                $('ul.nav.nav-tabs > li.active').removeClass('active');
                var at = me.attr('data-authType');
                me.parent().addClass('active');
                $('div.authTypes > div').hide().filter('[data-authType="' + at + '"]').show();
                return false;
            });

            <?php
            if (isset($lastAuthType)) {
                ?>
                $("ul.auth-types > li[data-handle='<?= $lastAuthType->getAuthenticationTypeHandle() ?>']")
                    .trigger("click");
                <?php

            }
            ?>
        })(jQuery);
    </script>
</div>
