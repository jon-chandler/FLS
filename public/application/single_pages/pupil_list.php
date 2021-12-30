<?php
	defined('C5_EXECUTE') or die("Access Denied.");

	$list = new \Concrete\Core\User\UserList();
	$group = \Group::getByName('Administrators');
	$list->filterByGroup($group, false);
	$list->filterByAttribute("firstName", "%{$_REQUEST['query']}%", "LIKE");

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
							<th>Parents</th>
							<th>Contact numbers</th>
							<th></th>
						</tr>";
				foreach($users as $i=>$user) {

					$DOB = $user->getAttribute('dob');
					$_DOB = $DOB->format('d/m/Y');
					$age = date_diff($DOB, $currDate);
					$years = round($age->days/365);
					$months = ($age->m != 1) ? "{$age->m} months" : "{$age->m} month"; 

					if(!empty($user->getAttribute('parent_photo'))) {
						$photo1 = $user->getAttribute('parent_photo')->getRelativePath();
					}
					if(!empty($user->getAttribute('parent_photo_2'))) {
						$photo2 = $user->getAttribute('parent_photo_2')->getRelativePath();
					}

					if(!empty($user->getAttribute('emergency_photo'))) {
						$emergencyPhoto1 = $user->getAttribute('emergency_photo')->getRelativePath();
					}
					if(!empty($user->getAttribute('emergency_photo_2'))) {
						$emergencyPhoto2 = $user->getAttribute('emergency_photo_2')->getRelativePath();
					}

					echo "<tr>
							<td class='img-holder'><img class='table-img' src='{$user->getUserAvatar()->getPath()}' title='{$user->getAttribute('firstName')}' /></td>
							<td>{$user->getAttribute('firstName')}</td>
							<td>{$user->getAttribute('lastName')}</td>
							<td title='{$_DOB}'>{$years} years - {$months}</td>
							<td>{$user->getAttribute('class')}</td>
							<td>{$user->getAttribute('key_teacher')}</td>
							<td><div class='parent'>{$user->getAttribute('parent_f_name')} {$user->getAttribute('parent_l_name')}<img class='pic' src='{$photo1}' /></div><div class='parent'>{$user->getAttribute('parent_f_name_2')} {$user->getAttribute('parent_l_name_2')}<img class='pic' src='{$photo2}' /></div></td>
							<td><a href='tel:{$user->getAttribute('parent_contact_number')}'>{$user->getAttribute('parent_contact_number')}</a><br /><a href='tel:{$user->getAttribute('parent_contact_number_2')}'>{$user->getAttribute('parent_contact_number_2')}</a></td>
							<td class='edit'><a href='dashboard/users/search/view/{$user->getUserID()}' class='edit-button' title='Edit'></a></td>
						</tr>";
				}
				echo '</table>';
			} else {
				echo '<h3>No pupils found</h3>';
			}


			$a = new Area('Full width content');
			$a->display($c);
		?>
	</section>
</div>