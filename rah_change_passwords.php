<?php

/**
 * Rah_change_passwords plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2011-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_change_passwords
 *
 * Requires Textpattern v4.4.1 or newer.
 *
 * Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		add_privs('rah_change_passwords', '1');
		register_tab('extensions', 'rah_change_passwords', gTxt('rah_change_passwords'));
		register_callback(array('rah_change_passwords', 'panes'), 'rah_change_passwords');
		register_callback(array('rah_change_passwords', 'head'), 'admin_side', 'head_end');
	}

class rah_change_passwords {

	/**
	 * Delivers the panes
	 */

	static public function panes() {
		global $step;
		require_privs('rah_change_passwords');
		
		$steps = 
			array(
				'edit' => false,
				'save' => true
			);
		
		if(!$step || !bouncer($step, $steps))
			$step = 'edit';
		
		$pane = new rah_change_passwords();
		$pane->$step();
	}

	/**
	 * Adds styles to the <head>
	 */

	static public function head() {
		global $event;

		if($event != 'rah_change_passwords')
			return;

		echo <<<EOF
			<style type="text/css">
				#rah_change_passwords_container {
					width: 300px;
					margin: 0 auto;
				}
				#rah_change_passwords_container select {
					width: 240px;
				}
				#rah_change_passwords_container input.edit {
					width: 290px;
				}
			</style>
			<script type="text/javascript">
				<!--
				$(document).ready(function(){
					var pane = $('#rah_change_passwords_container');
					var drop = pane.find('select[name="user_id"]');
					var ctrl = pane.find('p:not(:first)');
					drop.val() ? ctrl.show() : ctrl.hide();
					drop.parent().css({'text-align' : 'center'});
					drop.change(function() {
						$(this).val() ? ctrl.show() : ctrl.hide();
						pane.find('input[type="password"]').val('');
					});
				});
				//-->
			</script>
EOF;
	}

	/**
	 * The main pane
	 * @param string $message Activity message
	 * @param bool $remember If TRUE, sent values, apart from the password, are kept in the fields.
	 */

	public function edit($message='', $remember=false) {
		
		global $event;
		
		pagetop(gTxt('rah_change_passwords'), $message);
		
		extract(psa(array(
			'user_id',
			'email_password',
			'end_session'
		)));

		if($remember == false)
			$end_session = $email_password = $user_id = '';

		echo 
			'<form method="post" action="index.php" id="rah_change_passwords_container" class="rah_ui_container" autocomplete="off">'.n.
			
			tInput().n.
			eInput($event).n.
			sInput('save').n.

			'	<p>'.n.
			'		<select name="user_id">'.n.
			'			<option value="">'.gTxt('rah_change_passwords_select_user').'</option>'.n;
		
		$rs = 
			safe_rows(
				'user_id,name',
				'txp_users',
				"1=1 order by name asc"
			);
		
		foreach($rs as $a) 
			echo 
				'			<option value="'.htmlspecialchars($a['user_id']).'"'.
				($a['user_id'] == $user_id ? ' selected="selected"' : '').
				'>'.htmlspecialchars($a['name']).'</option>'.n;
		
		echo 
			'		</select>'.n.
			'	</p>'.n.
			'	<p>'.n.
			'		<label>'.n.
			'			'.gTxt('rah_change_passwords_new_password').'<br />'.n.
			'			<input class="edit" type="password" name="pass" value="" autocomplete="off" />'.n.
			'		</label>'.n.
			'	</p>'.n.
			'	<p>'.n.
			'		<label>'.n.
			'			'.gTxt('rah_change_passwords_confirm_pass').'<br />'.n.
			'			<input class="edit" type="password" name="confirm" value="" autocomplete="off" />'.n.
			'		</label>'.n.
			'	</p>'.n.
			'	<p>'.n.
			'		'.gTxt('rah_change_passwords_email').'<br />'.n.
			'		<label>'.n.
			'			<input type="radio" name="email_password" value="yes"'.
				($email_password != 'no' ? ' checked="checked"' : '').' /> '.gTxt('rah_change_passwords_yes').n.
			'		</label>'.n.
			'		<label>'.n.
			'				<input type="radio" name="email_password" value="no"'.
				($email_password == 'no' ? ' checked="checked"' : '').' /> '.gTxt('rah_change_passwords_no').n.
			'		</label>'.n.
			'	</p>'.n.
			'	<p>'.n.
			'		'.gTxt('rah_change_passwords_reset_session').'<br />'.n.
			'		<label>'.n.
			'			<input type="radio" name="end_session" value="yes"'.
				($end_session != 'no' ? ' checked="checked"' : '').' /> '.gTxt('rah_change_passwords_yes').n.
			'		</label>'.n.
			'		<label>'.n.
			'			<input type="radio" name="end_session" value="no"'.
				($end_session == 'no' ? ' checked="checked"' : '').' /> '.gTxt('rah_change_passwords_no').n.
			'		</label>'.n.
			'	</p>'.n.
			'	<p><input type="submit" value="'.gTxt('rah_change_passwords_change_password').'" class="publish" /></p>'.n.
			'</form>'.n;
	}

	/**
	 * Saves the changes
	 */

	public function save() {
		extract(psa(array(
			'pass',
			'confirm',
			'user_id',
			'email_password',
			'end_session'
		)));
		
		global $sitename, $txp_user;
		
		if(empty($pass) || empty($confirm) || empty($user_id)) {
			$this->edit(array(gTxt('rah_change_passwords_required_fields'), E_ERROR), true);
			return;
		}
		
		if($pass !== $confirm) {
			$this->edit(array(gTxt('rah_change_passwords_confirm_error'), E_ERROR), true);
			return;
		}
		
		$rs = 
			safe_row(
				'email,name',
				'txp_users',
				"user_id='".doSlash($user_id)."' LIMIT 0, 1"
			);
			
		if(!$rs) {
			$this->edit(array(gTxt('rah_change_passwords_unknown_user'), E_ERROR), true);
			return;
		}
		
		$sql = array();
		
		if($end_session == 'yes')
			$sql[] = "nonce='".doSlash(md5(uniqid(mt_rand(), TRUE)))."'";
		
		include_once txpath.'/include/txp_auth.php';
		$sql[] = "pass='".doSlash(txp_hash_password($pass))."'";
		
		if(
			safe_update(
				'txp_users',
				implode(',',$sql),
				"user_id='".doSlash($user_id)."'"
			) == false
		) {
			$this->edit(array(gTxt('rah_change_passwords_update_failed'), E_ERROR), true);
			return;
		}
		
		if($end_session == 'yes' && $rs['name'] == $txp_user) {
			$pub_path = preg_replace('|//$|','/', rhu.'/');
			setcookie('txp_login', '', time()-3600);
			setcookie('txp_login_public', '', time()-3600, $pub_path);
		}
		
		if($email_password != 'yes') {
			$this->edit(gTxt('rah_change_passwords_changed'));
			return;
		}
		
		extract($rs);
		
		$message = 
			gTxt('greeting').' '.$name.','.n.n.
			gTxt('your_password_is').': '.$pass.n.n.
			gTxt('log_in_at').': '.hu.'textpattern/index.php'
		;
		
		txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
		$this->edit(gTxt('rah_change_passwords_mailed'));
	}
}

?>