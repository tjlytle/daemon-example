Building PHP Daemons & Long Running Processes
=============================================
This code is example daemons and long running processes related to the talk / workshop. The various branches show the 
process of creation from initial simplistic concept to a well formed long running process or daemon. This means that:
 
**The associated branches will be rebased against `master` any time an example is added / updated!**

Setup
-----
If you're attending this as a workshop (or maybe you just want to take the examples for a spin), you should have these
setup before hand:
- A [Nexmo][nexmo] Account 
- Twitter [OAuth Tokens][twitter] 
- [beanstalkd][beanstalkd]
- MySQL (or MariaDB)

Since you generally learn better when you're comfortable, you can certainly just use your local development environment
if you're comfortable with installing those few dependencies. However, to make it easy, the included [vagrant 
configuration](./Vagrantfile) and [bootstrap script](./vagrant/bootstrap.sh) should take care of all setup without any 
change to your local system:
    
    vagrant up
    vagrant ssh
    cd /vagrant
    
_For how to install vagrant, visit the [official install guide][vagrant]. You'll also need [VirtualBox][virtualbox] as
this vagrant 'box' is a VirtualBox image._

**If you have any problems setting this up prior to the workshop**: [create an issue](../../issues/new), ping 
[`tjlytle`][t] on Twitter, or send me an email (_my name is `tim` and I own `timlytle.net`, I'm sure you can figure 
it out_).

Configuration
-------------
Edit [`config.php.dist`](./config.php.dist) and add your twitter oauth tokens, as well as your Nexmo credentials. If 
you're not using vagrant, you may also need to update the database credentials. Once edited, rename to `config.php`.

Tutorial
--------
The longer form hands on workshop (using all the examples) will be given at:
- ZendCon 2016

Talk
----
Originally a local PHP meetup talk, the [shorter version][talk] has been given as a talk (using the twitter example) 
at these meetups / conferences:
- LVPHP
- php[tek] 2015
- Nomad PHP 
- LoneStar PHP 2016
- OpenWest 2016

[talk]: https://prezi.com/0l3a7q5dywc6/building-php-daemons-and-long-running-processes/
[nexmo]: https://dashboard.nexmo.com/sign-up?utm_source=DEV_REL&utm_medium=github&utm_campaign=tjlytle/daemon-example
[beanstalkd]: http://kr.github.io/beanstalkd/
[twitter]: https://dev.twitter.com/oauth/overview/application-owner-access-tokens
[vagrant]: https://www.vagrantup.com/docs/installation/
[virtualbox]: https://www.vagrantup.com/docs/virtualbox/
[t]: https://twitter.com/tjlytle