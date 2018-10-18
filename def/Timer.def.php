<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Timer.def.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Timer.def.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/
class TimerDef
{

	static $ARR_CALLBACK = array ("callbackName" => "dummy" );
}

class TimerStatus
{

	const UNDO = 1;

	const FINISH = 2;

	const FAILED = 3;

	const RETRY = 4;

	const CANCEL = 5;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */