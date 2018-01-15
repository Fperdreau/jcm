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

use includes\Session;
use includes\Presentation;
use includes\Template;
use includes\Router;
use includes\TypesManager;

// Declare classes
$Session = new Session();
$session_types_form = Template::section(
    array(
        'body'=>TypesManager::form(
            'Session',
            TypesManager::renderTypes(
                'Session',
                $Session->getSettings('types'),
                $Session->getSettings('default_type')
            )
        ),
        'title'=>'Session types'
    )
);

$Presentation = new Presentation();
$submission_types_form = Template::section(
    array(
        'body'=>TypesManager::form(
            'Presentation',
            TypesManager::renderTypes(
                'Presentation',
                $Presentation->getSettings('types'),
                $Presentation->getSettings('default_type')
            )
        ),
        'title'=>'Submission types'
    )
);

// Session default settings form
$settingsForm = Template::section(
    array(
        'body'=>Session::defaultSettingsForm($Session->getSettings()),
        'title'=>'Default settings'
    ),
    'session_manager'
);

// Session manager
$sessionManager = Template::section(
    array(
        'body'=>$Session->getSessionManager(),
        'title'=>'Session Manager'
    ),
    'session_manager'
);

$result = "
<div class='page_header'>
<p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>
</div>
<div class='section_container'>
    <div class='section_left'>
        {$settingsForm}
        {$session_types_form}
        {$submission_types_form}
    </div>

    <div class='section_right'>
        {$sessionManager}
    </div>
</div>";

echo $result;
