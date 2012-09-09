<?php

/**
 * Rah_change_passwords plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_change_passwords
 *
 * Requires Textpattern v4.5.0 or newer.
 *
 * Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	new rah_change_passwords();

class rah_change_passwords {
	
	/**
	 * Constructor
	 */
	
	public function __construct() {
		add_privs('rah_change_passwords', '1');
		register_callback(array($this, 'pane'), 'author_ui', 'extend_detail_form');
		register_callback(array($this, 'save'), 'admin', 'author_save');
	}
	
	/**
	 * Adds options to the Users panel
	 */
	
	public function pane($event, $step, $void, $r) {
		
		global $theme;
		
		if(!has_privs('rah_change_passwords') || !$r || !isset($r['user_id'])) {
			return;
		}
		
		$msg = escape_js($theme->announce_async(array(gTxt('rah_change_passwords_confirm_error'), E_ERROR)));
		
		$js = <<<EOF
			$(document).ready(function(){
				$('#user_edit, #rah_change_passwords_confirm, #rah_change_passwords_confirm_pass').attr('autocomplete', 'off');
				
				function validate_pass() {
					var form = {
						pass : $('#rah_change_passwords_pass').val(),
						conf : $('#rah_change_passwords_confirm').val()
					};
					
					if(form.pass === '' && form.conf === '') {
						return true;
					}
					
					return (form.pass === form.conf);
				}

				$('#user_edit').submit(function(){
					if(!validate_pass()) {
						$.globalEval("{$msg}");
						return false;
					}
				});
			});
EOF;

		return 
			tag(gTxt('rah_change_passwords'), 'h3').n.
			
			inputLabel(__CLASS__.'_pass', fInput('password', __CLASS__.'_pass', '', '', '', '', INPUT_REGULAR, '', __CLASS__.'_pass'), __CLASS__.'_pass').n.
				
			inputLabel(__CLASS__.'_confirm', fInput('password', __CLASS__.'_confirm', '', '', '', '', INPUT_REGULAR, '', __CLASS__.'_confirm'), __CLASS__.'_confirm').n.
			
			inputLabel(__CLASS__.'_email_pass', yesnoradio(__CLASS__.'_email_pass', 0, '', __CLASS__.'_email_pass'), '', __CLASS__.'_email_pass').n.
			
			inputLabel(__CLASS__.'_reset_session', yesnoradio(__CLASS__.'_reset_session', 0, '', __CLASS__.'_reset_session'), '', __CLASS__.'_reset_session').n.
			
			script_js($js);
	}

	/**
	 * Changes a password
	 */

	public function save() {
		
		extract(doSlash(psa(array(
			'user_id',
		))));
		
		global $sitename, $txp_user, $theme;
		
		foreach(array('pass', 'confirm', 'email_pass', 'reset_session') as $name) {
			$$name = ps(__CLASS__.'_'.$name);
		}
		
		if(!has_privs('rah_change_passwords') || !$user_id || !$pass) {
			return;
		}
		
		if($pass !== $confirm) {
			echo $theme->announce(array(gTxt('rah_change_passwords_confirm_error'), E_ERROR));
			return;
		}
			
		$rs = 
			safe_row(
				'email, name',
				'txp_users',
				"user_id='".doSlash($user_id)."' LIMIT 0, 1"
			);
		
		if(!$rs) {
			echo $theme->announce(array(gTxt('rah_change_passwords_unknown_user'), E_ERROR));
			return;
		}
		
		$sql = array();
		
		if($reset_session) {
			$sql[] = "nonce='".doSlash(md5(uniqid(mt_rand(), TRUE)))."'";
		}
		
		include_once txpath.'/include/txp_auth.php';
		$sql[] = "pass='".doSlash(txp_hash_password($pass))."'";
		
		if(
			safe_update(
				'txp_users',
				implode(',', $sql),
				"user_id='".doSlash($user_id)."'"
			) === false
		) {
			echo $theme->announce(array(gTxt('rah_change_passwords_update_failed'), E_ERROR));
			return;
		}
		
		if($reset_session && $rs['name'] === $txp_user) {
			$pub_path = preg_replace('|//$|', '/', rhu.'/');
			setcookie('txp_login', '', time()-3600);
			setcookie('txp_login_public', '', time()-3600, $pub_path);
		}
		
		if(!$email_pass) {
			echo $theme->announce(gTxt('rah_change_passwords_changed'));
			return;
		}
		
		extract($rs);
		
		$message = 
			gTxt('greeting').' '.$name.','.n.n.
			gTxt('your_password_is').': '.$pass.n.n.
			gTxt('log_in_at').': '.hu.'textpattern/index.php'
		;
		
		txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message);
		echo $theme->announce(gTxt('rah_change_passwords_mailed'));
	}
}

?>