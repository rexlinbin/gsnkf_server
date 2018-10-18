<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Dummy.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/Dummy.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 本文件用于声明一些扩展实现的方法，以消除zend studio的warning
 *
 **/

/**
 * 提前结束fastcgi请求
 */
function fastcgi_finish_request()
{

}

class ClassDef
{

}

class Memcache
{

}

class Zookeeper
{

	/* if host is provided, attempt to connect */
	public function __construct($host = '', $watcher_cb = null, $recv_timeout = 10000)
	{

	}

	public function connect($host, $watcher_cb = null, $recv_timeout = 10000)
	{

	}

	public function create($path, $value, $acl, $flags = null)
	{

	}

	public function delete($path, $version = -1)
	{

	}

	public function set($path, $data, $version = -1, &$stat = null)
	{

	}

	public function get($path, $watcher_cb = null, &$stat = null, $max_size = 0)
	{

	}

	public function getChildren($path, $watcher_cb = null)
	{

	}

	public function exists($path, $watcher_cb = null)
	{

	}

	public function getAcl($path)
	{

	}

	public function setAcl($path, $version, $acls)
	{

	}

	public function getClientId()
	{

	}

	public function setWatcher($watcher_cb)
	{

	}

	public function getState()
	{

	}

	public function getRecvTimeout()
	{

	}

	public function addAuth($scheme, $cert, $completion_cb = null)
	{

	}

	public function isRecoverable()
	{

	}

	public function setLogFile($file)
	{

	} // TODO: might be able to set a stream like php://stderr or something

	
	public function close()
	{

	}

	public function getResultMessage()
	{

	}

	// static methods
	

	static public function setDebugLevel($level)
	{

	}

	static public function setDeterministicConnOrder($trueOrFalse)
	{

	}

}

/**
 * 获取btstore扩展配置中btstore.dir指定的文件夹下所有数据的指代对象
 * @return BtstoreElement
 */
function btstore_get()
{

}

/**
 * amfext模块的编码函数
 * @param mixed $data 要编码的数据
 * @param int $flag 设置
 * @return 编码的二进制数据
 */
function amf_encode($data, $flag = 0)
{

}

/**
 * amfext模块的解码函数
 * @param mixed $data 要解码的二进制数据
 * @param int $flag 设置
 * @return mixed 所代表的php类
 */
function amf_decode($data, $flag = 0)
{

}

/**
 * trie_filter扩展的替换方法，将字符串的敏感词替换成指定字符
 * @param string $string
 * @param string $replace
 * @return string
 */
function trie_filter_replace($string, $replace)
{

}

/**
 * trie_filter扩展的查找方法，在指定字符串中找到所有敏感词的位置
 * @param string $string
 */
function trie_filter_search($string)
{

}

function pk_single($pkData, $type)
{

}

function getItem($itemId)
{

}

function pcntl_fork()
{

}

function pcntl_waitpid()
{

}

class BtstoreElement
{

	/**
	 * 返回一个array，其代表当前对象所指代的array
	 * @return array
	 */
	public function toArray()
	{

	}
}

function memcache_connect()
{

}

function xdebug_disable()
{

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */