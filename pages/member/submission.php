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

 use includes\Users;
 use includes\Presentation;
 use includes\Suggestion;
 
$username = (isset($pageParameters['Users'])) ? $pageParameters['Users'] : $_SESSION['username'];
$user = new Users($username);

$submit_form = null;
$section_content = null;

if (isset($pageParameters['op'])) {
    $op = htmlspecialchars($pageParameters['op']);
    $date = (!empty($pageParameters['date'])) ? htmlspecialchars($pageParameters['date']) : null;
    // Submit a new presentation
    if ($op == 'edit') {
        $data = !empty($pageParameters['id']) ? array('id'=>$pageParameters['id']) : null;
        $Presentation = new Presentation();
        $section_content = $Presentation->editor($data);

    // Suggest a presentation
    } elseif ($op == 'suggest') {
        $Suggestion = new Suggestion();
        $data['id'] = !empty($pageParameters['id']) ? $pageParameters['id'] : null;
        $data['operation'] = 'edit';
        $section_content = $Suggestion->editor($data);

    // Select from the wish list
    } elseif ($op == 'wishpick') {
        $Suggestion = new Suggestion();
        $data['id'] = !empty($pageParameters['id']) ? $pageParameters['id'] : null;
        $data['operation'] = 'selection_list';
        $data['destination'] = '.submission_container';
        $section_content = $Suggestion->editor($data, 'body');
    }
}

// Submission menu
$submitMenu = Presentation::menu('body');

$form_section = null;
if (!is_null($section_content)) {
    $form_section = \includes\Presentation::formatSection($section_content);
}

$result = "
    {$submitMenu}
    <div class='submission_container'>{$form_section}</div> 
";
echo $result;
