<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ZKBackendManager.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ZKBackendManager.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
class ZKBackendManager extends BaseScript
{

	const GSC_CONF_PATH = '/home/pirate/rpcfw/conf/gsc';

	private function usage()
	{

		echo "usage: btscript BackendManager.php maskBackend|unmaskBackend|addBackend [host] [weight]\n";
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		$argc = count ( $arrOption );
		if ($argc < 1)
		{
			echo "not enough arguments\n";
			$this->usage ();
			return;
		}

		$method = $arrOption [0];
		if ($method != 'maskBackend' && $method != 'unmaskBackend' && $method != 'addBackend')
		{
			echo "invalid method\n";
			$this->usage ();
			return;
		}

		if (! empty ( $arrOption [1] ))
		{
			$targetHost = $arrOption [1];
		}
		else
		{
			$targetHost = ScriptConf::PRIVATE_HOST;
		}

		if (empty ( $arrOption [2] ))
		{
			$weight = 0;
		}
		else
		{
			$weight = intval ( $arrOption [2] );
		}

		if ($method == 'addBackend' && empty ( $weight ))
		{
			echo "weight can't be zero for add\n";
			$this->usage ();
			return;
		}

		$zk = new Zookeeper ( ScriptConf::ZK_HOSTS );
		$handle = opendir ( self::GSC_CONF_PATH );
		while ( true )
		{
			$name = readdir ( $handle );
			if (false === $name)
			{
				break;
			}
			$path = self::GSC_CONF_PATH . '/' . $name;
			if (! is_dir ( $path ) || substr ( $name, 0, 4 ) != 'game')
			{
				continue;
			}

			$path = ScriptConf::ZK_LCSERVER_PATH . '/lcserver#' . $name;
			$host = '';
			$port = 0;

			$ofile = 'php://stderr';
			try
			{
				if (! $zk->exists ( $path ))
				{
					continue;
				}
				$data = $zk->get ( $path );
				$arrData = Util::amfDecode ( $data );
				$proxy = new RPCProxy ( $arrData ['host'], $arrData ['port'] );
				$ret = $proxy->$method ( $targetHost, $weight );
				$data = sprintf ( "%s %s on %s:%d %d return %s\n", $method, $targetHost,
						$arrData ['host'], $arrData ['port'], $weight, $ret );
				if ($ret == 'ok')
				{
					$ofile = 'php://stdout';
				}
			}
			catch ( Exception $e )
			{
				$ret = $e->getMessage ();
				$data = sprintf ( "%s %s on path:%s host[%s:%d] return %s\n", $method, $targetHost,
						$path, $host, $port, $ret );
			}

			file_put_contents ( $ofile, $data );
		}
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */