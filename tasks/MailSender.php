<?php
/**
 * File for class MailSender
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tasks;
 
use includes\Task;
use includes\MailManager;

/**
 * Class MailSender
 */
class MailSender extends Task
{

    /**
     * @var string: Task name
     */
    public $name = 'MailSender';
    
    /**
     * @var array: task's settings
     */
    public $options = array(
        'nb_version'=>array(
            'options'=>array(),
            'value'=>10
        )
    );

    /**
     * @var MailManager $Manager
     */
    private static $Manager;

    public $description = "Checks that all emails have been sent and sends them otherwise. 
    It also cleans the mailing database by deleting the oldest emails. The number of days 
    of email storage can be defined in the task's settings (default is 10 days).";

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Factory
     * @return MailManager
     */
    private function getMailer()
    {
        if (is_null(self::$Manager)) {
            self::$Manager = new MailManager();
        }
        return self::$Manager;
    }

    /**
     * Sends emails
     *
     * @return array: array('status'=>success or failure, 'msg'=>output log)
     */
    public function run()
    {
        // Process emails queue
        $result = $this->getMailer()->processQueue();

        // Clean queue
        $this->getMailer()->cleanQueue($this->options['nb_version']['value']);

        return array('status'=>true, 'msg'=>$result);
    }
}
