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
		register_tab('extensions', 'rah_change_passwords', gTxt('rah_change_passwords'));
		register_callback(array($this, 'panes'), 'rah_change_passwords');
	}

	/**
	 * Delivers the panes
	 */

	public function panes() {
		global $step;
		require_privs('rah_change_passwords');
		
		$steps = 
			array(
				'edit' => false,
				'save' => true
			);
		
		if(!$step || !bouncer($step, $steps))
			$step = 'edit';
		
		$this->$step();
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

		if($remember == false) {
			$end_session = $email_password = $user_id = '';
		}
		
		$rs = 
			safe_rows(
				'user_id, name',
				'txp_users',
				'1=1 ORDER BY name asc'
			);
		
		$users = array();
		
		foreach($rs as $a) {
			$users[$a['user_id']] = $a['name'];
		}

		echo 
			form(eInput($event).sInput('save').
				'<div class="txp-edit">'.n.
				'<h2>'.gTxt('rah_change_passwords').'</h2>'.n.
				
				inputLabel(__CLASS__.'_user_id', selectInput('user_id', $users, $user_id, true, '', __CLASS__.'_user_id'), __CLASS__.'_user_id').n.
				
				inputLabel(__CLASS__.'_pass', fInput('password', 'pass', '', '', '', '', INPUT_REGULAR, '', __CLASS__.'_pass'), __CLASS__.'_new_password').n.
				
				inputLabel(__CLASS__.'_confirm', fInput('password', 'confirm', '', '', '', '', INPUT_REGULAR, '', __CLASS__.'_confirm'), __CLASS__.'_confirm_pass').n.
				
				graf(checkbox('email_password', '1', false, '', __CLASS__.'_email_password').n. '<label for="'.__CLASS__.'_email_password">'.gTxt('rah_change_passwords_email').'</label>', ' class="edit-'.__CLASS__.'_email_passwords"').n.
				
				graf(checkbox('end_session', '1', false, '', __CLASS__.'_end_session').n. '<label for="'.__CLASS__.'_end_session">'.gTxt('rah_change_passwords_reset_session').'</label>', ' class="edit-'.__CLASS__.'_end_session"').n.
			
				graf(fInput('submit', 'change_pass', gTxt('rah_change_passwords_change_password'), 'publish')).
				
				script_js('$("#'.__CLASS__.'_confirm, #'.__CLASS__.'_pass").attr("autocomplete", "off");').
				
				'</div>'
			, '', '', 'post');
	}

	/**
	 * Saves the changes
	 */

	public function save() {
		
		global $sitename, $txp_user;
		
		extract(psa(array(
			'pass',
			'confirm',
			'user_id',
			'email_password',
			'end_session'
		)));
		
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
		
		if($end_session == 'yes') {
			$sql[] = "nonce='".doSlash(md5(uniqid(mt_rand(), TRUE)))."'";
		}
		
		include_once txpath.'/include/txp_auth.php';
		$sql[] = "pass='".doSlash(txp_hash_password($pass))."'";
		
		if(
			safe_update(
				'txp_users',
				implode(',', $sql),
				"user_id='".doSlash($user_id)."'"
			) == false
		) {
			$this->edit(array(gTxt('rah_change_passwords_update_failed'), E_ERROR), true);
			return;
		}
		
		if($end_session == 'yes' && $rs['name'] == $txp_user) {
			$pub_path = preg_replace('|//$|', '/', rhu.'/');
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