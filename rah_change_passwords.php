<?php	##################
	#
	#	rah_change_passwords-plugin for Textpattern
	#	version 0.2
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') {
		add_privs('rah_change_passwords','1');
		register_tab('extensions','rah_change_passwords','Change passwords');
		register_callback('rah_change_passwords','rah_change_passwords');
	}

	function rah_change_passwords() {
		global $step;
		require_privs('rah_change_passwords');
		if($step == 'rah_change_passwords_save')
			rah_change_passwords_save();
		else
			rah_change_passwords_edit();
	}

	function rah_change_passwords_edit($message='') {
		
		global $event;
		
		pagetop('Change passwords',$message);

		echo 
			'	<form method="post" action="index.php" style="width:950px;margin:0 auto;">'.n.
			'		<h1><strong>rah_change_passwords</strong> | Reset user passwords</h1>'.n.
			'		<p>&#187; <a href="?event=plugin&amp;step=plugin_help&amp;name=rah_change_passwords">Documentation</a></p>'.n.
			
			
			'		<p>'.n.
			'			<label for="rah_user_id">User</label><br />'.n.
			'			<select style="width:450px;" name="user_id" id="rah_user_id">'.n.
			'				<option value="">Select a user to modify...</option>'.n;
		
		$rs = 
			safe_rows(
				'user_id,name',
				'txp_users',
				"1=1 order by name asc"
			);
		
		foreach($rs as $a) 
			echo '				<option value="'.htmlspecialchars($a['user_id']).'">'.htmlspecialchars($a['name']).'</option>'.n;
		
		echo 
			'			</select>'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label for="rah_password">New password</label><br />'.n.
			'			<input class="edit" type="password" name="pass" id="rah_password" value="" style="width:940px;" />'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label for="rah_confirm">Confirm new password</label><br />'.n.
			'			<input class="edit" type="password" name="confirm" id="rah_confirm" value="" style="width:940px;" />'.n.
			'		</p>'.n.
			
			'		<p>'.n.
			'			<label for="rah_email_password">Email the password to the user?</label><br />'.n.
			'			<select style="width:450px;" name="email_password" id="rah_email_password">'.n.
			'				<option value="yes">Yes</option>'.n.
			'				<option value="no">No</option>'.n.
			'			</select>'.n.
			'		</p>'.n.
			
			'		<input type="submit" value="Change the password" class="publish" />'.n.
			'		<input type="hidden" name="event" value="'.$event.'" />'.n.
			'		<input type="hidden" name="step" value="rah_change_passwords_save" />'.n.
			'	</form>'.n;
	}

	function rah_change_passwords_save() {
		extract(gpsa(array(
			'pass',
			'confirm',
			'user_id',
			'email_password'
		)));
		
		if(empty($pass) or empty($confirm) or empty($user_id)) {
			rah_change_passwords_edit('Password or user can\'t be empty.');
			return;
		}
		
		if($pass != $confirm) {
			rah_change_passwords_edit('Password differs from confirmation.');
			return;
		}
		
		$rs = 
			safe_row(
				'email,name',
				'txp_users',
				"user_id='".doSlash($user_id)."'"
			);
			
		if(!$rs) {
			rah_change_passwords_edit('User not found.');
			return;
		}
		
		$update =
			safe_update(
				'txp_users',
				"pass = password('".doSlash($pass)."')",
				"user_id='".doSlash($user_id)."'"
			);
		
		if($update == false) {
			rah_change_passwords_edit('Updating database failed.');
			return;
		}
		
		if($email_password == 'no') {
			rah_change_passwords_edit('Password changed.');
			return;
		}
		
		global $sitename;
		
		extract($rs);
		
		$message = 
			gTxt('greeting').' '.$name.','.n.n.
			gTxt('your_password_is').': '.$pass.n.n.
			gTxt('log_in_at').': '.hu.'textpattern/index.php'
		;
		
		txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
		rah_change_passwords_edit('Password changed.');
	}?>