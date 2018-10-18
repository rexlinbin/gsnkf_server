<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: TrieFilter.class.php 176731 2015-06-05 04:13:07Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/TrieFilter.class.php $
 * @author $Author: BaoguoMeng $(jhd@babeltime.com)
 * @date $Date: 2015-06-05 04:13:07 +0000 (Fri, 05 Jun 2015) $
 * @version $Revision: 176731 $
 * @brief
 *
 **/

/**
 * class TrieFilter 敏感词PHP extension封装类
 *
 * @author HaidongJia
 *
 * @version 1.0.0
 */
class TrieFilter
{
	const ENCODING	=	"UTF-8";

	/**
	 * replace 将所有的敏感词替换成$replace, 默认为"*"
	 *
	 * @param string $string
	 *
	 * @return string $string
	 *
	 * @throws Exception 如果扩展没有加载,则throw Exception
	 */
	public static function replace($string, $replace = "*")
	{
		if ( !function_exists("trie_filter_replace") )
			throw new Exception("no function trie_filter_replace");
		else
			return trie_filter_replace($string, $replace);
	}

	/**
	 * mb_replace 将所有的敏感词替换成$replace,多字节字符将会替换成1个字符 默认为"*"
	 *
	 * @param string $string
	 *
	 * @return string $string
	 *
	 * @throws Exception 如果扩展没有加载,则throw Exception
	 */
	public static function mb_replace($string, $replace = "*")
	{
		if ( !function_exists("trie_filter_search") )
			throw new Exception("no function trie_filter_search");
		$index = trie_filter_search($string);
		$replace_string = '';
		$str_length = strlen($string);
		$start = 0;
		$length = 0;
		for ( $i = 0; $i < count($index); )
		{
			if ( $start+$length < $index[$i] )
			{
				$replace_string .= substr($string, $start+$length, $index[$i]-$start-$length);
			}
			$start = $index[$i++];
			$length = $index[$i++];
			$sub = substr($string, $start, $length);
			$mb_length = mb_strlen($sub, TrieFilter::ENCODING);
			$replace_string .= str_repeat($replace, $mb_length);
		}
		if ( $start + $length < $str_length )
		{
			$replace_string .= substr($string, $start+$length, $str_length-$start-$length);
		}
		return $replace_string;
	}

	/**
	 * search 查找所有的敏感词
	 *
	 * @param string $string
	 *
	 * @return array(array(int, int)) (offset, length)数组         不是数组的数组，是形如 array(offset, length, offset, length, ......) modify by mengbaoguo 20150605
	 *
	 * @throws Exception 如果扩展没有加载,则throw Exception
	 */
	public static function search($string)
	{
		if ( !function_exists("trie_filter_search") )
			throw new Exception("no function trie_filter_search");
		else
			return trie_filter_search($string);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */