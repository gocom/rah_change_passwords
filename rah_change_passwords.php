<?php	##################
	#
	#	rah_change_passwords-plugin for Textpattern
	#	version 0.1
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') {
		add_privs('rah_change_passwords','1');
		register_tab("extensions", "rah_change_passwords", "Change passwords");
		register_callback("rah_change_passwords", "rah_change_passwords");
	}

	function rah_change_passwords() {
		global $step;
		require_privs('rah_change_passwords');
		if($step == 'rah_change_passwords_save') $step();
		else rah_change_passwords_edit();
	}

	function rah_change_passwords_edit($message='') {
		global $event,$step;
		pagetop('rah_change_passwords',$message);
		echo 
			'	<form method="post" action="index.php" style="width:900px;margin:0 auto;position:relative;">'.n.
			'		<h1><strong>rah_change_passwords</strong> | Change user passwords</h1>'.n.
			'		<p>&#187; <a target="_blank" href="?event=plugin&amp;step=plugin_help&amp;name=rah_change_passwords">Documentation</a></p>'.n.
			'		<fieldset style="padding:20px;margin:20px 0;">'.n.
			'			<legend>Reset a password</legend>'.n.
			'			<table border="0" cellspacing="3" cellpadding="0">'.n.
			'				<tr>'.n.
			'					<td style="vertical-align: middle;"><label for="rah_user_id">User</label></td>'.n.
			'					<td style="vertical-align: middle;"><select class="edit" name="user_id" id="rah_user_id">';
		
		$rs = safe_rows_start('user_id,name','txp_users',"1=1 order by name asc");
		while ($a = nextRow($rs)) {
			extract($a);
			echo '<option value="'.$user_id.'">'.$name.'</option>';
		}
		echo 
			'</select></label></td>'.n.
			'				</tr>'.n.
			'				<tr>'.n.
			'					<td style="vertical-align: middle;"><label for="rah_password">New password</label></td>'.n.
			'					<td style="vertical-align: middle;"><input class="edit" type="password" name="pass" id="rah_password" value="" size="100" /></td>'.n.
			'				</tr>'.n.
			'				<tr>'.n.
			'					<td style="vertical-align: middle;"><label for="rah_confirm">Confirm new password</label></td>'.n.
			'					<td style="vertical-align: middle;"><input class="edit" type="password" name="confirm" id="rah_confirm" value="" size="100" /></td>'.n.
			'				</tr>'.n.
			'			</table>'.n.
			'			<input type="submit" value="Change password" class="publish" />'.n.
			'		</fieldset>'.n.
			'		<input type="hidden" name="event" value="rah_change_passwords" />'.n.
			'		<input type="hidden" name="step" value="rah_change_passwords_save" />'.n.
			'	</form>'.n;
	}

	function rah_change_passwords_save() {
		global $sitename;
		if(ps('pass') == ps('confirm')) {
			safe_update('txp_users',"pass = password('".doSlash(ps('pass'))."')","user_id='".doSlash(ps('user_id'))."'");
			$email = fetch('email','txp_users','user_id',ps('user_id'));
			$name = fetch('name','txp_users','user_id',ps('user_id'));
			$message = gTxt('greeting').' '.$name.','.n.n.gTxt('your_password_is').': '.ps('pass').n.n.gTxt('log_in_at').': '.hu.'textpattern/index.php';
			txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
			rah_change_passwords_edit('Password changed.');
		} else rah_change_passwords_edit('Change incomplete: password and confirmation do not match.');
	}?>