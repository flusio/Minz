# Minz

Minz is yet another PHP 7.2+ framework. **It has no big ambitions and is
designed to my personal purposes.**

What I like in Minz is:

- I know its code perfectly (I wrote it)
- it's powerful enough for my needs
- it has a relative small size
- it grows ONLY when I need it (the consequence is basic features might be
  missing)
- it's easy to perform integration tests
- it provides a common interface for both Web and CLI requests
- it has no dependencies (at the moment)
- I learnt a LOT by coding it and it was quite fun

## Contributing

I'm not intended to develop features that I have no uses (sorry!) That being
said, forks are very welcome and I would be happy to link to yours if it brings
value over my own codebase.

The reason for this choice is that I consider I have to control most of the
code I rely on. Minz is a framework to ship next to the rest of your codebase,
not as an external dependance. That's also the reason I don't plan to package
it for Composer.

However, I'll be glad to accept patches for bugs and security flaws.

## Install

Download the repository into your project and include the autoload file. For
instance, if you've put Minz under a `lib/` folder:

```php
<?php

include(__DIR__ . '/lib/Minz/autoload.php');
```

You can also keep only the `src/` folder files and change the autoload file.
That's what it is designed for: adapt to your needs.

## A bit of Minz-story

When I was a student, my professors introduced me to the [Zend Framework](https://framework.zend.com/)
(now [Laminas project](https://getlaminas.org/)). At the time, I felt it very
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

## License

Minz is licensed under [AGPL 3](./LICENSE.txt).
