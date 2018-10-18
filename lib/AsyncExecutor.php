<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AsyncExecutor.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/AsyncExecutor.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class AsyncExecutor extends BaseScript
{

	private $arrRequest;

	public function init($arrOption)
	{

		$request = base64_decode ( $arrOption [0] );
		$this->arrRequest = Util::amfDecode ( $request );
		$this->serverIp = $this->arrRequest ['serverIp'];
		$this->logid = $this->arrRequest ['logid'];
		$this->group = $this->arrRequest ['group'];
		$this->db = $this->arrRequest ['db'];
		$this->time = $this->arrRequest ['time'];
		$this->serverId = $this->arrRequest ['serverId'];
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		Logger::debug ( "async execute request:%s", $this->arrRequest );
		RPCContext::getInstance ()->executeRequest ( $this->arrRequest );
	}

	public function getMethod()
	{

		return $this->arrRequest ['method'];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */