Wakeup Call
===========

For this example, allow a wakeup call to be scheduled in the future.

Starting Point
--------------
`cli.php` adds a call to the database with the following arguments:
- -p phone_number
- -n "Name of Person"
- -t "time of wake up call"
- -n "optional message"

`src/Service.php` exposes methods to add and list wakeup calls. It also provides a way to make calls as `queued` and 
get all calls not marked as such before a given `DateTime`.

`daemon.php` stubs out the request to Nexmo's API that will result in the call.

Goals
-----
- Connect the wakeup calls in the database, to `daemon.php`.
- Since making the phone call takes an API request, the solution must allow running multiple copies of `daemon.php`.
- Ensure that no call is made twice, and avoid awkward 'record locking' on the database. 