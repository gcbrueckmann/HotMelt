# HotMelt

HotMelt is a lightweight framework for implementing model-view-controller semantics. It has been tested in production.

## Installation

HotMelt doesn't yet support [Composer](http://getcomposer.org/), but adding it to your site is as easy as cloning this repository into a directory of your website.

### Create Website Repository

Create a repository for your website:

    $ mkdir MyWebsite
	$ cd MyWebsite
	$ git init

### Add HotMelt Submodule

Add HotMelt as a submodule. You can use any name for the submodule, but we will stick with `HotMelt` for this example.

    $ git submodule add git@github.com:gcbrueckmann/HotMelt.git HotMelt
    $ cd HotMelt
    $ git submodule update --init

### Create the Site Directory

HotMelt assumes that certain files be placed in specific locations. You should create a directory named `Site` alongside the `HotMelt` directory that the HotMelt submodule lives in.

    $ mkdir Site

### Dispatch Requests to HotMelt

Create an `.htaccess` file (in the repository's root directory) with the following *mod_redwrite* rules:

    <IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^HotMelt/dispatch\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /HotMelt/dispatch.php [L,QSA]
    RewriteRule ^$ /HotMelt/dispatch.php [L,QSA]
    </IfModule>

This will redirect all requests for non-existing files to the HotMelt dispatch machinery.

### Create a Site Configuration

Configuration options can be set in `Site/config.php`. Additionally, HotMelt will also load files matching `Site/config-<DOMAIN>.php`, where `<DOMAIN>` is the host name for the request. If the request's host name begins with `www.`, but there is no configuration file for this domain, HotMelt will also look for an appropriate configuration file without this prefix. So for a request to `www.example.com` HotMelt will try to load these configuration files:

- `Site/config.php`
- `Site/config-www.example.com.php`, or if that doesn't exist:
- `Site/config-example.com.php`
    
The file `Site/init.php` is loaded by the dispatch machinery as soon as HotMelt is ready, but before a request has actually been processed. You can use this file to configure middleware and other requirements of your site.

    <?php
    \HotMelt\Middleware::add('HotMelt\\Middleware\\BasicAccessAuthentication', 'Password-Protected Area', function ($user, $password) {
        // Grants access to for any non-empty user name/password combination
        // where user name and password are identical.
        return !empty($user) && $password == $user;
    });

You may also add autoload logic to `Site/autoload.php`. This file is loaded by HotMelt's own `autoload.php` as part of the initialization stage.

## Usage

Routes link URL patterns to controllers and views.

### Implementing Controllers

Controllers are implemented as callables, i.e. functions, or static or instance methods. Controller callables receive request information and return a data array that will be passed to the view.

For larger codebases it makes sense to group controllers in classes and namespaces:

    <?php
    namespace MySite;
    
    class Blog
    {
        public static function index($request, $route, $variables)
        {
            $page = isset('page', $variables) $variables['page'] : 1;
            return array(
                'posts' => self::getPosts($page)
            );
        }
        
        public static function newPost($request, $route, $variables)
        {
            if (!self::userCanCreatePosts()) {
                throw new \HotMelt\HTTPErrorException(403, 'You are not allowed to create a new post.');
            }
            return array();
        }
        
        ...
    }

### Implementing Views

Views are implemented by subclassing the `View` class. But in most scenarios this is overkill. Usually you will use [Twig][twig] templates with HotMelt (which are implemented by the `TemplateView` class behind the scenes).

### Declaring Routes

Routes are declared through the interfaces provided by the `Route` class. HotMelt will automatically load `Site/routes.php`, so you should use that file to declare routes.

    <?php
    \HotMelt\Route::add('/^\/$/', 'MySite\\Blog::index', 'Index.html');
    \HotMelt\Route::add('/^new-post$/', 'MySite\\Blog::newPost', 'NewPostEditor.html');
    // This is the target of the post editor form in NewPostEditor.html.
    // It only accepts POST requests.
    \HotMelt\Route::add('/^new-post$/', 'MySite\\Blog::newPost', 'NewPostEditor.html', 'POST');

`Route::add()` takes these arguments:

- A regular expression to match against the request URI.
- A callable identifying the controller.
- The name of a view, which can be either a class name or the name of a [Twig][twig] template in `Site/Templates` (optional).
- A string or an array of strings identifying the valid HTTP methods for this route (optional).
- An array of options (optional). This is typically used for per-route configuration of middleware options, such as `Middleware\BasicAccessAuthentication`.

Routes are evaluated in the order they have been declared in. If you want to define the same options for multiple routes, you can bracket these in calls to `Route::pushDefaultOptions()` and `Route::popDefaultOptions()`.

## To Do

- [ ] Add full documentation
- [ ] Add [Composer](http://getcomposer.org/) support

## Credits

The HotMelt project is lead by [Georg C. Brückmann][gcb], but it relies to a great extent on the following dependencies:

- [Twig][twig] by the Twig Team for providing template support
- [Inflector][inflector] by Olivier Laviale for supporting a thin object-relational mapper
- [Negotiation][negotiation] by William Durand for negotiating what content type to server for an HTTP request

# License

Negotiation is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.

[gcb]: http://gcbrueckmann.de
[twig]: https://github.com/fabpot/Twig
[inflector]: https://github.com/ICanBoogie/Inflector
[negotiation]: https://github.com/willdurand/Negotiation