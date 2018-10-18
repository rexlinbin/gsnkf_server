<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimpleBtstore.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/SimpleBtstore.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/
class BtstoreRoot
{

	/**
	 * 根结点
	 * @var BtstoreElement
	 */
	static $root;

	/**
	 * 锁文件的列表
	 * @var string
	 */
	static $mapLockFiles;

	/**
	 * 获取文件句柄
	 * @param string $path
	 */
	private static function getHandle($path)
	{

		if (isset ( self::$mapLockFiles [$path] ))
		{
			$handle = self::$mapLockFiles [$path];
		}
		else
		{
			$handle = fopen ( $path, 'r' );
			if (empty ( $handle ))
			{
				Logger::fatal ( "open file:%s failed", $path );
				throw new Exception ( 'sys' );
			}
			self::$mapLockFiles [$path] = $handle;
		}
		return $handle;
	}

	/**
	 * 对一个文件加读锁
	 * @param string $path
	 */
	static function lockr($path)
	{

		$handle = self::getHandle ( $path );
		$ret = flock ( $handle, LOCK_SH );
		if (! $ret)
		{
			Logger::fatal ( "lock file:%s failed", $path );
			throw new Exception ( 'sys' );
		}
		Logger::debug ( "path:%s lock read", $path );
	}

	/**
	 * 释放一个文件的锁
	 * @param string $path
	 */
	static function lockw($path)
	{

		$handle = self::getHandle ( $path );
		$ret = flock ( $handle, LOCK_EX );
		if (! $ret)
		{
			Logger::fatal ( "lock file:%s failed", $path );
			throw new Exception ( 'sys' );
		}
		Logger::debug ( "file:%s lock write", $path );
	}

	/**
	 * 对一个文件加写锁
	 * @param string $path
	 */
	static function unlock($path)
	{

		if (isset ( self::$mapLockFiles [$path] ))
		{
			$handle = self::$mapLockFiles [$path];
			unset ( self::$mapLockFiles [$path] );
			$ret = flock ( $handle, LOCK_UN );
			fclose ( $handle );
		}
		else
		{
			$ret = false;
		}

		if (! $ret)
		{
			Logger::fatal ( "lock file:%s failed", $path );
			throw new Exception ( 'sys' );
		}
		Logger::debug ( "path:%s unlocked", $path );
	}
}

class BtstoreElement implements ArrayAccess, Iterator, Countable
{

	/**
	 * 当前所代表的目录
	 * @var string
	 */
	private $dataDir;

	/**
	 * 当前所代表的数据
	 * @var array
	 */
	private $arrData;

	/**
	 * 当前的位置
	 * @var int
	 */
	private $offset;

	/**
	 * 构造函数
	 * @param string $dataDir
	 * @param array $arrData
	 */
	function __construct($dataDir, $arrData)
	{

		$this->dataDir = '';
		$this->arrData = array ();
		if (! empty ( $dataDir ) && is_dir ( ScriptConf::BTSTORE_ROOT . $dataDir ))
		{
			$this->dataDir = $dataDir;
		}

		if (! empty ( $arrData ))
		{
			$this->arrData = $arrData;
		}
		$this->offset = 0;
	}

	private function shouldUpdate($path)
	{

		if (! file_exists ( ScriptConf::BTSTORE_CACHE . $path . '.php' ))
		{
			Logger::debug ( "file:%s not exists, should update", $path );
			return true;
		}

		$arrFileInfo = stat ( ScriptConf::BTSTORE_CACHE . $path . '.php' );
		$mtimeCache = $arrFileInfo ['mtime'];
		$arrFileInfo = stat ( ScriptConf::BTSTORE_ROOT . $path );
		$mtimeOrigin = $arrFileInfo ['mtime'];
		if ($mtimeOrigin < $mtimeCache)
		{
			return false;
		}
		else
		{
			Logger::debug ( "file:%s is invalid, should update", $path );
			return true;
		}
	}

