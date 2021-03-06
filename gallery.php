<?php

$original_username = $username;
$username = (isset($_GET['user'])) ? $_GET['user'].'/' : $username;

if ($isloggedin) {
	echo '<span id="username_view">You\'re viewing: <i>'.explode('/',$username)[0].'</i></span>';
}

for ($i = 0; $i < count($user_array); $i++) {
	$exploded_user_array = explode('//',$user_array[$i]);
	$user_exist = ((!$isloggedin && ($allow_public == true) && !isset($_GET['user'])) ? true : ((isset($_GET['user']) && $_GET['user'] == 'public' && ($allow_public == true)) ? true : (($username == trim($exploded_user_array[0]).'/') ? true : false)));
	if ($user_exist == true) {
		break;
	}
}

if (!is_dir($userpath.$username)) {
	mkdir($userpath.$username, 0744, true);
}
$directories = [1 => '/pictures', 2 => '/pictures/thumbs', 3 => '/video', 4 => '/video/thumbs', 5 => '/audio', 6 => '/documents', 7 => '/applications'];
if (is_dir($userpath.$username)) {
	$foldercreated = false;
	foreach ($directories as $key => $dir) {
		if (!is_dir($userpath.$username.$dir)) {
			mkdir($userpath.$username.$dir, 0744, true);
			file_put_contents($userpath.$username.$dir.'/.gitignore','# Ignore everything in this directory'."\r\n".'*'."\r\n".'# Except this file'."\r\n".'!.gitignore');
			$foldercreated = true;
		}
	}
	$folderexist = true;
} 

if (Config::read('moderation_queue') == true) {
	if (!is_dir($userpath.'moderation/')) {
		mkdir($userpath.'moderation/', 0744, true);
	}
	if (is_dir($userpath.'moderation/')) {
		$foldercreated = false;
		foreach ($directories as $key => $dir) {
			if (!is_dir($userpath.'moderation/'.$dir)) {
				mkdir($userpath.'moderation/'.$dir, 0744, true);
				file_put_contents($userpath.'moderation/'.$dir.'/.gitignore','# Ignore everything in this directory'."\r\n".'*'."\r\n".'# Except this file'."\r\n".'!.gitignore');
				$foldercreated = true;
			}
		}
		$folderexist = true;
	}
}			

