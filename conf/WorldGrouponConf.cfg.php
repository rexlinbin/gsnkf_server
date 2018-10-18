<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class WorldGrouponConf
{
    const WORLD_GROUPON_CROSS_DB_PREFIX = "pirate_worldgroupon_";   //跨服db名称

    public static $TEST_MODE = 0;   //线上应该为0,1或者2是测试模式,会缩短整个活动周期
    public static $TEST_OFFSET	= array(600,6600);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */