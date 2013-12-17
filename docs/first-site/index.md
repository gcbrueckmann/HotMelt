---
title: Creating Your First HotMelt Site
layout: docs
---

This tutorial walks you through installing HotMelt and using URI mapping to serve web pages. You will learn how to process request data and render response data into a view. You will also learn how to handle HTTP errors using HotMelt and how to redirect visitors to another URL using the built-in tools.

# Installation

Adding HotMelt to your site is as easy as cloning this repository into a directory of your website.

## Create the Website Repository

Create a repository for your website:

``` bash
~$ mkdir MyWebsite
~$ cd MyWebsite
~/MyWebsite$ git init
```

## Add the HotMelt Submodule

Add HotMelt as a submodule. You can use any name for the submodule, but we will stick with `HotMelt` for this example.

``` bash
~/MyWebsite$ git submodule add git@github.com:gcbrueckmann/HotMelt.git HotMelt
~/MyWebsite$ cd HotMelt
~/MyWebsite/HotMelt$ git submodule update --init
```

## Create the Site Directory

HotMelt assumes that certain files be placed in specific locations. You should create a directory named `Site` alongside the `HotMelt` directory that the HotMelt submodule lives in.

``` bash
~/MyWebsite/HotMelt$ cd ..
~/MyWebsite$ mkdir Site
```

## Dispatch Requests to HotMelt

