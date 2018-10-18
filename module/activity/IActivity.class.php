<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IActivity.class.php 116793 2014-06-24 03:11:46Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/IActivity.class.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-06-24 03:11:46 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 116793 $
 * @brief 
 *  
 **/

interface IActivity
{
	
	/**
	 * 获取所有活动的配置。 
	 * 登录后第一次调用时，会返回所有活动的数据。 之后调用只返回有改变的活动的配置
	 * 注意！前端需要考虑一种情况：返回的结果中缺少某个活动的配置（比如arrData中没有消费累计活动的配置），说明没有配置此活动。
	 * 此时，认为此活动没有开即可。
	 * @param int $version 前端的当前版本
	 * @return
	 * <code>
	 * array
	 * {
	 *    			validity:int  配置在缓存中的有效期，可以通过这个时间知道什么时候，后端会自动触发更新配置
	 *   			version:int   当前的主干版本
	 *   			arrData=>array  
	 *   			{
	 *   				name=> array		//以配置名字为key
	 *   				{
	 *   					version:int		//此配置的版本号
	 *   					start_time:int
	 *   					end_time:int
	 *   					need_open_time:int	
	 *   					data:string		//配置文件内容
	 *   					ns:
	 *   				}
	 *   			}
	 * }
	 * </code>
	 */
	public function getActivityConf($version);

	
	
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */