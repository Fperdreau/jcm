# Journal Club Manager &copy; 2014

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


