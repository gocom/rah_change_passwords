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

        return
            hed(gTxt('rah_change_passwords'), 3).

            inputLabel(
                'rah_change_passwords_pass',
                fInput(
                    'password',
                    'rah_change_passwords_pass',
                    '',
                    '',
                    '',
                    '',
                    INPUT_REGULAR,
                    '',
                    'rah_change_passwords_pass'
                ),
                'rah_change_passwords_pass'
            ).

            inputLabel(
                'rah_change_passwords_confirm',
                fInput(
                    'password',
                    'rah_change_passwords_confirm',
                    '',
                    '',
                    '',
                    '',
                    INPUT_REGULAR,
                    '',
                    'rah_change_passwords_confirm'
                ),
                'rah_change_passwords_confirm'
            ).

            ($txp_user !== $r['name'] ?
                inputLabel(
                    'rah_change_passwords_reset_session',
                    yesnoradio(
                        'rah_change_passwords_reset_session',
                        0,
                        '',
                        'rah_change_passwords_reset_session'
                    ),
                    '',
                    'rah_change_passwords_reset_session'
                ) : ''
            );
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

        foreach (['pass', 'confirm', 'reset_session'] as $name) {
            $$name = ps('rah_change_passwords_'.$name);
        }

        if (!$user_id) {
            return;
        }

        $user = safe_row(
            'name',
            'txp_users',
            "user_id='$user_id' limit 0, 1"
        );

        if (!$user || (!has_privs('rah_change_passwords') && $txp_user !== $user['name'])) {
            return;
        }

        if ($pass) {
            if ($pass !== $confirm) {
                echo announce(gTxt('rah_change_passwords_confirm_error'), E_ERROR);
                return;
            }

            if (change_user_password($user['name'], $pass) === false) {
                echo announce(gTxt('rah_change_passwords_update_failed'), E_ERROR);
                return;
            }
        }

        if ($reset_session && $txp_user !== $user['name']) {
            if (update_user($user['name'], null, null, ['nonce' => $this->getSessionId()]) === false) {
                echo announce(gTxt('rah_change_passwords_update_failed'), E_ERROR);
                return;
            }
        }

        if (!$pass) {
            return;
        }

        echo announce(gTxt('rah_change_passwords_changed'));
    }

    /**
     * Gets a new session key.
     *
     * @return string
     */
    private function getSessionId()
    {
        return md5(uniqid(mt_rand(), true));
    }
}
