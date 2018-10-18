<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Lordwar.cfg.php 129009 2014-08-25 11:10:29Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Lordwar.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-08-25 11:10:29 +0000 (Mon, 25 Aug 2014) $
 * @version $Revision: 129009 $
 * @brief 
 *  
 **/
class LordwarConf
{
	const PROCESS_TEAM_NUM = 5;
	const AUDITION_PROMOTED_NUM = 32;
	const PROMOTION_ROUND_NUM = 5;
	const PROMOTION_OUT_NUM = 3;
	
	
	//support num limit
	const ACCEPT_NO_DEAL_SUPPORT_USER = 8;
	
	
	const REWARD_WHOLEWORLD_LAST_TIME = 259200; //跨服冠军全服奖励持续时间
	
	
	public static $PROMOTION_REWARD_SOURCE = array(
		LordwarField::INNER => array(
			LordwarTeamType::WIN => array(
					RewardSource::LORDWAR_PROM_INNER_WIN_NORMAL,
					RewardSource::LORDWAR_PROM_INNER_WIN_SECOND,
					RewardSource::LORDWAR_PROM_INNER_WIN_FIRST,
			),
			LordwarTeamType::LOSE => array(
					RewardSource::LORDWAR_PROM_INNER_LOSE_NORMAL,
					RewardSource::LORDWAR_PROM_INNER_LOSE_SECOND,
					RewardSource::LORDWAR_PROM_INNER_LOSE_FIRST,
			),
		),
		LordwarField::CROSS => array(
			LordwarTeamType::WIN => array(
					RewardSource::LORDWAR_PROM_CROSS_WIN_NORMAL,
					RewardSource::LORDWAR_PROM_CROSS_WIN_SECOND,
					RewardSource::LORDWAR_PROM_CROSS_WIN_FIRST,
			),
			LordwarTeamType::LOSE => array(
					RewardSource::LORDWAR_PROM_CROSS_LOSE_NORMAL,
					RewardSource::LORDWAR_PROM_CROSS_LOSE_SECOND,
					RewardSource::LORDWAR_PROM_CROSS_LOSE_FIRST,
			),
		),
	);
	
	public static $MAIL_ID = array(
			 'LORDWAR_INNER_WIN_4_32'					=> MailTemplateID::LORDWAR_INNER_WIN_4_32,
			 'LORDWAR_INNER_LOSE_4_32'					=> MailTemplateID::LORDWAR_INNER_LOSE_4_32,
			 'LORDWAR_INNER_WIN_2'						=>MailTemplateID::LORDWAR_INNER_WIN_2,
			 'LORDWAR_INNER_LOSE_2'						=>MailTemplateID::LORDWAR_INNER_LOSE_2,
			 'LORDWAR_INNER_WIN_1'						=>MailTemplateID::LORDWAR_INNER_WIN_1,
			 'LORDWAR_INNER_LOSE_1'						=>MailTemplateID::LORDWAR_INNER_LOSE_1,
			
			 'LORDWAR_CROSS_WIN_4_32'					=>MailTemplateID::LORDWAR_CROSS_WIN_4_32,
			 'LORDWAR_CROSS_LOSE_4_32'					=>MailTemplateID::LORDWAR_CROSS_LOSE_4_32,
			 'LORDWAR_CROSS_WIN_2'						=>MailTemplateID::LORDWAR_CROSS_WIN_2,
			 'LORDWAR_CROSS_LOSE_2'						=>MailTemplateID::LORDWAR_CROSS_LOSE_2,
			 'LORDWAR_CROSS_WIN_1'						=>MailTemplateID::LORDWAR_CROSS_WIN_1,
			 'LORDWAR_CROSS_LOSE_1'						=>MailTemplateID::LORDWAR_CROSS_LOSE_1,

	);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */