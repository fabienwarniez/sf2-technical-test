# EA Symfony2 Technical Test

### Notes (after an additional 2 hours)

* I spent 2 more hours (6 total). I decided to spend a bit more time because I am fairly new to Symfony 2 and lost a lot of time looking up the Symfony way to do things.
* I made the GitHub class a service.
* I created a before filter to handle authentication.
* I added a couple of unit tests.

The state of the work after 4 hours is available at this commit: https://github.com/fabienwarniez/sf2-technical-test/commit/faa120eec8fde4792f22d65ee66c2ff982a41674

### Notes

* I spent 4 hours on the test.
* I am not completely familiarized with Symfony 2 (coming from Zend), so I did not use the authentication module to not waste too much time, nor did I fully embrace Symfony's patterns and features.
* I wanted to make the GitHub API access a service, but did not have time, unfortunately.
* I also did not have time to get to unit tests.

### Task

* The task is implement a website that allows the user to login to search the Github repoistories.
* You have 4 hours to complete the test.
* Please finish all the items outlined in the Requirements section first, then try to tackle items in the Nice to Haves section if you have time.
* If you cannot finish the test, please explain why as we are reasonable and realize people have time constraints.

### Setup

* You can find the design under the design folder
* You would need php5.5, Symfony 2 and a web server to run the application
* To see if the app running, http://localhost/app_dev.php/ or http://localhost/app_dev.php/demo/hello/Fabien

### Requirements

* You are free to use 3rd party authentication bundle or build your own bundle
* The login form should have server side validation
* All pages should be gated by the login page if the user is not login.
* You are free to use any http client to call out to Github's API
* Have a search field that allows searching for a GitHub user's repositories. See http://developer.github.com/v3/repos/#list-user-repositories for more info. Call the following API (where USER_NAME is the value typed into the search field):
```
https://api.github.com/users/USER_NAME/repos
```
* Once the search is clicked, the results should show a list of that user's public repositories with each item in a "name/number of watchers" format.
* The results should be in json format for the view to consume
* It's not a requirement to style the pages
* We expect to have unit test code coverage of your code

### Nice to Haves

* When a result is clicked, display an alert box with the repository's ID and the created_at time.
* Use AngularJS to display the repositories results
* Extended functionalities where you see fit.

### Deliverables

* Please fork this project on GitHub and add your code to the forked project.
* Update the README file to include the time you spent and anything else you wish to convey.
* Send the link to your forked GitHub project to your recruiter.

*Good luck!**
