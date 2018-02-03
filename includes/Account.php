<?php

namespace includes;

/**
 * Account class handles account's status and activation/deactivation
 */
class Account
{
    /**
     * Users instance
     *
     * @var Users
     */
    private static $Users;

    /**
     * Account types
     */
    private static $roles = array(
        'member'=>0,
        'organizer'=>1,
        'admin'=>2
    );

    /**
     * Class constructor
     */
    public function __construct()
    {
        // Get Users instance
        $this::$Users = new \includes\Users();
    }

    /**
     * Get user's role (member, organizer or admin)
     *
     * @param string $username: username
     * @return string: role
     */
    public function isAuthorized($username, $minRole = 'member')
    {
        $data = $this::$Users->get(array('username'=>$username));

        if ($data !== false) {
            return self::$roles[$data['status']] >= self::$roles[$minRole];
        } else {
            return false;
        }
    }
}
