---
title: Configuration Overview
layout: docs
---

Configuration options are managed by [`HotMelt\Config`][Config-api], a special class that allows arbitrary static method overloading. Any option that is also a valid PHP method name can be read and written this way, e.g. if you have an option named `fooGranularity`:

```php
<?php

// To set option, call method with new value.
\HotMelt\Config::fooGranularity(1.23);

// To get option, call method without any parameters.
\HotMelt\Config::fooGranularity(); // 1.23
```

Alternatively, you can use the methods `HotMelt\Config::get()` and `HotMelt\Config::set()` to read and write options:

```php
<?php

// Set option
\HotMelt\Config::set('fooGranularity', 1.23);

// Get option
\HotMelt\Config::get('fooGranularity'); // 1.23
```

Configuration options can be set in `Site/config.php`, which will be loaded automatically along with the `HotMelt\Config` class. Additionally, HotMelt will also load files matching `Site/config-<DOMAIN>.php`, where `<DOMAIN>` is the host name for the request. If the request's host name begins with `www.`, but there is no configuration file for this domain, HotMelt will also look for an appropriate configuration file without this prefix. So for a request to `www.example.com` HotMelt will try to load these configuration files:

- `Site/config.php`
- `Site/config-www.example.com.php`, or if that doesn't exist:
- `Site/config-example.com.php`

[Config-api]: ../../api/classes/HotMelt.Config.html