if ((isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) || $allow_public == true) {
	$allempty = 0;
	$dir_array = [1 => 'audio', 2 => 'pictures/thumbs', 3 => 'video', 4 => 'documents', 5 => 'applications'];
	if ($user_exist == true || isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'admin') {
		foreach ($dir_array as $key => $folder) {
			if ($handle = opendir ($userpath.$username.$folder)) {
				$filelist = [];
				while (false !== ($file = readdir ($handle))) {
					$file = str_replace ('&', '&amp;',$file);
					$file = explode ('\n', $file);
					$file = $file[0];
					$filelist[] = $file;
				}
				natsort ($filelist);
				$remove_from_filelist = ['.','..','.DS_Store','index.html','.htaccess','.gitignore','Thumbs.db','thumbs.db','thumbs','private__'];
				foreach ($remove_from_filelist as $key => $value) {
					if (($key_rff = array_search($value, $filelist)) !== false) {
	 					unset($filelist[$key_rff]);
					}
					if (($value == 'private__') && (isset($_SESSION['username']) && ($_SESSION['username'] != explode('/',$username)[0]))) {
						foreach ($filelist as $fkey => $fvalue) {
							if (stripos($fvalue,$value) !== false && $_SESSION['usertype'] != 'admin') {
								unset($filelist[$fkey]);
							}
						}
					}
				}
				if (!empty($filelist)) {
					$allempty = 1;
					if ($folder == 'pictures/thumbs') { $folder = 'pictures'; };
					echo '<div class="container">
							<h2>'.ucfirst($folder).'</h2>
						<ul id="'.$folder.'_list"'.(($folder == 'pictures' || $folder == 'video') ? ' class="grid"' : '').'>';
						$id_number = 0;
					while (list ($key, $val) = each ($filelist)) {
						if ($val != "." && $val != ".." && in_array(getExtension(strtolower($val)),allowedMimeAndExtensions('extension'))) {
							++$id_number; 
							$usercontrols = '';
							$shared_content = '';
							if (is_link($_SERVER['DOCUMENT_ROOT'].'/'.$userpath.$username.$folder.'/'.$val)) {
								$shared_content = array_reverse(explode('/',readlink($_SERVER['DOCUMENT_ROOT'].'/'.$userpath.$username.$folder.'/'.$val)))[2];
							}
							$document_name = ((strpos($val,'__') == true) ? explode('__',urldecode(ucwords(removeExtension($val)))) : urldecode(ucwords(removeExtension($val))));
							$usercontrols = '<div class="usercontrols">
								<a class="sharefile" href="'.$baseurl.'sharefile.php">
									<i class="fa fa-share-alt" title="Share file"></i>
								</a>';
								if (($isloggedin && $isadmin) || ($isloggedin && $original_username == $username) || (is_array($document_name) && strtolower($document_name[0]) == strtolower(explode('/',$original_username)[0]))) {
								$usercontrols .= '<a class="deletefile" href="'.$processpath.'deletefile.php">
									<i class="fa fa-remove" title="Delete file"></i>
								</a>';
								}
								if ((($isloggedin && $isadmin) || ($original_username == $username)) && $username != 'public/') {
								$usercontrols .= '<form method="post" action="'.$processpath.'create_public_link.php.php"><input type="checkbox" id="'.$folder.'_'.$id_number.'" class="hidden make_public" title="Make public" '.((is_link($userpath.'public/'.$folder.'/'.explode('/',$username)[0].'__'.$val) ? 'checked' : '')).' value="'.((is_link($userpath.'public/'.$folder.'/'.explode('/',$username)[0].'__'.$val) ? 1 : 0)).'"><label title="'.(is_link($userpath.'public/'.$folder.'/'.explode('/',$username)[0].'__'.$val) ? 'Undo &quot;make file public&quot;' : 'Make file public').'" for="'.$folder.'_'.$id_number.'"><i class="makepublic fa '.((is_link($userpath.'public/'.$folder.'/'.explode('/',$username)[0].'__'.$val) ? 'fa-check-square' : 'fa-square')).'"></i></label></form>';
								}
							$usercontrols .= '</div>';
							if (getExtension($val) && ($folder == 'documents' || $folder == 'audio' || $folder == 'applications')) {
								$fileext = getExtension($val);
								$extension = (($fileext == 'txt') ? 'text-o' : (($fileext == 'xls' || $fileext == 'xlsx') ? 'excel-o' : (($fileext == 'doc' || $fileext == 'docx') ? 'word-o' : (($fileext == 'mp3' || $fileext == 'webm') ? 'audio-o' : (($fileext == 'dmg') ? 'o' : $fileext.'-o')))));
								$fileicon = '<i class="dark-background fa fa-file-'.$extension.'"></i>';
							}

							$document_name_result = ((is_array($document_name) && strtolower($document_name[0]) != 'private') ? '<span class="public_sharename">(Uploaded by '.$document_name[0].') - '.$document_name[1].'</span>' : ((is_array($document_name) && strtolower($document_name[0]) == 'private') ? '<span class="public_sharename tooltiphover private_file"><span data-tooltip="Go to your profile to change privacy settings"></span>(Set as private) '.$document_name[1].'</span>' : (is_array($document_name) ? '<span class="public_sharename">(Uploaded by '.$document_name[0].') '.$document_name[1].'</span>' : '<span class="public_sharename">'.rtrim(trim($document_name),'_-').'</span>')));
							if ($folder == 'video') {
								$getvidfile = 'showfile.php?vidfile='.$val.'';
							}
							$linkdisplay = (($folder == 'pictures') ?
								'<div class="imagecontainer"><a class="lightbox" href="showfile?imgfile='.$val.(isset($_GET['user']) ? '&user='.$_GET['user'].'' : '').'">
									<img src="showfile.php?imgfile='.$val.'&thumbs=true'.(isset($_GET['user']) ? '&user='.$_GET['user'].'' : '').'" alt="Thumbnail for '.$val.'">'.((is_array($document_name) && strtolower($document_name[0]) == 'private') ? '<span class="private_cover"></span>' : '').'
								</a>'.$usercontrols.$document_name_result.'</div>' : 
								(($folder == 'video') ? 
								'<div class="tech-slideshow">
									<a class="lightbox" href="'.$getvidfile.(isset($_GET['user']) ? '&user='.$_GET['user'].'' : '').'">
										<div class="stillimage" style="background: url('.$getvidfile.'.jpg&thumbs=true'.(isset($_GET['user']) ? '&user='.$_GET['user'].'' : '').');"></div>
										<div class="animationimage" style="background: url('.$getvidfile.'.gif&thumbs=true'.(isset($_GET['user']) ? '&user='.$_GET['user'].'' : '').');"></div>'.((is_array($document_name) && strtolower($document_name[0]) == 'private') ? '<span class="private_cover"></span>' : '').'
									</a>'.$usercontrols.$document_name_result.
								'</div>' : 
								(($folder == 'documents') ? 
								'<a href="showfile.php?docfile='.$val.'">'.$fileicon.' '.$document_name_result.'</a>'.$usercontrols : 
								(($folder == 'audio') ?
								'<a href="showfile.php?audiofile='.$val.'">'.$fileicon.' '.$document_name_result.'</a>'.$usercontrols : 
								(($folder == 'applications') ?
								'<a href="showfile.php?application='.$val.'">'.$fileicon.' '.$document_name_result.'</a>'.$usercontrols : '')))));
							$floatleft = (($folder == 'pictures' && !empty($shared_content)) ? 'class="grid-item pictures shared"' : (($folder == 'pictures' && empty($shared_folder)) ? 'class="grid-item pictures"' : (($folder == 'video') ? 'class="grid-item video"' : '')));
							echo '<li '.$floatleft.'>'.$linkdisplay.'</li>';
						}
					}
					closedir ($handle);
					echo '</ul></div>';
				}
			}
		}
	}

	echo '<div class="container '.(($allempty == 0) ? 'visible' : 'hidden').'">';
		if ($user_exist == true && (trim($exploded_user_array[0]) == trim(explode('/',$original_username)[0]))) {
			$status = 'info';
			$content = 'You haven\'t uploaded anything. <a href="upload">Upload files</a>';
		} elseif ($user_exist == true) {
			$status = 'info';
			$content = 'This user hasn\'t uploaded anything. Tell him or her to get their butt in gear';
		} elseif ($username == 'public/') {
			$status = 'info';
			$content = 'There are no public uploads to show. <a href="upload">Upload files</a>';
		} elseif ($user_exist == false) {
			$status = 'error';
			$content = 'The user doesn\'t exist on the server';
		} 
		echo '<p class="messagebox visible '.$status.'">'.$content.'</p>
		</div>';
	if ($show_quotes == true) { // this setting can be changed in config.php
		include 'quotes.php';
	}
}
?>