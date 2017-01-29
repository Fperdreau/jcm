# Journal Club Manager &copy; 2014

## Release: 1.5.0
### Changes
- Corrected minor errors (PHP STRICT errors)
- Fixed compatibility issues with Php 5.4
- Improved user interface and page loading speed
- Corrected bug that prevented the user to delete his/her account
- Automatically log out user if inactive for more than a given duration
- Added Help section.
- Added About section.

### Mailing
- Added possibility to hide recipients list.
- Added possibility to test email host settings without modifying JCM's configuration during the installation process
 and on Admin>Settings page.
- Added possibility to reply to email sent through JCM if sender is a human.
- Added option to publish email's content as news.
- Automatically add recipients to list when selected from dropdown menu (no need to click the 'add' button)

### Assignments
- Send reminder to user about his/her upcoming presentation
- Speakers are now notified by email if the type of their session has been modified.

### Plugins
- Groups: only make groups for the next session 1) if a session is planned on this day and 2) if groups have not already
 been made.
  
## Release: 1.4.3
### Changes
- Corrected minor bugs introduced with JCM 1.4.2
- Improved styling of submission menu
- Mailing: recipients list now also includes every member.
- Mailing: added warning message in case no recipients have been added.

### Scheduled tasks
Improved management of scheduled tasks.
- Improved handling of plugins/scheduled tasks options
- Added possibility to display or delete scheduled tasks logs from Admin>Scheduled task page

### Mailing
- Added possibility to attach files to emails (Admin>Mailing page)

### Assignments
- Corrected bug in automatic assignment

### ReminderMaker
- New feature: Organizers can select what to display in the reminder emails (admin>reminder)

### Group plugin
- Added possibility to send an email to group members

## Release: 1.4.2
### Changes
- Updated TinyMCE version to 4.3.8
- Mailing tool: Add possibility to select recipients
- Removed signature from the emails. OK
- Added an option on the profile page to let the user the possibility to inform the system if he/she is available
 and can be automatically assigned to presentations or groups.
- Removed the possibility to change the website title. This avoids styling issues in the mobile version.
- Display list of user's assignment on its profile page
- Notify user by email if he/she has been manually assigned to a session or if his/her presentation has been canceled
 by an organizer
- Improved assignment randomization
- New feature: With the DigestMaker, now you can customize the weekly digest that will be sent to JCM members.

## Release: 1.4.1
### Changes
- fixed bugs in scheduled task execution
- Added an option on the profile page to leave the user the choice to be automatically assigned as a speaker.
- Improved automatic assignment of speakers and added work-around to prevent maximum execution time limit.

## Release: 1.4
### Changes:
- Major bugs corrections
- New responsive design (better compatibility with mobile devices and small screens)
- Simpler procedure to submit presentations
- Improved automatic speaker assignment (scheduled task) and removed management of chairmen. Now, the speaker is considered as the chairman.
- Added management of plugins (installation, settings and execution)
- Added management of scheduled tasks (installation, settings and execution)
- Simpler management of sessions

### Notes
This new version should be considered as a transition to the version 2.0.
Remaining issues:
- find a more efficient way to structure the application (handling of dependencies, classes structures)
- Use PDO instead of MySqli to handle database connections.
- make the application a javascript application instead of Php-driven application?
- Simplify (again) the submission process: the application will actually work as a calendar. Users will then be able to browse through the calendar in order to select the day of their
presentation or to get details about previous/upcoming presentations.

## Release: 1.3
### Changes:
New design, improved installer and new features!
- Now, journal club sessions can hold several presentations and the amount of presentation can be set by the admin or the organizers.
- Multiple files can now be attached to a presentation, either during the submission, or later by modifying the past presentation.
- Possibility to manage the sessions, their type or time, individually.
- Possibility to assign a chairman to each presentation either on each submission or via an automatic preplanning.
- Possibility to access the history of Posts, to edit/delete them and choose to add them to the homepage.
- The Configuration and the Admin tools are now only accessible by user with an admin status.
