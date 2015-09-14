This application is a simple job queue that fetches data from a URL and store the results in a database.  The job queue exposes a REST API for adding jobs and checking their status / results.

Example:
Submit www.google.com via POST.
A job id is returned.
Ask for the status of the job id via GET.


URLS:
POST /jobs/     with the post data {'url': 'www.google.com'}
GET /jobs/id	to request the status, which is given as a JSON object {id:1, url:'text.com', html:'...'}
				Where html will contain the status of the request or the HTML if completed.
DELETE /jobs/id	remove a job from the queue


SETUP:
1) Apache should be set up to AllowOverride for this virtual host.  For that, my httpd.conf looks like this:
<VirtualHost *:88>
	DocumentRoot "C:/webDev/MassDrop/PhpRestQ"
</VirtualHost>
<Directory "C:/webDev/MassDrop/PhpRestQ">
	AllowOverride All
</Directory>

2) work.php should be running in the background to do the work...
For example, in the browser open http://localhost:88/work.php