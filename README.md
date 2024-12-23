# Minz

Minz is yet another PHP 8.2+ framework. **It has no big ambitions and it is
designed with my personal needs in mind.**

What I like in Minz is:

- I know the code perfectly (I wrote it);
- it's powerful enough for my needs;
- it has a relative small size;
- it grows ONLY when I need it (the consequence is basic features might be missing);
- it's easy to perform integration tests;
- it provides a common interface for both Web and CLI requests;
- it has very few dependencies (only one at the moment, i.e. PHPMailer);
- I learnt a LOT by coding it and it was quite fun.

## Install

You must use [Composer](https://getcomposer.org) to install Minz.

Run:

```console
$ composer require flus/minz
```

Then, in your code:

```php
<?php

require '/path/to/vendor/autoload.php';
```

## Guide

- [Getting started: setup a project](/docs/getting_started.md)
- [An overview of Minz classes](/docs/overview.md)

## Contributing

I'm not intended to develop features that I have no uses (sorry!) That being
said, forks are very welcome and I would be happy to link to yours if it brings
value over my own codebase.

However, I'll be glad to accept patches for bugs and security flaws.

[See the documentation to develop Minz.](/docs/development.md)

## A bit of Minz-story

When I was a student, my professors introduced me to the [Zend Framework](https://framework.zend.com/)
(now [Laminas project](https://getlaminas.org/)). At the time, it was very
frustrating: too big, too abstracted and complicated for the young student I
was. I've finally been able to use it, but I wasn't able to explain "how"
things were working.

That's when I started the first "Minz" project (for "Minz Is Not Zend", very
inspired). Its first repository [is still visible on GitHub](https://github.com/marienfressinaud/MINZ),
but I urge you not to judge the code quality! I learnt how a framework could
work and I've got reconciliated with Zend (well, sort of… I've never used it
again). I used Minz, first of its name, for a bunch of projects; the most (and
only) known today is [FreshRSS](https://github.com/FreshRSS/FreshRSS). It's
been hacked a lot since then, but the initial design is still there, in this
RSS aggregator.

Almost 10 years later, I started a new PHP project ([Webubbub](https://github.com/flusio/Webubbub))
but I felt a bit rusty and I wanted to apply some ideas I had in mind for years
concerning the architecture of Web applications. And that's how I ended up
developing another PHP framework. I hope the years of experience are visible in
this repository, but please remember I'm not a PHP expert (I mainly used Python
and Ruby these last years).

It's still fun to develop a framework from scratch… and it takes some time too.

## Minz in the real world

Today, I use Minz in several projects:

- [Flus](https://github.com/flusio/Flus): a feed aggregator and social bookmarking tool;
- [flus.fr](https://github.com/flusio/flus.fr): the website of the Web service I provide for Flus;
- [taust](https://github.com/flusio/taust): a monitoring system easy to setup (provided as a proof of concept);
- [Webubbub](https://github.com/flusio/Webubbub): a simple [WebSub](https://www.w3.org/TR/websub/) hub.

## License

Minz is licensed under [AGPL 3](./LICENSE.txt).
