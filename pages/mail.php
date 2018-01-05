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

 use includes\App;
 use includes\MailManager;
 
// Bootstrap
include('../includes/App.php');
App::boot(true);

if (!empty($_GET['mail_id'])) {
    $MailManager = new MailManager();
    $content = $MailManager->show(htmlspecialchars($_GET['mail_id']));
} else {
    $content = "Nothing to show here";
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <META NAME="viewport" CONTENT="width=device-width, initial-scale=1.0, user-scalable=yes">
    <META NAME="description" CONTENT="Journal Club Manager - an efficient way of organizing journal clubs">
    <META NAME="keywords" CONTENT="Journal Club, application">

    <!-- Stylesheets -->
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link type='text/css' rel='stylesheet' href="../styles/stylesheet.min.css"/>

    <title>Journal Club Manager - Organize your journal club efficiently</title>
</head>
<body>
<?php echo $content; ?>
</body>
</html>
