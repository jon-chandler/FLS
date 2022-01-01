<?php
	defined('C5_EXECUTE') or die("Access Denied.");

	$list = new \Concrete\Core\User\UserList();
	$group = \Group::getByName('Administrators');
	$list->filterByGroup($group, false);
	$list->filterByKeywords($_REQUEST['query']);
	//$list->filterByAttribute("firstName", "%{$_REQUEST['query']}%", "LIKE");
	
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
							<th>Allergies/Dietary needs</th>
							<th></th>
						</tr>";
				foreach($users as $i=>$user) {

					$DOB = $user->getAttribute('dob');
					$_DOB = $DOB->format('d/m/Y');
					$age = date_diff($DOB, $currDate);
					$years = round($age->days/365);
					$months = ($age->m != 1) ? "{$age->m}mths" : "{$age->m}mth";

					$groups = $user->getUserObject()->getUserGroups();
					$groupsVals = array_values($groups);
					$group = Group::getByID($groupsVals[1]);
					$className = explode(' ', $group->getGroupName())[1];

					$allergies = ($user->getAttribute('allergies_diet')) ? $user->getAttribute('allergies_diet') : 'N/A';
					
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
							<td title='{$_DOB}'>{$years}yrs : {$months}</td>
							<td>{$className}</td>
							<td>{$user->getAttribute('key_teacher')}</td>
							<td><div class='parent'>{$user->getAttribute('parent_f_name')} {$user->getAttribute('parent_l_name')}<div class='pic' style='background-image: url({$photo1})' data-img='{$photo1}'></div></div><div class='parent'>{$user->getAttribute('parent_f_name_2')} {$user->getAttribute('parent_l_name_2')}<div class='pic' style='background-image: url({$photo2})' data-img='{$photo2}'></div></div></td>
							<td><a href='tel:{$user->getAttribute('parent_contact_number')}'>{$user->getAttribute('parent_contact_number')}</a><br /><a href='tel:{$user->getAttribute('parent_contact_number_2')}'>{$user->getAttribute('parent_contact_number_2')}</a></td>
							<td>{$allergies}</td>
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