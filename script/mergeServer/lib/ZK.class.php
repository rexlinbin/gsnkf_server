<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ZK.class.php 68960 2013-10-15 07:04:53Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/lib/ZK.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-10-15 15:04:53 +0800 (星期二, 15 十月 2013) $
 * @version $Revision: 68960 $
 * @brief
 *
 **/

class ZK
{
	private $zk;

	public function ZK($host)
	{
		$this->zk = new Zookeeper ( $host );
	}

	public function execute($operand, $path, $dir)
	{
		$return = FALSE;
		switch ($operand)
		{
			case 'set' :
				if (empty ( $dir ) || ! is_dir ( $dir ))
				{
					throw new Exception('inter');
				}
				$return = $this->set ($path, $dir);
				break;
			case 'get' :
				$return = $this->get ($path);
				break;
			case 'dump' :
				$return = $this->dump ($path, $dir);
				break;
			case 'nodes' :
				$return = $this->nodes ($path);
				break;
			case 'create' :
				$return = $this->create ($path);
				break;
			case 'delete' :
				$return = $this->delete ($path);
				break;
			default :
				throw new Exception('inter');
				break;
		}
		return $return;
	}

	/**
	 *
	 * 设置zookeeper的节点
	 *
	 * @param string $path
	 * @param string $dir
	 */
	private function set($path, $dir)
	{
		$data = self::dir2amf ( $dir );
		if ($this->zk->exists ( $path ))
		{
			$this->zk->set ( $path, $data );
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 *
	 * 导出某个节点的信息
	 *
	 * @param string $path
	 * @param string $dir
	 *
	 * @return boolean
	 */
	private function dump($path, $dir)
	{

		if ($this->zk->exists ( $path ))
		{
			$data = $this->zk->get ( $path );
			$arrData = Util::amfDecode ( $data );
			if (empty ( $dir ))
			{
				return FALSE;
			}
			else
			{
				self::array2dir ( $arrData, $dir );
				return TRUE;
			}
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 *
	 * 列出某个节点的所有子节点信息
	 *
	 * @param string $path
	 * @param string $dir
	 *
	 * @return boolean
	 */
	private function nodes($path)
	{
		if ($this->zk->exists ( $path ))
		{
			$data = $this->zk->getChildren ( $path );
			return $data;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 *
	 * 得到某个节点的信息
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	private function get($path)
	{

		if ($this->zk->exists ( $path ))
		{
			$data = $this->zk->get ( $path );
			$arrData = Util::amfDecode ( $data );
			return $arrData;
		}
		else
		{
			return FALSE;
		}
	}

	private static function dir2array($dir, &$arrData)
	{

		$dh = opendir ( $dir );
		if (empty ( $dh ))
		{
			throw new Exception ( sprintf ( "path:%s not exists\n", $dh ) );
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
				if (preg_match ( '/^\s*[0-9]+\s*$/', $content ))
				{
					$content = trim ( $content );
				}
				$arrData [$child] = $content;
			}
			else
			{
				self::dir2array ( $dir . '/' . $child, $arrData [$child] );
			}
		}
	}

	private static function array2dir($arrData, $dir)
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
				throw new Exception ( sprintf("invalid input:%s", $arrData) );
			}
		}
	}

	private static function dir2amf($dir)
	{

		$arrData = array ();
		self::dir2array ( $dir, $arrData );
		return Util::amfEncode ( $arrData );
	}

	/**
	 *
	 * 新建立节点
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	private function create($path)
	{

		if ($this->zk->exists ( $path ))
		{
			return FALSE;
		}
		else
		{
			$this->zk->create ( $path, '',
					array (array ('id' => 'anyone', 'scheme' => 'world', 'perms' => 0x01 ) ) );
			return TRUE;
		}
	}

	/**
	 *
	 * 删除某个节点
	 *
	 * @param string $path
	 *
	 * @return boolean
	 */
	private function delete($path)
	{

		if ($this->zk->exists ( $path ))
		{
			$this->zk->delete ( $path );
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */