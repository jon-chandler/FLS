<?php
defined('C5_EXECUTE') or die('Access Denied');

/** @var $configuration \S3Storage\S3Configuration */
if (is_object($configuration)) {
	$useIAM = $configuration->useIAM;
	$bucket = $configuration->bucket;
	$key = $configuration->key;
	$secret = $configuration->secret;
	$expire = $configuration->expire;
	$expire_enabled = $configuration->expire_enabled;
	$region = $configuration->region;
	if (!$region) {
		$region = 'us-east-1';
	}
	$base_url = $configuration->base_url;

	$region_help = h(t(
		'This required field is where you can specify a AWS region. ' .
		'eg: us-east-1, us-west-1, eu-west-1.'
	));

	$expire_help = h(t(
		'<b>Note:</b> Bucket permissions must be configured properly for this to actually take effect. ' .
		'When enabled, you can enter any string accepted by %s. ' .
		'When not enabled, your bucket permissions must be configured to allow the public viewing of files. ' .
		'If you need some files to be restricted, I would suggest having two buckets and two storage locations.',
		'<a href="http://php.net/manual/en/function.strtotime.php">PHP\'s strtotime()</a>'
	));

	$host_help = h(t(
		'This optional field is where you can specify a host to be used ' .
		'(such as a Cloudfront host) instead of the normal S3 hostname when generating asset URIs. ' .
		'This can only be used with non-expiring urls.'
	));

	$iam_help = h(t(
		'If you are using concrete5 in an EC2 instance, you can use IAM roles instead of manually configuring.'
	));

	$expire_args = array('placeholder' => t('Optional unless checked'));
	$expire_required = '';
	if (!$expire_enabled) {
		$expire_args['disabled'] = 'disabled';
		$expire_required = ' style="display:none"';
	}

	$hide_iam = '';
	if ($useIAM) {
		$hide_iam = ' style="display:none"';
	}

	$form = Core::make('helper/form'); ?>
    <div class="form-group">
        <label for="fslType[bucket]"><?php echo t('Bucket')?></label>
        <div class="input-group">
			<?php echo $form->text('fslType[bucket]', $bucket)?>
            <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
        </div>
    </div>
    <div class="form-group">
        <label for="fslType[useIAM]"><?php echo t('Use IAM Roles')?></label>
        <i class="fa fa-question-circle" data-content="<?php echo $iam_help?>" data-toggle="popover"></i>
        <div class="input-group">
			<?php echo $form->checkbox('fslType[useIAM]', 1, $useIAM);?>
        </div>
    </div>
    <div class="hide-iam"<?php echo $hide_iam;?>>
        <div class="form-group">
            <label for="fslType[key]"><?php echo t('Key')?></label>
            <div class="input-group">
				<?php echo $form->text('fslType[key]', $key)?>
                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label for="fslType[secret]"><?php echo t('Secret')?></label>
            <div class="input-group">
				<?php echo $form->text('fslType[secret]', $secret)?>
                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label for="fslType[region]"><?php echo t('AWS Region')?></label>
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-question-circle" data-content="<?php echo $region_help?>" data-toggle="popover"></i>
                </span>
				<?php echo $form->text('fslType[region]', $region, array('placeholder' => 'us-east-1'))?>
                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label for="fslType[base_url]"><?php echo t('Alternate Host')?></label>
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-question-circle" data-content="<?php echo $host_help?>" data-toggle="popover"></i>
                </span>
				<?php echo $form->text('fslType[base_url]', $base_url, array('placeholder' => t('Optional. E.g. http://s3.example.com')))?>
            </div>
        </div>
        <div class="form-group">
            <label for="fslType[expire]"><?php echo t('Link Expire Time')?></label>
            <i class="fa fa-question-circle" data-content="<?php echo $expire_help?>" data-toggle="popover"></i>
            <div class="input-group">
                <div class="input-group-addon">
					<?php echo $form->checkbox('fslType[expire_enabled]', 1, $expire_enabled);?>
                </div>
				<?php echo $form->text('fslType[expire]', $expire, $expire_args)?>
                <span id="enabled_required" class="input-group-addon"<?php echo $expire_required?>>
                    <i class="fa fa-asterisk"></i>
                </span>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(function () {
            $('[data-toggle="popover"]').popover({ trigger: "hover", html: true })
        })
        $("input[name='fslType[expire_enabled]']").on("click", function() {
            if(!this.checked) {
                $("input[name='fslType[expire]']").prop('disabled', true);
                $("input[name='fslType[base_url]']").prop('disabled', false);
                $("#enabled_required").hide();
            } else {
                $("input[name='fslType[expire]']").prop('disabled', false);
                $("input[name='fslType[base_url]']").prop('disabled', true);
                $("#enabled_required").show();
            }
        });
        $("input[name='fslType[useIAM]']").on("click", function() {
            if(!this.checked) {
                $(".hide-iam").show();
            } else {
                $(".hide-iam").hide();
            }
        });
    </script>

	<?php
}