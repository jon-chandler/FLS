<?php 

    defined('C5_EXECUTE') or die("Access Denied.");

    $save_url = \Concrete\Core\Url\Url::createFromUrl($view->action('save_avatar'));
    $save_url = $save_url->setQuery(array(
        'ccm_token' => $token->generate('avatar/save_avatar'),
    ));

    $width = Config::get('concrete.icons.user_avatar.width');
    $height = Config::get('concrete.icons.user_avatar.height');
?>

<main>
    <div class="full-width">
        <section class="avatar">
            
            <div class="avatar-content-wrapper">
                <div class="avatar-holder" vue-enabled>
                    <h2 class="section-heaader">Edit photo</h2>
                    <avatar-cropper  
                        v-bind:height="<?php echo $height; ?>"
                        v-bind:width="<?php echo $width; ?>"
                        uploadurl="<?php echo $save_url; ?>"
                        src="<?php echo $profile->getUserAvatar()->getPath(); ?>">
                    </avatar-cropper>

                    <?php if ($profile->hasAvatar()) { ?>
                        <form method="post" action="<?php echo $view->action('delete'); ?>">
                            <?php echo $token->output('delete_avatar'); ?>
                            <button class="delete" title="Delete">Delete photo</button>
                        </form>
                    <?php } ?>

                    <br />

                    <div class="ccm-dashboard-form-actions-wrapper">
                        <div class="ccm-dashboard-form-actions">
                            <a href="<?php echo URL::to('/account/edit_profile'); ?>" /><button class="profile-link" title="Edit details">Edit pupil details</button></a>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>
</main>