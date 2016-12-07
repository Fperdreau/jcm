<?php
/**
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

// Get contact form
$recipients_list = isset($_POST['recipients_list']) ? $_POST['recipients_list'] : null;
$MailManager = new MailManager($db);
$contactForm = $MailManager->getContactForm($recipients_list);

// Send mail
$result = "
    <div class='page_header'>
    <p class='page_description'>Here you can send emails along with attached files to other JCM members.</p>
    </div>
    
    <section>
        <div class='section_content'>{$contactForm}</div>
    </section>";

echo $result;