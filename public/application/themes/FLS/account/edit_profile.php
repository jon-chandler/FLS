<?php 
    defined('C5_EXECUTE') or die('Access Denied.');
    $this->inc('elements/header.php');
 ?>
<main>
<section class="fls-profile">
    <div class="user-info">
    
    <div class="image-container">
        <a href="/account/avatar"><img src="<?php echo $profile->getUserAvatar()->getPath(); ?>" title="<?php t($profile->getUserName()); ?>" class="profile-img" /></a>
        <h1><?php echo t($profile->getUserName()); ?></h1>
    </div>

    <?php  if (isset($error) && $error != '') { ?>
        <?php  
        if ($error instanceof Exception) {
            $_error[] = $error->getMessage();
        } else if ($error instanceof ValidationErrorHelper) { 
            $_error = $error->getList();
        } else if (is_array($error)) {
            $_error = $error;
        } else if (is_string($error)) {
            $_error[] = $error;
        }    
        ?>
      <div class="ccm-system-errors alert alert-danger alert-dismissable">
        <?php
            $errs = json_decode(json_encode($error), true);
            for($i=0; $i<count($errs); $i++) {
                echo '<div>' . $errs['errors'][$i] . '</div>';
            }
        ?>
      </div>
   <?php } ?>

<form method="post" action="<?= $view->action('save'); ?>" enctype="multipart/form-data">
    <input type="hidden" name="rcID" id="rcID" value="1" />
	<?php $valt->output('profile_edit'); ?>

        <div class="form-group">
            <?= $form->hidden('uEmail', $profile->getUserEmail()); ?>
        </div>

    <?php foreach ($attributeSets as $setName => $attibutes) { ?>
        <fieldset>
            <legend><?php echo $setName; ?></legend>
            <?php
                foreach ($attibutes as $ak) {
                    $profileFormRenderer->buildView($ak)->setIsRequired($ak->isAttributeKeyRequiredOnProfile())->render();
                }
            ?>
        </fieldset>
    <?php } ?>

    <?php if (!empty($unassignedAttributes)) { ?>
        <fieldset>
            <?php
                foreach ($unassignedAttributes as $ak) {
                    $profileFormRenderer->buildView($ak)->setIsRequired($ak->isAttributeKeyRequiredOnProfile())->render();
                }
            ?>
        </fieldset>
    <?php } ?>

	<?php
    $ats = [];
    foreach (AuthenticationType::getList(true, true) as $at) {
        /* @var AuthenticationType $at */
        if ($at->isHooked($profile)) {
            if ($at->hasHooked()) {
                $ats[] = [$at, 'renderHooked'];
            }
        } else {
            if ($at->hasHook()) {
                $ats[] = [$at, 'renderHook'];
            }
        }
    }

    if (!empty($ats)) { ?>
		
            <?php
                foreach ($ats as $at) {
                    call_user_func($at);
                }
            ?>
    <?php } ?>

    <div class="password-header"><h3>Update password?</h3><sup>(optional)</sup></div>

    <fieldset class="password-update">
    	    <div class="form-group">
                <?= $form->label('uPasswordNew', t('New Password')); ?>
                <?= $form->password('uPasswordNew', ['autocomplete' => 'off']); ?>
    		</div>

            <div class="form-group">
                <?= $form->label('uPasswordNewConfirm', t('Confirm New Password')); ?>
                <?= $form->password('uPasswordNewConfirm', ['autocomplete' => 'off']); ?>
            </div>
    </fieldset>
    <fieldset>
        <div class="form-group row">
            <div class="form-actions">
                <?= $form->hidden('rcID', isset($rcID) ? $rcID : ''); ?>
                <button class="button-submit"><?php echo t('Update'); ?></button>
            </div>
        </div>
    </fieldset>

</form>
</div>
</section>
</main>

<?php $this->inc('elements/footer.php'); ?>

