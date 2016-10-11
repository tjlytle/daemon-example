Building PHP Daemons & Long Running Processes
=============================================
This code is example daemons and long running processes. The various branches / pull requests show the process of 
creation from initial simplistic concept to a well formed long running process or daemon. This means that:
 
**The associated branches will be rebased against `master` any time an example is added / updated!**

Setup
-----
Other than the composer dependencies, you'll need:
- beanstalkd
- MySQL

The included vagrant configuration should take care of all setup:
    
    vagrant up
    vagrant ssh
    cd /vagrant

Tutorial
--------
The longer form hands on workshop (using all the examples) has been given at:
- ZendCon

Talk
----
Originally a local PHP meetup talk, the [shorter version][talk] has been given as a talk (using the twitter example) 
at these meetups / conferences:
- LVPHP
- php[tek]
- Nomad PHP
- LoneStar PHP
- OpenWest

[talk]: https://prezi.com/0l3a7q5dywc6/building-php-daemons-and-long-running-processes/