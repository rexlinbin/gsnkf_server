<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/


class RewardCfg
{
	const REWARD_LIFE_TIME		=	1209600; //14*24*3600
	
	//是否需要向前端推送新奖励的通知,必须为static,否则其他地方无法修改该值
	static $NO_CALLBACK			=	false;
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */