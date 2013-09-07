<?php
namespace HotMelt;

class HTTP
{
	private static $HTTPStatusMessages = array(
		'202' => 'Accepted',
		'208' => 'Already Reported',
		'502' => 'Bad Gateway',
		'400' => 'Bad Request',
		'409' => 'Conflict',
		'100' => 'Continue',
		'201' => 'Created',
		'417' => 'Expectation Failed',
		'424' => 'Failed Dependency',
		'403' => 'Forbidden',
		'302' => 'Found',
		'504' => 'Gateway Timeout',
		'410' => 'Gone',
		'505' => 'HTTP Version Not Supported',
		'226' => 'IM Used',
		'507' => 'Insufficient Storage',
		'500' => 'Internal Server Error',
		'411' => 'Length Required',
		'423' => 'Locked',
		'508' => 'Loop Detected',
		'405' => 'Method Not Allowed',
		'301' => 'Moved Permanently',
		'207' => 'Multi-Status',
		'300' => 'Multiple Choices',
		'511' => 'Network Authentication Required',
		'204' => 'No Content',
		'203' => 'Non-Authoritative Information',
		'406' => 'Not Acceptable',
		'510' => 'Not Extended',
		'404' => 'Not Found',
		'501' => 'Not Implemented',
		'304' => 'Not Modified',
		'200' => 'OK',
		'206' => 'Partial Content',
		'402' => 'Payment Required',
		'308' => 'Permanent Redirect',
		'412' => 'Precondition Failed',
		'428' => 'Precondition Required',
		'102' => 'Processing',
		'407' => 'Proxy Authentication Required',
		'413' => 'Request Entity Too Large',
		'431' => 'Request Header Fields Too Large',
		'408' => 'Request Timeout',
		'414' => 'Request-URI Too Long',
		'416' => 'Requested Range Not Satisfiable',
		'306' => 'Reserved',
		'205' => 'Reset Content',
		'303' => 'See Other',
		'503' => 'Service Unavailable',
		'101' => 'Switching Protocols',
		'307' => 'Temporary Redirect',
		'429' => 'Too Many Requests',
		'401' => 'Unauthorized',
		'422' => 'Unprocessable Entity',
		'415' => 'Unsupported Media Type',
		'426' => 'Upgrade Required',
		'305' => 'Use Proxy',
		'506' => 'Variant Also Negotiates (Experimental)'
	);
	
	public static function statusMessage($statusCode)
	{
		return self::$HTTPStatusMessages[$statusCode];
	}
	
	public static function statusHeader($statusCode)
	{
		header($_SERVER['SERVER_PROTOCOL']." $statusCode ".self::statusMessage($statusCode));
	}
}