<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Session.cfg.php 60628 2013-08-21 09:49:35Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Session.cfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:49:35 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60628 $
 * @brief
 *
 **/

class SessionConf
{

	/**
	 * 是否压缩session
	 * @var int
	 */
	const SESSION_COMPRESS = true;

	/**
	 * 存储压缩的key
	 * @var string
	 */
	const SESSION_KEY = '__zlib__';

	/**
	 * 保留而不压缩的字段
	 * @var array
	 */
	static $ARR_RESERVED_KEYS = array ();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */