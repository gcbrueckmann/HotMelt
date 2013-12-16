---
title: Routes Overview
layout: docs
---

Routes are declared through the interfaces provided by the `Route` class. HotMelt will automatically load `Site/routes.php`, so you should use that file to declare routes.

``` php
<?php
\HotMelt\Route::add('/^\/$/', 'MySite\\Blog::index', 'Index.html');
\HotMelt\Route::add('/^new-post$/', 'MySite\\Blog::newPost', 'NewPostEditor.html');
// This is the target of the post editor form in NewPostEditor.html.
// It only accepts POST requests.
\HotMelt\Route::add('/^new-post$/', 'MySite\\Blog::newPost', 'NewPostEditor.html', 'POST');
```

`Route::add()` takes these arguments:

- A regular expression to match against the request URI.
- A callable identifying the [action][actions-overview].
- The name of a [view][views-overview], which can be either a class name or the name of a [Twig][twig] template in `Site/Templates` (optional).
- A string or an array of strings identifying the valid HTTP methods for this route (optional).
- An array of options (optional). This is typically used for per-route configuration of middleware options, such as `Middleware\BasicAccessAuthentication`.

Routes are evaluated in the order they have been declared in. If you want to define the same options for multiple routes, you can bracket these in calls to `Route::pushDefaultOptions()` and `Route::popDefaultOptions()`.

[actions-overview]: ../actions
[views-overview]: ../views