<?php
namespace HotMelt;

class Cookie
{
	public function __construct($name, $path = '/', $domain = null)
	{
		$this->_name = $name;
		$this->_path = $path;
		$this->_domain = $domain;
	}
	
	public function __get($key)
	{
		if (array_search($key, array('name', 'path', 'domain')) !== false) {
			return $this->{'_'.$key};
		} elseif ($key == 'value') {
			return $_COOKIE[$this->name];
		}
	}
	
	public function __isset($key)
	{
		if ($key == 'value') {
			return isset($_COOKIE[$this->name]);
		}
	}
	
	public function update($value, $time)
	{
		$args = array($this->name, $value, $time, $this->path);
		if ($this->domain) {
			$args[] = $this->domain;
		}
		call_user_func_array('setcookie', $args);
	}
	
	public function delete()
	{
		$this->update('', time() - 3600);
	}
}