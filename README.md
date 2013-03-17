Who unfollows me on Twitter?
============================

Track your twitter unfollowers with the whounfollowsmeontwitter script(s) and get an email notification.

Installation
------------

In your shell (terminal) download the scripts as follows

```
# we assume you want it residing in ~/crons
git clone git://github.com/slopjong/whounfollowsmeontwitter.git ~/crons/whounfollowsmeontwitter
```

Now it's configuration time. Rename `config.sample.php` to `config.php` and define

* **to_mail**, the email address to which the notification should be sent to
* **from_mail**, the email address that you'd like to see as the sender
* **screen_name**, your twitter user name

As the last step run `crontab -e` and add the following line.

```
* * */1 * * cd ~/crons/whounfollowsmeontwitter && php -f index.php
```

This will check your followers list once a day. With each execution of the whounfollowsmeontwitter script two requests are made.

When you install whounfollowsmeontwitter on a server where other twitter clients are running keep in mind that your host will be blocked when exceeding the rate of 150 requests per hour in total.

Notes
-----

The script works with public accounts only. If you wish support for private accounts either fork the repository and contribute or write a new issue with your feature request.
