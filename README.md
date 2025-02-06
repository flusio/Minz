# Minz

Minz is yet another PHP 8.2+ framework. **It has no big ambitions and it is
designed with my personal needs in mind.**

What I like in Minz is:

- I know the code perfectly (I wrote it);
- it's powerful enough for my needs;
- it has a relative small size;
- it grows ONLY when I need it;
- it's easy to perform integration tests;
- it provides a common interface for both Web and CLI requests;
- it has very few dependencies (only one at the moment, i.e. PHPMailer);
- I learnt a LOT by coding it and it was quite fun.

## Install

You should use [Composer](https://getcomposer.org) to install Minz.

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

You'll find more documentation directly in the source files.

## Contributing

I'm not intended to develop features that I have no uses (sorry!) That being
said, forks are very welcome and I would be happy to link to yours if it brings
value over my own codebase.

However, I'll be glad to accept patches for bugs and security flaws.

[See the documentation to develop Minz.](/docs/development.md)

## Minz in the real world

Today, I use Minz in several projects:

- [Flus](https://github.com/flusio/Flus): a feed aggregator and social bookmarking tool;
- [flus.fr](https://github.com/flusio/flus.fr): the website of the Web service I provide for Flus;
- [taust](https://github.com/flusio/taust): a monitoring system easy to setup (provided as a proof of concept);
- [Webubbub](https://github.com/flusio/Webubbub): a simple [WebSub](https://www.w3.org/TR/websub/) hub.

## Last question: should you use Minz?

Probably not for your own projects. Minz was designed for my needs.

However, because I tried to keep the codebase away from too many levels of
abstraction, it can help you to learn how a framework works. I think Minz can
be great for educational purposes.

## License

Minz is licensed under [AGPL 3](./LICENSE.txt).
