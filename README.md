# servermon README

`servermon` is a small, simple, lightweight and easy to setup server monitoring
tool.

Written by [Denilson Sá](http://about.me/denilsonsa) denilsonsa@gmail.com

* [http://bitbucket.org/denilsonsa/servermon][bb]
* [http://github.com/denilsonsa/servermon][gh]

[bb]: http://bitbucket.org/denilsonsa/servermon
[gh]: http://github.com/denilsonsa/servermon

## Demo

[A simulation of the `servermon` web interface][demo] is available from the
`gh-pages` branch.

[demo]: http://denilsonsa.github.io/servermon/index.php.html
[ghp]: http://pages.github.com/

## Requirements

- Backend: bash, wget, ping
- Frontend: PHP (4 or newer), apache (or anything that runs PHP)

## Features

* Small, easy to configure and easy to install.
* Easy to deploy with Apache+PHP.
* Due to simple requirements, runs essentially everywhere.
* All logs are stored as plaintext files.
* Can monitor servers through `ping`, or by using `wget` to fetch an address
  (good to detect some kinds of errors that `ping` can't, or to probe servers
  that are not directly accessible from the web).
* Well-thought web frontend UI:
    * Sends cache-friendly headers, improving client-side caching while still
      setting a correct expire date, so it will not cache longer than needed.
    * Concise UI that shows all the relevant information in a very clear way.
    * Concise UI that loads quickly and will not waste all your mobile data.
    * Gracefully degrades even when using ancient `Opera Mini` versions. (this
      was a very important feature in 2006)
    * Gracefully degrades even when using `lynx` versions. (this is just silly,
      but neat)

## Non-features

* No database is used.
* No access control is provided.
* The code seems a bit messy, but that's because I'm looking at code that I
  wrote many years ago.

## History

I wrote this tool to solve my own problem: I wanted to know if my servers were
down, for how long, and if other servers were also affected.

Back in 2006, I was working as a sysadmin at a college lab and I was
responsible for a couple of Linux servers. However, the computer network of the
college was very unreliable, and very often our lab (and, thus, our servers)
lost connectivity to the Internet.

Whenever it happened, some professors contacted me. They asked if I knew about
the issue and if I knew why our servers were down. If it was just our servers,
then for sure I needed to act and fix the issue, but most of the time it was
just the college network, and I just had to wait.

Thus I needed a (quick) way to check the health of our servers, and also to
check if other servers from the college network were also down. And I had to be
able to check it from anywhere. Remember, in 2006 there were no smartphones, I
still had a very limited [Nokia 3100][nokia3100] with one of the first versions
of [Opera Mini][operamini], mobile Internet cost a few cents per kilobyte, and
I had a budget of a college student.

[nokia3100]: http://en.wikipedia.org/wiki/Nokia_3100
[operamini]: http://en.wikipedia.org/wiki/Opera_mini

Then `servermon` was born. It was hosted on my own home computer (running
Linux, up 24/7, connected through home DSL, and with a [dynamic DNS][ddns]
address). It was created because I was tired of people calling me about server
issues (mostly outside my power), and because I was tired of not having a clear
answer when they called me.

[ddns]: http://en.wikipedia.org/wiki/Dynamic_DNS
