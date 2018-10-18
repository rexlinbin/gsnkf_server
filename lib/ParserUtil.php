<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ParserUtil.php 91569 2014-02-26 13:37:21Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/ParserUtil.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2014-02-26 13:37:21 +0000 (Wed, 26 Feb 2014) $
 * @version $Revision: 91569 $
 * @brief 
 *  
 **/


/**
 * 解析btstore的异常处理函数
 */
function errorHandler($errcode, $errstr, $errfile, $errline, $errcontext)
{
	if (! $errcode )
	{
		return true;
	}

	echo "ERRO:[$errfile][$errline] $errstr\n";
	exit ( 0 );
}


set_error_handler ( 'errorHandler' );



function array2Int($array)
{
	foreach ( $array as $key => $value )
	{
		if(intval($value) != $value)
		{
			trigger_error( "invalid conf.key:$key, value:$value");
		}
		$array[$key] = intval($value);
	}
	return $array;
}


/**
 * 将一个字符串按照指定分隔符分割成对应的字符串
 * @param string $str
 * @param string $delimiter
 */
function str2Array($str, $delimiter = ',')
{
	if(  trim($str) == '' )
	{
		return array();
	}
	return explode($delimiter, $str);
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */