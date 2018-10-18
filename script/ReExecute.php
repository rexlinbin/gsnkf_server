<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReExecute.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ReExecute.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
require_once (LIB_ROOT . '/HTTPClient.class.php');
require_once (CONF_ROOT . '/Script.cfg.php');

class ReExecute extends BaseScript
{

	private $method;

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		$this->method = get_class ( $this );

		if (empty ( $arrOption ))
		{
			echo "Usage: btscript ReExcuete.php logid filename method\n";
			return;
		}
		$logid = $arrOption [0];

		if (isset ( $arrOption [1] ))
		{
			$filename = $arrOption [1];
		}
		else
		{
			$filename = LOG_ROOT . '/' . FrameworkConfig::LOG_NAME;
		}

		if (isset ( $arrOption [2] ))
		{
			$method = $arrOption [2];
		}
		else
		{
			$method = "";
		}
		$serverIp = '';
		$arrRequest = $this->findRequest ( $filename, $logid, $method, $serverIp );

		if (empty ( $arrRequest ))
		{
			echo "eval request failed\n";
			return;
		}

		$arrRequest ['request'] ['token'] = RPCContext::getInstance ()->getFramework ()->getLogid ();
		if (isset ( $arrRequest ['session'] [SessionConf::SESSION_KEY] ))
		{
			$data = base64_decode ( $arrRequest ['session'] [SessionConf::SESSION_KEY] );
			$arrRequest ['session'] [SessionConf::SESSION_KEY] = $data;
		}
		$this->method = $arrRequest ['request'] ['method'];

		$client = new HTTPClient (
				sprintf ( 'http://%s:%d/execute', ScriptConf::PRIVATE_HOST, ScriptConf::REEXE_PORT ) );
        $client->setHeader ( 'GAME-ADDR', $serverIp );
        $client->setHeader ( 'GAME-GROUP', ScriptConf::PRIVATE_GROUP );
        $client->setHeader ( 'GAME-DB', ScriptConf::PRIVATE_DB );

        $request = Util::amfEncode ( $arrRequest );
		$response = $client->post ( $request );
		$arrResponse = Util::amfDecode ( $response );
		Logger::debug ( "response:%s", $arrResponse );
		echo "response:";
		var_dump ( Util::amfDecode ( $arrResponse ["response"] ) );
	}

	public function getMethod()
	{

		return $this->method;
	}

	private function findRequest($filename, $logid, $method, &$serverIp)
	{

		$file = fopen ( $filename, 'r' );
		if (empty ( $file ))
		{
			echo "file $filename not found\n";
			return false;
		}
		$marker = "logid:$logid";
		$start = false;
		$recordRequest = false;
		$serverIp = '';
		$request = 'array (';
		while ( ! feof ( $file ) )
		{
			$line = fgets ( $file );

			if (strstr ( $line, $marker ))
			{
				if ($recordRequest)
				{
					if (preg_match ( '#\[server:([^\]]+)\]#', $line, $arrMatch ))
					{
						$serverIp = $arrMatch [1];
						Logger::trace ( "serverIp:%s", $serverIp );
					}

					$arrRequest = eval ( 'return ' . $request . ';' );
					$findMethod = '';
					if (isset ( $arrRequest ['request'] ['method'] ))
					{
						$findMethod = $arrRequest ['request'] ['method'];
					}
					if (empty ( $method ) || $findMethod == $method)
					{
						return $arrRequest;
					}
					else
					{
						$request = 'array (';
					}
				}

				if (strstr ( $line, 'request:' ))
				{
					$recordRequest = true;
				}
			}
			else if ($recordRequest)
			{
				$request .= $line;
			}
		}
		echo "request not found\n";
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
