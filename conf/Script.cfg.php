<?php

/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Script.cfg.php 66400 2013-09-26 01:42:47Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/Script.cfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-09-26 09:42:47 +0800 (四, 2013-09-26) $
 * @version $Revision: 66400 $
 * @brief
 *
 **/

class ScriptConf
{

	static $ARR_PRELOAD_BTSTORE = array ('HEROES', 'PET' );

	const CRONTAB_FORK_INTERVAL = 1000000;

	const LCSERVER_CFG_ROOT = '/home/pirate/lcserver/conf';

	const MAX_EXECUTE_TIME = 30000;

	const BTSTORE_ROOT = '/home/pirate/rpcfw/data/btstore';

	const BTSTORE_CACHE = '/home/pirate/rpcfw/data/btscache';

	const ZK_HOSTS = '127.0.0.1:2181';

	const ZK_LCSERVER_PATH = '/card/lcserver';

	const PRIVATE_HOST = '127.0.0.1';

	const PRIVATE_GROUP = '';

	const PRIVATE_DB = '';

	const PRIVATE_PORT = 7788;

	const REEXE_PORT = 8080;

	const CALLBACK_INTERVAL = 200;

	const PHPPROXY_CONF = '/home/pirate/phpproxy/conf/module.xml';
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
