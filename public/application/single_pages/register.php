<?php 
	defined('C5_EXECUTE') or die('Access Denied.'); 
?>

<section class="registration">
	<h1>Registration</h1>

	<?php if (!empty($registerSuccess)) { ?>
	    <div class="row">
	        <?php 

	        	// User registered on a page that they need to get back to.. Send them back
	        	if($_SESSION['KARFU_user']['registrationRedirect']) {
	        		$r = new RedirectResponse($_SESSION['KARFU_user']['registrationRedirect']);
                    $r->send();
	        	}

	            switch ($registerSuccess) {
	                case 'registered':
	                    ?>
	                    <p><strong><?= $successMsg; ?></strong><br/><br/>
	                    <a href="<?= $view->url('/'); ?>"><?= t('Return to Home'); ?></a></p>
	                    <?php
	                    break;
	                case 'validate':
	                    ?>
	                    <p><?= $successMsg[0]; ?></p>
	                    <p><?= $successMsg[1]; ?></p>
	                    <p><a href="<?= $view->url('/'); ?>"><?= t('Return to Home'); ?></a></p>
	                    <?php
	                    break;
	                case 'pending':
	                    ?>
	                    <p><?= $successMsg; ?></p>
	                    <p><a href="<?= $view->url('/'); ?>"><?= t('Return to Home'); ?></a></p>
	                    <?php
	                    break;
	            } 
	        ?>
	    </div>
	<?php } else { ?>
	    <form method="post" action="<?= $view->url('/register', 'do_register'); ?>" class="form-stacked">
	        <?php $token->output('register.do_register'); ?>
	        <div class="row">
				<?php if ($displayUserName) { ?>
					<div class="form-group">
						<?= $form->label('uName', t('Username')); ?>
						<?= $form->text('uName'); ?>
					</div>
				<?php } ?>
					<div class="form-group">
						<?= $form->label('uEmail', t('Email Address')); ?>
						<?= $form->text('uEmail'); ?>
					</div>
					<div class="form-group">
						<?= $form->label('uPassword', t('Password')); ?>
						<?= $form->password('uPassword', ['autocomplete' => 'off']); ?>
					</div>
					<?php if (Config::get('concrete.user.registration.display_confirm_password_field')) { ?>
						<div class="form-group">
						<?= $form->label('uPasswordConfirm', t('Confirm Password')); ?>
						<?= $form->password('uPasswordConfirm', ['autocomplete' => 'off']); ?>
					</div>
				<?php } ?>
	        </div>

	        <?php if (!empty($attributeSets)) { ?>
	            <div class="row">
	                <div class="col-sm-10 col-sm-offset-1">
	                    <?php foreach ($attributeSets as $setName => $attibutes) { ?>
	                        <fieldset>
	                            <legend><?= $setName; ?></legend>
	                            <?php
	                                foreach ($attibutes as $ak) {
	                                    $renderer->buildView($ak)->setIsRequired($ak->isAttributeKeyRequiredOnRegister())->render();
	                                }
	                            ?>
	                        </fieldset>
	                    <?php } ?>
	                </div>
	            </div>
	        <?php } ?>

	        <?php if (Config::get('concrete.user.registration.captcha')) { ?>
	            <div class="row">
	              
	                    <div class="form-group">
	                        <?php
	                        $captcha = Loader::helper('validation/captcha');
	                        echo $captcha->label(); ?>
	                        <?php
	                        $captcha->showInput();
	                        $captcha->display(); ?>
	                    </div>
	            </div>
	        <?php } ?>

	        <div class="row">
	                <div class="form-actions">
	                    <?= $form->hidden('rcID', isset($rcID) ? $rcID : ''); ?>
	                    <button class="button-dark-green data-call-btn"><?php echo t('Register'); ?><div class="button-loader"></div></button>
	                </div>
	        </div>
	    </form>
</section>	    
<?php } ?>
