<?php 

    defined('C5_EXECUTE') or die("Access Denied.");

    $save_url = \Concrete\Core\Url\Url::createFromUrl($view->action('save_avatar'));
    $save_url = $save_url->setQuery(array(
        'ccm_token' => $token->generate('avatar/save_avatar'),
    ));

    $id = 294; // ID for parent section. Will update

    $width = Config::get('concrete.icons.user_avatar.width');
    $height = Config::get('concrete.icons.user_avatar.height');
?>

<main>
    <div class="full-width">
        <section class="fls-profile restricted-content">
            
            <div class="avatar-content-wrapper">
                <div vue-enabled>
                    <h2><?php echo $c->getCollectionName(); ?></h2>
                    <avatar-cropper  
                        v-bind:height="<?php echo $height; ?>"
                        v-bind:width="<?php echo $width; ?>"
                        uploadurl="<?php echo $save_url; ?>"
                        src="<?php echo $profile->getUserAvatar()->getPath(); ?>">
                    </avatar-cropper>

                    <?php if ($profile->hasAvatar()) { ?>
                        <form method="post" action="<?php echo $view->action('delete'); ?>">
                            <?php echo $token->output('delete_avatar'); ?>
                            <button class="button-green-pale search data-call-btn" title="Delete"><span>Delete picture</span><div class="button-loader"></div></button>
                        </form>
                    <?php } ?>

                    <br />

                    <div class="ccm-dashboard-form-actions-wrapper">
                        <div class="ccm-dashboard-form-actions">
                            <a href="<?php echo URL::to('/pupil'); ?>" /><button class="button-dark-green search data-call-btn" title="Your profile"><span>Your profile</span><div class="button-loader"></div></button></a>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>
</main>