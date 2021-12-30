<?php
	defined('C5_EXECUTE') or die("Access Denied.");

	$list = new \Concrete\Core\User\UserList();
	$group = \Group::getByName('Administrators');
	$list->filterByGroup($group, false);
	$list->filterByFuzzyUsername($_REQUEST['query']);
	$users = $list->getResults();

	$currDate = new DateTime();

?>
<div class="full-width">
	<section class="search-results">
		<?php
			
			if($users) {
				echo "<table class='pupil-list-table'>
						<tr>
							<th></th>
							<th>First name</th>
							<th>Last name</th>
							<th>Age</th>
							<th>Class</th>
							<th>Key teacher</th>
							<th>Parent</th>
							<th>Contact #</th>
							<th></th>
						</tr>";
				foreach($users as $i=>$user) {

					$age = date_diff($user->getAttribute('dob'), $currDate);
					$years = round($age->days/365);

					echo "<tr>
							<td class='img-holder'><img class='table-img' src='{$user->getUserAvatar()->getPath()}' title='{$user->getAttribute('firstName')}' /></td>
							<td>{$user->getAttribute('firstName')}</td>
							<td>{$user->getAttribute('lastName')}</td>
							<td>{$years}</td>
							<td>{$user->getAttribute('class')}</td>
							<td>{$user->getAttribute('key_teacher')}</td>
							<td>{$user->getAttribute('parent_f_name')} {$user->getAttribute('parent_l_name')}</td>
							<td>{$user->getAttribute('parent_contact_number')}</td>
							<td class='edit'><a href='dashboard/users/search/view/{$user->getUserID()}'><button>Edit</button></a></td>
						</tr>";
				}
				echo '</table>';
			}


			$a = new Area('Full width content');
			$a->display($c);
		?>
	</section>
</div>