Create an `.htaccess` file (in the repository's root directory) with the following *mod_redwrite* rules:

``` apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^HotMelt/dispatch\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /HotMelt/dispatch.php [L,QSA]
RewriteRule ^$ /HotMelt/dispatch.php [L,QSA]
</IfModule>
```

This will redirect all requests for non-existing files to the HotMelt dispatch machinery.

# Configure Your Site

First, prepare the `init.php`, `autoload.php` and `routes.php` files (that is one command spanning three lines):

``` bash
~/MyWebsite$ echo '<?php
namespace MySite;
' | tee Site/{init,autoload,routes}.php > /dev/null
```

Configuration options can be set in `Site/config.php`. Additionally, HotMelt will also load files matching `Site/config-<DOMAIN>.php`, where `<DOMAIN>` is the host name for the request. If the request's host name begins with `www.`, but there is no configuration file for this domain, HotMelt will also look for an appropriate configuration file without this prefix. So for a request to `www.example.com` HotMelt will try to load these configuration files:

- `Site/config.php`
- `Site/config-www.example.com.php`, or if that doesn't exist:
- `Site/config-example.com.php`
    
The file `Site/init.php` is loaded by the dispatch machinery as soon as HotMelt is ready, but before a request has actually been processed. You can use this file to configure middleware and other requirements of your site.

You may also add autoload logic to `Site/autoload.php`. This file is loaded by HotMelt's own `autoload.php` as part of the initialization stage.

# Implement Your First Route

For every URI that you wish your site to handle you will need:

- **an action** processing the request and producing data,
- **a view** that renders that data produced by the action, and
- **a route** that tells HotMelt to use this action and view for the URI.

## Implement an Action

Actions are implemented as callables, i.e. functions, or static or instance methods. Action callables receive request information and return a data array that will be passed to the view.

For larger codebases it makes sense to group actions in classes and namespaces, so that is what we will do right from the start (even if our demo site cannot exactly be considered a large codebase):

``` php
<?php
namespace MySite;

class Site
{
    public static function index($request, $route, $variables)
    {
        return array();
    }
}
```

## Implement a View

We will make do with a very simple view, an HTML template file using the [Twig][twig] template language.

``` html
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
    <title>My Site</title>
</head>
<body>
    <p>hello, world</p>
</body>
```

## Add a Route to the Routing Table

In order for HotMelt to know how to map your action and view template to a URI, update `Site/routes.php`:

``` php
<?php
namespace MySite;

\HotMelt\Route::add('/^\/$/', 'MySite\\Site::index', 'Index.html');
```

# Make Things a Little More Interesting

Now that you have your first HotMelt site working, why not make things a little more interesting by adding dynamic behaviour?

Let's make it so that you can change whom to say *hello* to. We will use good ol' query strings for that.

## Using Query Strings

First, add a form to the view template (`Site/Templates/Index.html`):

``` html
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
    <title>My Site</title>
</head>
<body>
    <p>hello, world</p>
    <form action="{{ request.url }}" method="get" accept-charset="utf-8">
        <p style="text-align: center;">
            <label for="whom">Say <em>hello</em> to:</label>&nbsp;<input type="text" name="whom" value="" id="whom">
            <input type="submit" value="Go &rarr;">
        </p>
    </form>
</body>
```

Then update the action implementation in `Site/Classes/Site.php`:

``` php
<?php
namespace MySite;

class Site
{
    public static function index($request, $route, $variables)
    {
        return array(
        	'whom' => array_key_exists('whom', $_GET) ? $_GET['whom'] : false
        );
    }
}
```

## Using URI Components

While we have approached things in a different order before, it is usually best to design your site top-down, starting with the user interface. For a website, URLs and URIs are a part of this user interface, so let's pay a little more attention to what the user sees in their browser's location field.

The query strings we've used until now are a reliable tool, but they make URLs look rather ugly. Also, it would probably be nice if you could have your site use greetings other than *hello.* Conceptually what we want to achieve is that URIs like `/greetings/konichiwa` and `/greetings/ahoi` show a page like before, only with *konichiwa* and *ahoi* substituted for *hello.* Also, we do not want to hard-code every possible greeting. So what we will do is declare a route with a placeholder for the greeting in `Site/routes.php`. And then we want all requests for `/` redirect to the default greeting of *hello.*

First, add a new route to `Site/routes.php` and replace the index route you previously declared. We also have to declare the error because it will be used when we redirect from the `index` action that matches requests for `/`.

``` php
namespace MySite;

\HotMelt\Route::error('MySite\\Site::error', 'Error.html');

\HotMelt\Route::add('/^\/$/', 'MySite\\Site::index');
\HotMelt\Route::add('/^\/greetings\/(?<greeting>[^\/]+)\/?$/', 'MySite\\Site::greeting', 'Greeting.html');
```

Of course, you have to update your implementation of the `MySite\Site` class as well:

``` php
<?php
namespace MySite;

class Site
{
    public static function index($request, $route, $variables)
    {
		throw new \HotMelt\HTTPErrorException(302, 'Redirecting to default greeting...', array('Location' => "{$request->rootURL}/greetings/hello"));
    }
    	
    public static function greeting($request, $route, $variables)
    {
        return array(
        	'greeting' => $variables['greeting'],
        	'whom' => array_key_exists('whom', $_GET) ? $_GET['whom'] : false
        );
    }
	
    public static function error($request, $route, $exception)
    {
		return array(
		'httpStatusCode' => $exception->statusCode,
		'httpStatusMessage' => \HotMelt\HTTP::statusMessage($exception->statusCode),
		'errorMessage' => $exception->getMessage()
		);
    }
}
```

There are three things of note here:

- The `index` method does not return data. That is perfectly alright, because it throws a special type of exception for redirecting to the default greeting instead.
- The `greeting` method uses the named capture `greeting` from the regular expression of the greeting route. These captures are passed in the `$variables` parameter.
- The `error` method does not take a `$variables` parameter but an `$exception` parameter instead. That is a special convention for the action of an error route.

Now you need to add a template for greeting route and one for the error route you have just declared.

`Site/Templates/Greeting.html`:

``` html
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
    <title>My Site: {{ "{{ greeting" }} }}</title>
</head>
<body>
    <p>{{ "{{ greeting" }} }}, {{ "{{ whom" }} }}</p>
    <form action="{{ "{{ request.url" }} }}" method="get" accept-charset="utf-8">
        <p style="text-align: center;">
            <label for="whom">Say <em>{{ greeting }}</em> to:</label>&nbsp;<input type="text" name="whom" value="{{ whom }}" id="whom">
            <input type="submit" value="Go &rarr;">
        </p>
    </form>
</body>
```

`Site/Templates/Error.html`:

``` html
<!DOCTYPE html>
<head>
	<meta charset="utf-8">
    <title>My Site: Error {{ "{{ httpStatusCode" }} }} {{ "{{ httpStatusMessage" }} }}</title>
</head>
<body>
    <h1>Error {{ "{{ httpStatusCode" }} }} {{ "{{ httpStatusMessage" }} }}</h1>
	<p>{{ "{{ errorMessage" }} }}</p>
</body>
```

You no longer need the index template `Site/Templates/Index.html`, so you can go ahead and delete it.

# Summary

You have learned how to set up a basic HotMelt-powered website and implemented an action, handling both query parameters as well as parsing URI components and rendering response data into a view. You have also learned how to handle HTTP errors using HotMelt and how to redirect to another URL using the built-in tools.

[twig]: https://github.com/fabpot/Twig