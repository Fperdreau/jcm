# jcm
Author: Florian Perdreau (Radboud University Nijmegen, Donders Centre for Cognition), www.florianperdreau.fr

Journal Club Manager is a web-application developed in order to help labs in managing their journal clubs or talks.
Users who created an account on this application can schedule, modify or delete their presentations.
Also, presentation/talks can be suggested to the other users of the application.

Journal Club Manager includes a mailing system (requires CRON jobs installed on the running server) to notify users
about upcoming journal club sessions, recent news posted by admins or the last wishes added by the other users.<br>
An administration interface allows admins and organizers of the journal club to manage the different presentations, users,
to export/back-up the database, to configure the application (frequency of email notifications, etc.).


Requirements: 
- A web server running PHP 5.2 or later, 
- MySQLi (5.0 or later), 
- CRON table (required for email notifications), 
- SMTP server (or a Google Mail account)
