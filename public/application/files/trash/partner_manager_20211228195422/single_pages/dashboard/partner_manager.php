<?php 
	defined('C5_EXECUTE') or die(_("Access Denied.")); 
?>
<style>
i.item-select-list-sort:hover{cursor:move}
.ui-sortable-helper {display: table;}

</style>
 <?php $confirmMsg = t('Are you sure?'); ?>
  <script type="text/javascript">
	deleteCode = function(url) {
		if(confirm('<?php echo $confirmMsg?>')){ 
			window.location = url
		} 
	}
 </script>

<div class="ccm-dashboard-header-buttons">
		<a href="<?php echo View::url('/dashboard/partner_manager/add_partner')?>" class="btn btn-primary"><?php echo t("Add Partner")?></a>
</div>

<?php 
	if(!empty($_REQUEST['ccm_paging_p'])) {
		//$pge = $_REQUEST['ccm_paging_p'];
	}

	$dir = $_REQUEST['setDir'];

	if (!empty($_SESSION['KARFU_user']['sortDirection'])) {
        if($dir === 'DESC') {
            $dir = 'ASC';
            $_SESSION['KARFU_user']['sortDirection'] = 'ASC';
        } else {
            $dir = 'DESC';
            $_SESSION['KARFU_user']['sortDirection'] = 'DESC';
        }
    } else {
        $dir = 'DESC';
    }

    $_SESSION['KARFU_user']['sortDirection'] = $dir;

?>

<table class="table table-striped sorted_table">
	<thead>
	<tr>
		<th><a href="/dashboard/partner_manager/?order=partner&ccm_paging_p=<?php echo $pge; ?>&setDir=<?php echo $dir; ?>"><?php echo t('Partner')?></a></th>
		<th><a href="/dashboard/partner_manager/?order=partner_type&ccm_paging_p=<?php echo $pge; ?>&setDir=<?php echo $dir; ?>"><?php echo t('Type')?></a></th>
		<th><?php echo t('Offerings')?></th>
		<th><?php echo t('Link')?></th>
		<th><a href="/dashboard/partner_manager/?order=partner&ccm_paging_p=<?php echo $pge; ?>&setDir=<?php echo $dir; ?>"><?php echo t('Logo')?></a></th>
		<th><?php echo t('Locations')?></th>
		<th><a href="/dashboard/partner_manager/?order=active&ccm_paging_p=<?php echo $pge; ?>&setDir=<?php echo $dir; ?>"><?php echo t('Active')?></a></th>
		<th></th>
		<th></th>
	</tr>
	</thead>
	<tbody>

	<?php foreach ($codesList as $tl) : ?>
		<tr id ="akID_<?php echo($tl->sID)?>">
			<td><?php echo $tl->partner; ?></td>
			<td><?php echo $tl->partner_type; ?></td>
			<td>
				<?php
					if(!empty($tl->partner_offerings)) {
						echo count(explode(',', $tl->partner_offerings));
					}
				?>
			</td>
			<td><a href="<?php echo $tl->link; ?>" target="_blank"><?php echo $tl->link; ?></a></td>
			<td>
				<?php 
					if(!empty($tl->partner_logo)) {
						$logo = File::getByID($tl->partner_logo);
						if(is_object($logo)) {
							$partner_logo = $logo->getRelativePath();
							echo '<img src="'. $partner_logo .'" width="50" />';
						} else {
							echo 'Selected image removed';
						}
					} else {
						echo 'No image selected';
					}
				 ?> 
			</td>
			<td>
				<?php 
					$locationCount = !empty($tl->locations) ? count(explode(',', $tl->locations)) : 'ALL';
					echo $locationCount;
				 ?> 
			</td>
			<td><i class="fa <?php echo ($tl->active) ? 'fa-check' : 'fa-times'; ?>"></i></td>
			<td>
				<a href="<?php echo $view->action('add_partner', 'edit', $tl->sID)?>" class="fa fa-pencil-square-o fa-lg"></a>
				<a href="#" onclick="deleteCode('<?php echo $view->action('delete_check', $tl->sID)?>')" class="fa fa-trash fa-lg"></a>
			</td>
			<td><!-- <i class="fa fa-arrows-v item-select-list-sort"></i> --></td>
		</tr>
  	<?php endforeach; ?>

	</tbody>
</table>
		<?php if ($paginator): ?>
			<?php echo $pagination; ?>
		<?php endif; ?>
		
	<script type="text/javascript">

	$(document).ready(function(){
		$('tbody').sortable({
			handle: 'i.item-select-list-sort',
			cursor: 'move',
 			opacity: 0.5,
			stop: function( event, ui ){
  				var ualist = $(this).sortable('serialize');
 				$.post('<?php echo URL::to('/PartnerManager/sortorder')?>', ualist, function(r) {});
  			}
		})
	});

</script>





