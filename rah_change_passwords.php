<?php	##################
	#
	#	rah_change_passwords-plugin for Textpattern
	#	version 0.3
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if(@txpinterface == 'admin') {
		add_privs('rah_change_passwords','1');
		register_tab('extensions','rah_change_passwords','Change passwords');
		register_callback('rah_change_passwords','rah_change_passwords');
		register_callback('rah_change_passwords_head','admin_side','head_end');
	}

/**
	Delivers the panes
*/

	function rah_change_passwords() {
		global $step;
		require_privs('rah_change_passwords');
		if($step == 'rah_change_passwords_save')
			rah_change_passwords_save();
		else
			rah_change_passwords_edit();
	}

/**
	Adds styles to the <head>
*/

	function rah_change_passwords_head() {
		global $event;

		if($event != 'rah_change_passwords')
			return;

		echo <<<EOF
			<style type="text/css">
				#rah_change_passwords_container {
					width: 950px;
					margin: 0 auto;
				}
				#rah_change_passwords_container select {
					width: 450px;
				}
				#rah_change_passwords_container input.edit {
					width: 940px;
				}
			</style>
EOF;
	}

/**
	The main pane
*/

	function rah_change_passwords_edit($message='') {
		
		global $event;
		
		pagetop('Change passwords',$message);

		echo 
			'	<form method="post" action="index.php" id="rah_change_passwords_container">'.n.
			'		<h1><strong>rah_change_passwords</strong> | Reset user passwords</h1>'.n.
			'		<p>&#187; <a href="?event=plugin&amp;step=plugin_help&amp;name=rah_change_passwords">Documentation</a></p>'.n.
			
			
			'		<p>'.n.
			'			<label>'.n.
			'				User<br />'.n.
			'				<select name="user_id">'.n.
			'					<option value="">Select a user to modify...</option>'.n;
		
		$rs = 
			safe_rows(
				'user_id,name',
				'txp_users',
				"1=1 order by name asc"
			);
		
		foreach($rs as $a) 
			echo '					<option value="'.htmlspecialchars($a['user_id']).'">'.htmlspecialchars($a['name']).'</option>'.n;
		
		echo 
			'				</select>'.n.
			'			</label>'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label>'.n.
			'				New password<br />'.n.
			'				<input class="edit" type="password" name="pass" value="" />'.n.
			'			</label>'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			<label>'.n.
			'				Confirm new password<br />'.n.
			'				<input class="edit" type="password" name="confirm" value="" />'.n.
			'			</label>'.n.
			'		</p>'.n.
			'		<p>'.n.
			'			Email the password to the user?<br />'.n.
			'			<label>'.n.
			'				<input type="radio" name="email_password" value="yes" checked="checked" /> Yes'.n.
			'			</label>'.n.
			'			<label>'.n.
			'				<input type="radio" name="email_password" value="no" /> No'.n.
			'			</label>'.n.
			'		</p>'.n.
			'		<input type="submit" value="Change the password" class="publish" />'.n.
			'		<input type="hidden" name="event" value="'.$event.'" />'.n.
			'		<input type="hidden" name="step" value="rah_change_passwords_save" />'.n.
			'	</form>'.n;
	}

/**
	Saves the changes
*/

	function rah_change_passwords_save() {
		extract(gpsa(array(
			'pass',
			'confirm',
			'user_id',
			'email_password'
		)));
		
		if(empty($pass) || empty($confirm) || empty($user_id)) {
			rah_change_passwords_edit('Password or user fields are required.');
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
		
		if($email_password != 'yes') {
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