	function __get($key)
	{

		if (isset ( $this->arrData [$key] ))
		{
			$data = $this->arrData [$key];
			if (is_array ( $data ) && ! is_object ( $data ))
			{
				$data = new BtstoreElement ( '', $data );
			}
			return $data;
		}

		if (! empty ( $this->dataDir ))
		{
			$path = $this->dataDir . '/' . $key;
			if (is_dir ( ScriptConf::BTSTORE_ROOT . $path ))
			{
				$data = new BtstoreElement ( $path, null );
				$this->arrData [$key] = $data;
				return $data;
			}

			$begin = microtime ( true );
			if ($this->shouldUpdate ( $path ))
			{

				BtstoreRoot::lockw ( ScriptConf::BTSTORE_ROOT . $path );
				if ($this->shouldUpdate ( $path ))
				{
					Logger::info ( "store file:%s should be updated", $path );
					$content = file_get_contents ( ScriptConf::BTSTORE_ROOT . $path );
					$arrData = unserialize ( $content );
					$data = "<?php\n  return ";
					$data .= var_export ( $arrData, true );
					$data .= ";\n";
					file_put_contents ( ScriptConf::BTSTORE_CACHE . $path . '.php', $data );
				}
				BtstoreRoot::unlock ( ScriptConf::BTSTORE_ROOT . $path );
			}

			BtstoreRoot::lockr ( ScriptConf::BTSTORE_ROOT . $path );
			$arrData = require_once (ScriptConf::BTSTORE_CACHE . $path . '.php');
			BtstoreRoot::unlock ( ScriptConf::BTSTORE_ROOT . $path );
			$end = microtime ( true );
			$cost = intval ( ($end - $begin) * 1000 );
			Logger::info ( "btstore init file:%s cost:%s(ms)", $path, $cost );
			$data = new BtstoreElement ( '', $arrData );
			$this->arrData [$key] = $data;
			return $data;
		}

		trigger_error ( "undefined index:$key" );
	}

	function __isset($key)
	{

		if (isset ( $this->arrData [$key] ))
		{
			return true;
		}

		if (empty ( $this->dataDir ))
		{
			return false;
		}

		if (file_exists ( ScriptConf::BTSTORE_ROOT . $this->dataDir . '/' . $key ))
		{
			return true;
		}

		return false;
	}

	function toArray()
	{

		return $this->arrData;
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset)
	{

		return $this->__isset ( $offset );
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset)
	{

		return $this->__get ( $offset );
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value)
	{

		trigger_error ( 'offsetSet not implemented by BtstoreElement' );
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset)
	{

		trigger_error ( 'offsetUnset not implemented by BtstoreElement' );
	}

	/* (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current()
	{

		$data = current ( $this->arrData );

		if (is_array ( $data ) && ! is_object ( $data ))
		{
			$data = new BtstoreElement ( '', $data );
		}

		return $data;
	}

	/* (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next()
	{

		$this->offset ++;
		$data = next ( $this->arrData );

		if (is_array ( $data ) && ! is_object ( $data ))
		{
			$data = new BtstoreElement ( '', $data );
		}

		return $data;
	}

	/* (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key()
	{

		return key ( $this->arrData );
	}

	/* (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid()
	{

		return $this->offset < count ( $this->arrData );
	}

	/* (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind()
	{

		reset ( $this->arrData );
		$this->offset = 0;
	}

	/* (non-PHPdoc)
	 * @see Countable::count()
	 */
	public function count()
	{

		return count ( $this->arrData );
	}

}

/**
 * 获取一个BtstoreElement对象
 * @return BtstoreElement
 */
function btstore_get()
{

	if (empty ( BtstoreRoot::$root ))
	{
		BtstoreRoot::$root = new BtstoreElement ( '/', null );
		BtstoreRoot::$mapLockFiles = array ();
		if (! is_dir ( ScriptConf::BTSTORE_CACHE ))
		{
			mkdir ( ScriptConf::BTSTORE_CACHE, 0755, true );
		}
	}

	return BtstoreRoot::$root;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
