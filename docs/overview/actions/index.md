---
title: Actions Overview
layout: docs
---

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

Action implementations take three parameters:

- `$request`, an instance of `HotMelt\Request`
- `$route`, an instance of `HotMelt\Route`
- `$variables`, an array containing the matches (as returned from `preg_match()`)

The matches passed in `$variables` have already been URL-decoded.

Action implementations must return a data array. This data will be passed to the rendering view.

**Site/routes.php:**

``` php
<?php
// ...

\HotMelt\Route::add('/^\/blog\/posts\/(?<slug>[^\/]+)\/?$/', 'MySite\\Blog::singlePost', 'SinglePost.html');

// ...
```

**Site/Classes/Blog.php:**

``` php
<?php
namespace MySite;

class Blog
{
    // ...
    
    public static function singlePost($request, $route, $variables)
    {
        $slug = $variables['slug'];
        $post = BlogPost::findBySlug($slug, 1)[0];
        return array(
            'post': $post
        );
    }
    
    // ...
}
```
