<?php

namespace App\Controller;

use App\Service\DirectAdmin;

class DirectAdminEmailController {

    private DirectAdmin $da;

    public function __construct(DirectAdmin $directAdmin) {
        $this->da = $directAdmin;
    }

    public function createEmail(string $domain, string $username, string $password) {
        $result = $this->da->query('CMD_API_POP', [
            'action' => 'create',
            'domain' => $domain,
            'user' => $username,
            'passwd' => $password,
            'passwd2' => $password,
            'quota' => '0',
            'limit' => '0'
        ]);
        return $result['error'] == '0';
    }

    public function changePassword(string $domain, string $username, string $password) {
        $result = $this->da->query('CMD_API_POP', [
            'action' => 'modify',
            'domain' => $domain,
            'user' => $username,
            'newuser' => $username,
            'passwd' => $password,
            'passwd2' => $password,
            'quota' => '0',
            'limit' => '0'
        ]);
        return $result['error'] == '0';
    }

    public function deleteEmail(string $domain, string $username) {
        $result = $this->da->query('CMD_API_POP', [
            'action' => 'delete',
            'domain' => $domain,
            'clear_forwarders' => 'yes',
            'select0' => $username
        ]);
        return $result['error'] == '0';
    }

}
