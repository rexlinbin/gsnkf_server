<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BabelCrypt.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/BabelCrypt.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief 用于加解密，防止id直接暴露
 *
 **/
class BabelCrypt
{

	static function encryptNumber($pid, $method = BabelCryptConf::METHOD, $key = BabelCryptConf::KEY, $iv = BabelCryptConf::IV)
	{

		$pid = intval ( $pid );
		$data = '';
		while ( $pid )
		{
			$char = $pid % 256;
			$data = chr ( $char ) . $data;
			$pid = intval ( ($pid - $char) / 256 );
		}
		$data = self::encrypt ( $data, true, $method, $key, $iv );
		return bin2hex ( $data );
	}

	static function decryptNumber($data, $method = BabelCryptConf::METHOD, $key = BabelCryptConf::KEY, $iv = BabelCryptConf::IV)
	{

		$data = pack ( 'H' . strlen ( $data ), $data );
		$data = self::decrypt ( $data, true, $method, $key, $iv );
		if (false === $data)
		{
			return false;
		}

		$pid = 0;
		for($counter = 0; $counter < strlen ( $data ); $counter ++)
		{
			$pid <<= 8;
			$pid += ord ( $data [$counter] );
		}
		return $pid;
	}

	static function encrypt($data, $rawOutput = false, $method = BabelCryptConf::METHOD, $key = BabelCryptConf::KEY, $iv = BabelCryptConf::IV)
	{

		return openssl_encrypt ( $data, $method, $key, $rawOutput, $iv );
	}

	static function decrypt($data, $rawOutput = false, $method = BabelCryptConf::METHOD, $key = BabelCryptConf::KEY, $iv = BabelCryptConf::IV)
	{

		return openssl_decrypt ( $data, $method, $key, $rawOutput, $iv );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */