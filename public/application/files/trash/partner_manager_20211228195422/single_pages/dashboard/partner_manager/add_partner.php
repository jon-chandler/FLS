<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php        
	$fp = FilePermissions::getGlobal();
	$tp = new TaskPermission();

	$al = Loader::helper('concrete/asset_library');
  
	$ih = Loader::helper('concrete/ui'); 
	$action = $view->action('add_partner');
	$PageTitle = t('New Partner');
	$button = t('Add');

	$form = Loader::helper('form'); 
	
	if ($controller->getTask() == 'edit') {
		$action = $view->action('edit_partner', $sID);
		$PageTitle = t('Edit Partner');
		$button = t('Update');
	}


	if ($partner_logo > 0) {
        $fo = File::getByID($partner_logo);
        if (!is_object($fo)) {
            unset($fo);
        }
    }


?>
    <form method="post" class="form-horizontal" action="<?php echo $action; ?>">
		<fieldset>
    		<legend><?php echo $PageTitle; ?></legend>
			
			<div class="row">
				<div class="form-group">
					<label for="partner" class="control-label col-sm-3"><?php echo t('Partner'); ?></label>
						<div class="col-md-5">
						<?php echo $form->text('partner', $partner); ?>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="form-group">
					<label for="partner_type" class="control-label col-sm-3"><?php echo t('Type'); ?></label>
						<div class="col-md-5">
						<select name="partner_type" class="form-control ccm-input-select">
							<option value=""> - Select -</option>
							<?php 
								$types = $this->controller->getSelectValues('partner_type');
								foreach ($types as $type) {
									$selected = ($partner_type == $type['value']) ? 'selected' : '';
									echo "<option value='". $type['value'] ."' ". $selected .">". $type['value'] ."</option>";
								}
							?>
						</select>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="form-group">
					<label for="partner_type" class="control-label col-sm-3"><?php echo t('Offerings'); ?></label>
						<div class="col-md-5">
							<?php 
								$types = $this->controller->getSelectValues('partner_offerings');

								$bodyStyle = $this->controller->getBodyStyleOpts();

								$vals = $partner_offerings;
								$offerings = (!empty($vals)) ? explode(',', $vals) : [];

								foreach ($types as $key => $type) { 
								?>
									<div class="checkbox">
										<label>
											<?php echo $form->checkbox('partner_offerings[]', $key, in_array($key, $offerings)); ?>
											<?php echo $type['value']; ?>
										</label>
									</div>
							<?php } ?>
						</div>
				</div>
			</div>


			<div class="row">
				<div class="form-group">
					<label for="link" class="control-label col-sm-3"><?php echo t('Link'); ?></label>
						<div class="col-md-5">
						<?php echo $form->text('link', $link); ?>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="form-group">
					<label for="link" class="control-label col-sm-3"><?php echo t('Locations'); ?></label>
						<div class="col-md-5">
						<?php
							echo $form->textArea('locations', $locations); 
						?>
					</div>
				</div>
			</div>


			<div class="row">
				<div class="form-group">
					<label for="partner_type" class="control-label col-sm-3"><?php echo t('Body Styles'); ?></label>
						<div class="col-md-5">
							<?php 
								$types = $this->controller->getBodyStyleOpts('getBodyStyleOpts');

								$vals = $body_styles;
								$styles = (!empty($vals)) ? explode(',', $vals) : [];

								foreach ($types as $key => $style) { 
								?>
									<div class="checkbox">
										<label>
											<?php echo $form->checkbox('body_styles[]', $key, in_array($key, $styles)); ?>
											<?php echo $style['attribute_name']; ?>
										</label>
									</div>
							<?php } ?>
						</div>
				</div>
			</div>


			<div class="row">
				<div class="form-group">
					<label for="active" class="control-label col-sm-3"><?php echo t('Active'); ?></label>
						<div class="col-md-5">
						<?php echo $form->checkbox('active', 1, $active); ?>
					</div>
				</div>
			</div>


			<div class="row">
				<div class="form-group">
					<label for="partner_logo" class="control-label col-sm-3"><?php echo t('Partner logo'); ?></label>
						<div class="col-md-5">
							<?php
					            echo $al->file('ccm-partner_logo', 'partner_logo', t('Choose File'), $fo);
					        ?>
						</div>
				</div>
			</div>	

			<fieldset>
   			<legend>DATA PARTNER</legend>
				<div class="row">
					<div class="form-group">
						<label for="hp_percent" class="control-label col-sm-3"><?php echo t('Data provider HP %'); ?></label>
						<div class="col-md-5">
							<?php echo $form->text('hp_percent', $hp_percent); ?>
						</div>
					</div>
				</div>
			</fieldset>

    	</fieldset>
    

	<div class="ccm-dashboard-form-actions-wrapper">
	<div class="ccm-dashboard-form-actions">
		<a href="<?php echo View::url('/dashboard/partner_manager')?>" class="btn btn-default pull-left"><?php echo t('Cancel'); ?></a>
		<?php echo Loader::helper("form")->submit($button, $button, array('class' => 'btn btn-primary pull-right')); ?>
	</div>
	</div>
    </form>