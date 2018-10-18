<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Logger.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/Logger.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/
class Logger
{

	const L_ALL = 0;

	const L_DEBUG = 1;

	const L_TRACE = 2;

	const L_INFO = 3;

	const L_NOTICE = 4;

	const L_WARNING = 5;

	const L_FATAL = 6;

	private static $ARR_DESC = array (0 => 'ALL', 1 => 'DEBUG', 2 => 'TRACE', 3 => 'INFO', 
			4 => 'NOTICE', 5 => 'WARNING', 6 => 'FATAL' );

	private static $LOG_LEVEL = self::L_DEBUG;

	private static $ARR_BASIC = array ();

	private static $FILE = array ();

	private static $FORCE_FLUSH = false;

	public static function flush()
	{

		foreach ( self::$FILE as $file )
		{
			fflush ( $file );
		}
	}

	public static function addBasic($key, $value)
	{

		self::$ARR_BASIC [$key] = $value;
	}

	public static function init($filename, $level, $arrBasic = null, $forceFlush = false)
	{

		if (! isset ( self::$ARR_DESC [$level] ))
		{
			trigger_error ( "invalid level:$level" );
			return;
		}
		self::$LOG_LEVEL = $level;
		$dir = dirname ( $filename );
		if (! file_exists ( $dir ))
		{
			if (! mkdir ( $dir, 0755, true ))
			{
				trigger_error ( "create log file $filename failed, no permmission" );
				return;
			}
		}
		self::$FILE [0] = fopen ( $filename, 'a+' );
		if (empty ( self::$FILE [0] ))
		{
			trigger_error ( "create log file $filename failed, no disk space for permission" );
			self::$FILE = array ();
			return;
		}
		
		self::$FILE [1] = fopen ( $filename . '.wf', 'a+' );
		if (empty ( self::$FILE [1] ))
		{
			trigger_error ( "create log file $filename.wf failed, no disk space for permission" );
			self::$FILE = array ();
			return;
		}
		
		if (! empty ( $arrBasic ))
		{
			self::$ARR_BASIC = $arrBasic;
		}
		
		self::$FORCE_FLUSH = $forceFlush;
	}

	private static function checkPrintable(&$data, $key)
	{

		if (! is_string ( $data ))
		{
			return;
		}
		
		if (preg_match ( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', $data ))
		{
			$data = base64_encode ( $data );
		}
	}

	public static function log($level, $arrArg, $traceLevel = 1)
	{

		if ($level < self::$LOG_LEVEL || empty ( self::$FILE ) || empty ( $arrArg ))
		{
			return '';
		}
		
		$arrMicro = explode ( " ", microtime () );
		$content = '[' . date ( 'Ymd H:i:s ' );
		$content .= sprintf ( "%06d", intval ( 1000000 * $arrMicro [0] ) );
		$content .= '][';
		$content .= self::$ARR_DESC [$level];
		$content .= "]";
		foreach ( self::$ARR_BASIC as $key => $value )
		{
			$content .= "[$key:$value]";
		}
		
		$arrTrace = debug_backtrace ();
		if (isset ( $arrTrace [$traceLevel] ))
		{
			$line = $arrTrace [$traceLevel] ['line'];
			$file = $arrTrace [$traceLevel] ['file'];
			$file = substr ( $file, strlen ( ROOT ) + 1 );
			$content .= "[$file:$line]";
		}
		
		foreach ( $arrArg as $idx => $arg )
		{
			if ($arg instanceof BtstoreElement)
			{
				$arg = $arg->toArray ();
			}
			
			if (is_array ( $arg ))
			{
				array_walk_recursive ( $arg, array ('Logger', 'checkPrintable' ) );
				
				if (FrameworkConfig::DEBUG)
				{
					$data = var_export ( $arg, true );
				}
				else
				{
					$data = serialize ( $arg );
				}
				
				$arrArg [$idx] = $data;
			}
		}
		$ret = call_user_func_array ( 'sprintf', $arrArg );
		$content .= $ret;
		$content .= "\n";
		
		$file = self::$FILE [0];
		fputs ( $file, $content );
		if (self::$FORCE_FLUSH)
		{
			fflush ( $file );
		}
		
		if ($level > self::L_NOTICE)
		{
			$file = self::$FILE [1];
			fputs ( $file, $content );
			if (self::$FORCE_FLUSH)
			{
				fflush ( $file );
			}
		}
		
		return $ret;
	}

	public static function debug()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_DEBUG, $arrArg );
	}

	public static function trace()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_TRACE, $arrArg );
	}

	public static function info()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_INFO, $arrArg );
	}

	public static function notice()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_NOTICE, $arrArg );
	}

	public static function warning()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_WARNING, $arrArg );
	}

	public static function fatal()
	{

		$arrArg = func_get_args ();
		return self::log ( self::L_FATAL, $arrArg );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */