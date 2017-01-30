# Journal club Manager
##Author:
Florian Perdreau - [www.florianperdreau.fr](http://www.florianperdreau.fr)

##Description:
*Journal Club Manager* is a web-application developed in order to help labs in managing their journal clubs or talks.
Users who created an account on this application can schedule, modify or delete their presentations.
Also, presentation/talks can be suggested to the other users of the application.

*Journal Club Manager* includes a mailing system (requires CRON jobs installed on the running server) to notify users
about upcoming journal club sessions, recent news posted by admins or the last wishes added by the other users.
An administration interface allows admins and organizers of the journal club to manage the different presentations, users,
to export/back-up the database, to configure the application (frequency of email notifications, etc.).

##License:
Copyright &copy; 2014 Florian Perdreau

*Journal Club Manager* is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

*Journal Club Manager* is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.

###External sources
*Journal Club Manager* also depends on several external free softwares:

* PHPMailer, Copyright &copy; 2014 Marcus Bointon, licenced under the [LGPL 2.1 ](http://www.gnu.org/licenses/lgpl-2.1.html "LGPL 2.1").
* html2text, Copyright &copy; 2010 Jevon Wright and others, licenced under the [LGPL 2.1 ](http://www.gnu.org/licenses/lgpl-2.1.html "LGPL 2.1").
* TinyMCE Copyright &copy; Moxiecode Systems AB, licenced under the [LGPL 2.1 ](http://www.gnu.org/licenses/lgpl-2.1.html "LGPL 2.1").

##Requirements:
* A web server running PHP 5.4 or later
* MySQL (5.0 or later)
* CRON table (Linux)/Scheduled task (Windows) *(required for email notifications)*
* SMTP server or a Google Mail account

##Instructions
Instructions about how to install and use the *Journal Club Manager* can be found in the [manual](manual.md).
