<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: PlatformApi.cfg.php 81254 2013-12-17 04:21:26Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/PlatformApi.cfg.php $
 * @author $Author: wuqilin $(dh0000@babeltime.com)
 * @date $Date: 2013-12-17 12:21:26 +0800 (二, 2013-12-17) $
 * @version $Revision: 81254 $
 * @brief
 *
 **/

class PlatformApiConfig
{
	const MD5KEY    =   'platform_ZuiGame';
	
	const PROJECT_ID	=	4;	//三国卡牌在平台那边的项目id
	
	public static $SERVER_ADDR  =   array(
        'addRole'=>'http://10.22.151.129/stat/playerstatus/addRole',
        'roleLvUp'=>array('http://10.22.151.129//stat/playerstatus/rolelvup'),
        'getGiftByCard'=>'http://10.22.151.129:10001/cardApi.php', //礼包码
        'getActivityData'=>'http://10.22.151.129//stat/activitydata/getactivitydata',
        //获取活动配置
		'getTeamAll' => 'http://10.22.151.129/platformapi/worldfight/getTeamAll',
		'getServerByTeamId' => 'http://10.22.151.129/platformapi/worldfight/getServerByTeamId',
		'getTeamByServerId' => 'http://10.22.151.129/platformapi/worldfight/getTeamByServerId',
		'getNameAll' => 'http://10.22.151.129/platformapi/worldfight/getNameAll',

		'getTeamAllNear' => 'http://10.22.151.129/platformapi/worldfight/getNearTeamBySpanId',
		'getServerByTeamIdNear' => 'http://10.22.151.129/platformapi/worldfight/getNearServerByTeamId',
		'getTeamByServerIdNear' => 'http://10.22.151.129/platformapi/worldfight/getNearTeamByServerId'
    );
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
