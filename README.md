# jcm
<h1>Journal club Manager</h1>
<b>Author:</b> 
Florian Perdreau (Radboud University Nijmegen, Donders Centre for Cognition), www.florianperdreau.fr

<b>Description:</b> 
<p><i>Journal Club Manager</i> is a web-application developed in order to help labs in managing their journal clubs or talks.
Users who created an account on this application can schedule, modify or delete their presentations.
Also, presentation/talks can be suggested to the other users of the application.</p>

<p><i>Journal Club Manager</i> includes a mailing system (requires CRON jobs installed on the running server) to notify users
about upcoming journal club sessions, recent news posted by admins or the last wishes added by the other users.<br>
An administration interface allows admins and organizers of the journal club to manage the different presentations, users,
to export/back-up the database, to configure the application (frequency of email notifications, etc.).</p>

<b>License:</p>
<p><i>Journal Club Manager</i> is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.</p>
<p><i>Journal Club Manager</i> is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.</p>
<p>You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.</p>

<b>Requirements:</b> 
- A web server running PHP 5.2 or later, 
- MySQLi (5.0 or later), 
- CRON table (required for email notifications), 
- SMTP server (or a Google Mail account)

Instructions and a more detailed description of the Journal Club Manager can be found in "readme.pdf"

