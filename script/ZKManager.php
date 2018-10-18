<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ZKManager.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ZKManager.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
class ZKManager extends BaseScript
{

	const OPERAND_OFFSET = 0;

	const PATH_OFFSET = 1;

	const DIR_OFFSET = 2;

	private function usage()
	{

		echo "btscript ZKManager.php set|get|create|delete path [dir]\n";
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		$hosts = ScriptConf::ZK_HOSTS;
		$zk = new Zookeeper ( $hosts );

		if (empty ( $arrOption [self::OPERAND_OFFSET] ))
		{
			$this->usage ();
			return;
		}
		$operand = $arrOption [self::OPERAND_OFFSET];

		if (empty ( $arrOption [self::PATH_OFFSET] ))
		{
			$this->usage ();
			return;
		}
		$path = $arrOption [self::PATH_OFFSET];

		switch ($operand)
		{
			case 'set' :
				$dirNone = empty ( $arrOption [self::DIR_OFFSET] );
				if ($dirNone || ! is_dir ( $arrOption [self::DIR_OFFSET] ))
				{
					$this->usage ();
					return;
				}
				$dir = $arrOption [self::DIR_OFFSET];

				$this->set ( $zk, $path, $dir );
				break;
			case 'get' :
				if (empty ( $arrOption [self::DIR_OFFSET] ))
				{
					$dir = "";
				}
				else
				{
					$dir = $arrOption [self::DIR_OFFSET];
				}

				$this->get ( $zk, $path, $dir );
				break;
			case 'create' :
				$this->create ( $zk, $path );
				break;
			case 'delete' :
				$this->delete ( $zk, $path );
				break;
			default :
				$this->usage ();
				return;
		}
	}

	private function set(Zookeeper $zk, $path, $dir)
	{

		$data = self::dir2amf ( $dir );
		if ($zk->exists ( $path ))
		{
			$zk->set ( $path, $data );
			echo sprintf ( "set data to path:%s ok\n", $path );
		}
		else
		{
			echo sprintf ( "path:%s not found in zookeeper\n", $path );
		}
	}

	private function get(Zookeeper $zk, $path, $dir)
	{

		if ($zk->exists ( $path ))
		{
			$data = $zk->get ( $path );
			$arrData = Util::amfDecode ( $data );
			if (empty ( $dir ))
			{
				var_dump ( $arrData );
			}
			else
			{
				self::array2dir ( $arrData, $dir );
			}
		}
		else
		{
			echo sprintf ( "path:%s not found in zookeeper\n", $path );
		}
	}

	public static function dir2array($dir, &$arrData)
	{

		$dh = opendir ( $dir );
		if (empty ( $dh ))
		{
			Logger::fatal ( "open directory:%s failed", $dir );
			throw new Exception ( 'inter' );
		}

		while ( true )
		{
			$child = readdir ( $dh );
			if (empty ( $child ))
			{
				break;
			}
			if ($child == '.' || $child == '..')
			{
				continue;
			}
			if (is_file ( $dir . '/' . $child ))
			{
				$content = file_get_contents ( $dir . '/' . $child );
				$arrData [$child] = $content;
			}
			else
			{
				self::dir2array ( $dir . '/' . $child, $arrData [$child] );
			}
		}
	}

	public static function array2dir($arrData, $dir)
	{

		if (! is_dir ( $dir ))
		{
			mkdir ( $dir );
		}

		foreach ( $arrData as $key => $value )
		{
			if (is_array ( $value ))
			{
				self::array2dir ( $value, $dir . "/" . $key );
			}
			else if (is_string ( $value ))
			{
				file_put_contents ( $dir . '/' . $key, $value );
			}
			else
			{
				Logger::fatal ( "invalid input:%s", $arrData );
				throw new Exception ( 'inter' );
			}
		}
	}

	public static function dir2amf($dir)
	{

		$arrData = array ();
		self::dir2array ( $dir, $arrData );
		return Util::amfEncode ( $arrData );
	}

	public static function create(Zookeeper $zk, $path)
	{

		if ($zk->exists ( $path ))
		{
			echo "$path already exists\n";
		}
		else
		{
			$zk->create ( $path, '',
					array (array ('id' => 'anyone', 'scheme' => 'world', 'perms' => 0x01 ) ) );
			echo "create $path ok\n";
		}
	}

	public static function delete(Zookeeper $zk, $path)
	{

		if ($zk->exists ( $path ))
		{
			$zk->delete ( $path );
			echo "delete $path ok\n";
		}
		else
		{
			echo "$path not found\n";
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */