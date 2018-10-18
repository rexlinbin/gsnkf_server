<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BabelCrypt.cfg.php 44884 2013-04-26 05:46:17Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/BabelCrypt.cfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-04-26 13:46:17 +0800 (五, 2013-04-26) $
 * @version $Revision: 44884 $
 * @brief 加解密相关的配置
 *
 **/
class BabelCryptConf
{

	const METHOD = 'des';

	const KEY = 'BabelTime';

	const IV = '32210967';

	//验证登录时候用
	const PlayHashKey = "2012#B@belPir@te#0410";
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */