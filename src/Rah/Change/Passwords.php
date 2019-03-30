<?php

/*
 * rah_change_passwords - Password changer for Textpattern CMS
 * https://github.com/gocom/rah_change_passwords
 *
 * Copyright (C) 2019 Jukka Svahn
 *
 * This file is part of rah_change_passwords.
 *
 * rah_change_passwords is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_change_passwords is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_change_passwords. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * The plugin class.
 */
final class Rah_Change_Passwords
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		add_privs('rah_change_passwords', '1');
		register_callback([$this, 'pane'], 'author_ui', 'extend_detail_form');
		register_callback([$this, 'save'], 'admin', 'author_save');
		register_callback([$this, 'head'], 'admin_side', 'head_end');
	}

	/**
	 * Removes the "Change your password" button.
	 */
	public function head()
	{
		global $event;

		if ($event !== 'admin' || !has_privs('admin.edit')) {
			return;
		}

		$js = <<<EOF
			$(document).ready(function () {
				$('#users_control a').filter(function () {
					return $(this).attr('href') === '?event=admin&step=new_pass_form';
				}).remove();
			});
EOF;

		echo script_js($js);
	}

	/**
	 * Adds options to the Users panel.
	 *
	 * @param  string $event The event
	 * @param  string $step  The step
	 * @param  mixed  $void  Not used
	 * @param  array  $r     The form data
	 * @return string HTML
	 */
	public function pane($event, $step, $void, $r)
	{
		global $txp_user;

		if (!$r || !isset($r['user_id'])) {
			return;
		}

		if (!has_privs('rah_change_passwords') && $txp_user !== $r['name']) {
			return;
		}

		$msg = escape_js(announce(gTxt('rah_change_passwords_confirm_error'), E_ERROR, ANNOUNCE_ASYNC));

		$js = <<<EOF
			$(document).ready(function () {
				$('#user_edit, #rah_change_passwords_confirm, #rah_change_passwords_confirm_pass').attr('autocomplete', 'off');

				function validate_pass()
				{
					var form = {
						pass : $('#rah_change_passwords_pass').val(),
						conf : $('#rah_change_passwords_confirm').val()
					};

					if (form.pass === '' && form.conf === '') {
						return true;
					}

					return (form.pass === form.conf);
				}

				$('#user_edit').submit(function () {
					if (!validate_pass()) {
						$.globalEval("{$msg}");
						return false;
					}
				});
			});
EOF;

		return
			hed(gTxt('rah_change_passwords'), 3).

			inputLabel('rah_change_passwords_pass', fInput('password', 'rah_change_passwords_pass', '', '', '', '', INPUT_REGULAR, '', 'rah_change_passwords_pass'), 'rah_change_passwords_pass').

			inputLabel('rah_change_passwords_confirm', fInput('password', 'rah_change_passwords_confirm', '', '', '', '', INPUT_REGULAR, '', 'rah_change_passwords_confirm'), 'rah_change_passwords_confirm').

			inputLabel('rah_change_passwords_email_pass', yesnoradio('rah_change_passwords_email_pass', 0, '', 'rah_change_passwords_email_pass'), '', 'rah_change_passwords_email_pass').

			($txp_user !== $r['name'] ?
				inputLabel('rah_change_passwords_reset_session', yesnoradio('rah_change_passwords_reset_session', 0, '', 'rah_change_passwords_reset_session'), '', 'rah_change_passwords_reset_session') : ''
			).

			script_js($js);
	}

	/**
	 * Changes a password
	 */
	public function save()
	{
		global $sitename, $txp_user;

		extract(doSlash(psa([
			'user_id',
		])));

		foreach (['pass', 'confirm', 'email_pass', 'reset_session'] as $name) {
			$$name = ps('rah_change_passwords_'.$name);
		}

		if (!$user_id) {
			return;
		}

		$rs = safe_row(
			'email, name',
			'txp_users',
			"user_id='".doSlash($user_id)."' limit 0, 1"
		);

		if (!$rs || (!has_privs('rah_change_passwords') && $txp_user !== $rs['name'])) {
			return;
		}

		$sql = [];

		if ($reset_session && $txp_user !== $rs['name']) {
			$sql[] = "nonce='".doSlash(md5(uniqid(mt_rand(), TRUE)))."'";
		}

		if ($pass) {
			if ($pass !== $confirm) {
				echo announce(gTxt('rah_change_passwords_confirm_error'), E_ERROR);
				return;
			}

			$sql[] = "pass='".doSlash(txp_hash_password($pass))."'";
		}

		if (!$sql) {
			return;
		}

		if (safe_update(
				'txp_users',
				implode(',', $sql),
				"user_id='".doSlash($user_id)."'"
			) === false
		) {
			echo announce(gTxt('rah_change_passwords_update_failed'), E_ERROR);
			return;
		}

		if (!$pass) {
			return;
		}

		if (!$email_pass) {
			echo announce(gTxt('rah_change_passwords_changed'));
			return;
		}

		extract($rs);

		$message =
			gTxt('greeting').' '.$name.','.n.n.
			gTxt('your_password_is').': '.$pass.n.n.
			gTxt('log_in_at').': '.hu.'textpattern/index.php';

		if (txpMail($email, "[$sitename] ".gTxt('your_new_password'), $message) === false) {
			echo announce(gTxt('rah_change_passwords_mailing_failed', ['{email}' => $email]), E_ERROR);
			return;
		}

		echo announce(gTxt('rah_change_passwords_mailed', ['{email}' => $email]));
	}
}
