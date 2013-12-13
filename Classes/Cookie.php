<?php
/**
 * Defines the `\HotMelt\Cookie` class.
 */
namespace HotMelt;

/**
 * Provides an interace for manipulating HTTP cookies.
 * 
 * Create a cookie object and access its value with the `value` property.
 * Use `\HotMelt\Cookie::update()` to change its value and send the cookie back to the client,
 * or use `\HotMelt\Cookie::delete()` to delete the cookie.
 * 
 * @property-read mixed $value The value of the cookie. Use `\HotMelt/Cookie::update()` to change.
 */
class Cookie
{
	/**
	 * Initialize a cookie object.
	 * 
	 * @param string $name The name of the cookie.
	 * @param string $path The path on the server in which the cookie will be available on. If set to `'/'`, the cookie will be available within the entire domain. If set to `'/foo/'`, the cookie will only be available within the *\/foo/* directory and all sub-directories such as *\/foo/bar/* of $domain. The default value is the current directory that the cookie is being set in.
	 * @param string $domain The domain that the cookie is available to. Setting the domain to `'www.example.com'` will make the cookie available in the www subdomain and higher subdomains. Cookies available to a lower domain, such as `'example.com'` will be available to higher subdomains, such as `'www.example.com'`. Older browsers still implementing the deprecated RFC 2109 may require a leading `.` to match all subdomains.
	 */
	public function __construct($name, $path = '/', $domain = null)
	{
		$this->_name = $name;
		$this->_path = $path;
		$this->_domain = $domain;
	}
	
	/** @ignore */
	public function __get($key)
	{
		if (array_search($key, array('name', 'path', 'domain')) !== false) {
			return $this->{'_'.$key};
		} elseif ($key == 'value') {
			return $_COOKIE[$this->name];
		}
	}
	
	/** @ignore */
	public function __isset($key)
	{
		if ($key == 'value') {
			return isset($_COOKIE[$this->name]);
		}
	}
	
	/**
	 * Update the value of a cookie object.
	 * 
	 * @param mixed $value The value to set.
	 * @param int $time The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch. In other words, you'll most likely set this with the `time()` function plus the number of seconds before you want it to expire. Or you might use `mktime()`. `time()+60*60*24*30` will set the cookie to expire in 30 days. If set to `0`, or omitted, the cookie will expire at the end of the session (when the browser closes).
	 * @return void
	 */
	public function update($value, $time)
	{
		$args = array($this->name, $value, $time, $this->path);
		if ($this->domain) {
			$args[] = $this->domain;
		}
		call_user_func_array('setcookie', $args);
	}
	
	/**
	 * Delete a cookie (make it expire immediately).
	 * 
	 * @return void
	 */
	public function delete()
	{
		$this->update('', time() - 3600);
	}
}