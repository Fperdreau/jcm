<?php
/*
Copyright © 2014, Florian Perdreau

This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/
require('../includes/boot.php');

$pages = ($AppPage->getPages());
$sql = "SELECT * FROM ".$db->tablesname['Pages'];
$req = $db->send_query($sql);
$pageSettings = "";
while ($row = mysqli_fetch_assoc($req)) {
    $pageName = $row['name'];
    $pageStatus = $row['status'];
    $pageRank = $row['rank'];
    $pageParent = $row['parent'];
    $pageTitle = $row['meta_title'];
    $pageKey = $row['meta_keywords'];
    $pageDesc = $row['meta_description'];

    $pageList = "<option value='none'>None</option>";
    foreach ($pages as $name) {
        $selectOpt = ($name == $pageParent) ? "selected":"";
        $pageList .= "<option value='$name' $selectOpt>$name</option>";
    }

    $statusList = "";
    $status = array('member','organizer','admin','none');
    $i = 0;
    foreach ($status as $st) {
        $selectOpt = ($st == $pageStatus) ? "selected":"";
        $statusList .= "<option value='$i' $selectOpt>$st</option>";
        $i++;
    }

    $rankList = "";
    for ($i=0;$i<count($pages);$i++) {
        $selectOpt = ($i == $pageRank) ? "selected":"";
        $rankList .= "<option value='$i' $selectOpt>$i</option>";
    }

    $pageSettings .= "
            <div class='plugDiv' id='page_$pageName'>
                <div class='plugLeft' style='width: 200px;'>
                    <div class='plugName'>$pageName</div>
                </div>

                <div class='plugSettings'>
                    <form id='config_page_$pageName'>
                        <input type='hidden' value='true' name='modPage' />
                        <input type='hidden' value='$pageName' name='name' />
                        <div class='formcontrol'>
                            <label>Status</label>
                            <select class='select_opt' name='status'>
                                $statusList
                            </select>
                        </div>
                        <div class='formcontrol'>
                            <label>Rank</label>
                            <select class='select_opt' name='rank'>
                                $rankList
                            </select>
                       </div>
                        <input type='submit' value='Modify' class='page_modify' id='$pageName'/>
                    </form>
                </div>
            </div>
            ";

}

$content = "
    <p class='page_description'>Here you manage the pages' settings and status</p>
    <section>
        <h2>Pages Management</h2>
        <div class='feedback'></div>
        $pageSettings
    </section>
";

$result = "
    <div id='content'>
        $content
    </div>";

echo json_encode($result);
exit;