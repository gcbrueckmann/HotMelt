---
title: Views Overview
layout: docs
---

Views render the data produced by an [action][actions-overview].

Views are implemented by subclassing the `View` class. But in most scenarios this is overkill. Usually you will use [Twig][twig] templates with HotMelt (which are implemented by the `TemplateView` class behind the scenes).

To use a template view, place a [Twig][twig] template file in the site templates directory (`Site/Templates`) and specify its name when [declaring a route][routes-overview], e.g. for an index template:

``` php
<?php
\HotMelt\Route::add('/^\/$/', 'MySite\\Site::index', 'Index.html');
```

If you want to configure Twig, e.g. by adding filters or functions, you can access the Twig environment used by HotMelt with `HotMelt\TemplateView::twig()`:

``` php
<?php
$twig = \HotMelt\TemplateView::twig();
$twig->addFilter(new \Twig_SimpleFilter('currency_format', function ($string) {
	return number_format(floatval($string), 2, ',', '.').' €';
}));
```


[actions-overview]: ../actions
[routes-overview]: ../routes
[twig]: https://github.com/fabpot/Twig