<?php 
	defined('C5_EXECUTE') or die(_("Access Denied.")); 
?>
<style>
i.item-select-list-sort:hover{cursor:move}
.ui-sortable-helper {display: table;}

</style>
 <?php $confirmMsg = t('Are you sure?'); ?>
  <script type="text/javascript">
	deleteCode = function() {
		if(confirm('<?php echo $confirmMsg?>')){ 

		} 
	}
 </script>

<div class="ccm-dashboard-header-buttons">
		<a href="<?php echo View::url('/dashboard/partner_manager/add_partner')?>" class="btn btn-primary"><?php echo t("Add Partner")?></a>
</div>

<table class="table table-striped sorted_table">
	<thead>
	<tr>
		<th><?php echo t('partner')?></th>
		<th><?php echo t('link')?></th>
		<th><?php echo t('active')?></th>
		<th><?php echo t('logo')?></th>
		<th></th>
		<th></th>
	</tr>
	</thead>
	<tbody>

	<?php foreach ($codesList as $tl) : ?>
		<tr id ="akID_<?php echo($tl->sID)?>">
			<td>
				<?php echo $tl->partner; ?>
			</td>
			<td><?php echo $tl->link; ?></td>
			<td><i class="fa <?php echo ($tl->active) ? 'fa-check' : 'fa-times'; ?>"></i></td>
			<td>
				<?php 
					if(!empty($tl->partner_logo)) {
						$logo = File::getByID($tl->partner_logo);
						$partner_logo = $logo->getRelativePath();
						echo '<img src="'. $partner_logo .'" width="50" />';
					}
				 ?> 
			</td>
			<td>
				<a href="<?php echo $view->action('add_partner', 'edit', $tl->sID)?>" class="fa fa-pencil-square-o fa-lg"></a>
				<a href="<?php echo $view->action('delete_check', $tl->sID)?>" onclick="deleteCode()" class="fa fa-trash fa-lg"></a>
			</td>
			<td><i class="fa fa-arrows-v item-select-list-sort"></i></td>
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





