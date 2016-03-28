# Journal Club Manager &copy; 2014

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

- Check on assignment randomization
- Check on emails (room number)

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
