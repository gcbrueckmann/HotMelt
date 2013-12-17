---
title: Home
layout: default
no_title: true
---

# What Is HotMelt?

HotMelt is a lightweight framework for implementing model-view-controller semantics. That means that HotMelt will help you bootstrap a website or web service in no time. Key selling points include:

- Content type-aware view negotiation: Server different content types with different views through a single [route](docs/overview/routes)/URI depending on the HTTP `Content-Type` header sent with the request.
- Template support powered by [Twig][twig].
- A thin [object-relational mapper layer][orm]: Things like `\MySite\Blog\Post::findByTag($requestedTag)` work without any boilerplate code (except the class definition, of course).
- [Middleware](docs/api/classes/HotMelt.Middleware.html) support so you can easily extend HotMelt.
- Host name-based [configuration](docs/overview/configuration).

# How Mature Is HotMelt?

HotMelt is currently used in production and the features already implemented should work as advertised. But while HotMelt is meant to stay lightweight, there are still a few features on the to-do list, e.g.:

- [Composer][composer] support for easier setup
- Automagic support for relationships in the [ORM layer][orm]

# Where Do I Go from Here?

If you want to start playing with HotMelt, head over to the [Creating Your First HotMelt Site](docs/first-site) guide. Or check out the [API reference][api] or [source at GitHub][source], if you’re into the gory details.

# Who Are the Authors?

The HotMelt project has been initiated by [Georg C. Brückmann][gcb] but is open to new contributors. Feel free to [fork the source](https://github.com/gcbrueckmann/HotMelt/fork) to experiment and send us a pull request when you want to contribute bugfixes, improvements, or new features.

[api]: docs/api
[composer]: http://getcomposer.org/
[docs]: docs
[source]: https://github.com/gcbrueckmann/HotMelt
[gcb]: http://gcbrueckmann.de
[orm]: docs/api/classes/HotMelt.PersistentObject.html
[twig]: https://github.com/fabpot/Twig