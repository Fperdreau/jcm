<?php
/**
 * File for class DigestMaker
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

namespace includes;

use includes\BaseMailMaker;

/**
 * Class DigestMaker
 */
class DigestMaker extends BaseMailMaker
{

    /**
     * Constructor
     * @param bool $name
     */
    public function __construct($name = false)
    {
        parent::__construct($name);
    }

    // VIEW
    protected static function pageHeader()
    {
        return "<p class='page_description'>Here you can customize and preview the 
        weekly digest email that will be sent to the JCM members.</p>";
    }

    /**
     * Email header
     *
     * @return string
     */
    protected static function header()
    {
        return "Weekly digest - ".date('d M Y');
    }

    /**
     * Email body
     *
     * @param Users $user
     * @param string $content: email content
     * @return string
     */
    protected static function body(Users $user, $content)
    {
        return "
        <div style='width: 100%; margin: auto; text-align: center; font-size: 16px; font-weight: 500;'>
            <p>Hello {$user->firstname},</p>
            <p>Here is your Journal Club weekly digest.</p>
        </div>
        {$content}
        ";
    }
}
