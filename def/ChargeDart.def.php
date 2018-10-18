<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDart.def.php 242142 2016-05-11 10:06:33Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/ChargeDart.def.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-11 10:06:33 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242142 $
 * @brief 
 *  
 **/

class ChargeDartDef
{
    /******mnlm_rule.csv******/
    const CSV_ID = 'id';
    const CSV_LEVEL = 'level';
    const CSV_LAST_TIME = 'time';
    const CSV_INTERVAL = 'interval';
    const CSV_DOUBLE_TIME = 'double_time';
    const CSV_FREE_REFRESH = 'free_refresh';    //每日免费刷新次数
    const CSV_GOLD_REFRESH = 'gold_refresh';    //每日金币刷新次数限制
    const CSV_COST_REFRESH = 'cost_refresh';    //金币刷新花费
    const CSV_FIRST_REFRESH_PRO = 'first_refresh_pro';      //初始刷新概率
    const CSV_REFRESH_UPGRADE_PRO = 'refresh_upgrade_pro';  //非初始升级概率
    const CSV_REFRESH_DARK_CHECK = 'dark_check';            //刷新暗格
    const CSV_CAN_ROB_NUM = 'can_rob_num';          //每车可以被掠夺而次数
    const CSV_FREE_SHIP_NUM = 'free_ship_num';              //每天免费运送次数
    const CSV_GOLD_SHIP_NUM = 'gold_ship_num';              //每天金币运送次数限制
    const CSV_COST_SHIP_NUM = 'cost_ship_num';              //金币运送花费
    const CSV_FREE_ROB_NUM = 'free_rob_num';                //每天的免费掠夺次数
    const CSV_GOLD_ROB_NUM = 'gold_rob_num';                //每天金币掠夺次数限制
    const CSV_COST_ROB_NUM = 'cost_rob_num';                //金币掠夺花费
    const CSV_FREE_ASSIT_NUM = 'free_assit_num';            //每天免费协助次数
    const CSV_GOLD_ASSIT_NUM = 'gold_assit_num';            //每天金币协助限制
    const CSV_COST_ASSIT_NUM = 'cost_assit_num';            //金币协助花费
    const CSV_LEVEL_PROTECT = 'level_protect';              //等级保护
    const CSV_FAST_COST = 'fast_cost';                      //疾行消耗
    const CSV_RAGE_COST = 'rage_cost';                      //狂怒消耗
    const CSV_RAGE_GROW = 'rage_grow';                      //狂怒属性加成
    const CSV_LOOK_COST = 'look_cost';                      //瞭望花费
    const CSV_ALL_PAGE_NUM = 'all_page_num';
    const CSV_ALL_ROAD_NUM = 'all_road_num';
    const CSV_REFRESH_ITEM = 'refresh_item';                //刷新消耗物品
    const CSV_SHIP_ITEM = 'ship_item';                      //运送消耗物品
    
    /******mnlm_items.csv******/
    const CSV_CAR_ID = 'car_id';                            //镖车id
    const CSV_QUALITY = 'quality';                          //镖车品质
    const CSV_REWARD = 'reward';                            //押镖成功的奖励
    const CSV_ROB_REWARD = 'rob_reward';                    //被抢减少收益和抢夺收货收益
    const CSV_ASSIT_REWARD = 'assit_reward';                //协助收益
    const CSV_SPECIAL_REWARD = 'special_reward';			//特殊奖励
    const CSV_SPECIAL_ONCE_REWARD = 'once_special_reward'; 	//被抢获得的特殊奖励
    
    
    /******t_charge_dart_user.sql******/
    const SQL_UID = 'uid';
    const SQL_CMP_TIME = 'cmp_time';
    const SQL_SHIPPING_NUM = 'shipping_num';
    const SQL_BUY_SHIPPING_NUM = 'buy_shipping_num';
    const SQL_ROB_NUM = 'rob_num';
    const SQL_BUY_ROB_NUM = 'buy_rob_num';
    const SQL_ASSISTANCE_NUM = 'assistance_num';
    const SQL_BUY_ASSISTANCE_NUM = 'buy_assistance_num';
    const SQL_REFRESH_NUM = 'refresh_num';
    //下面部分可能会被其他人修改，将隔天检查放到上层中
    const SQL_STAGE_ID = 'stage_id';
    const SQL_STAGE_REFRESH_NUM = 'stage_refresh_num';
    const SQL_HAS_INVITED = 'has_invited';
    const SQL_ASSISTANCE_UID = 'assistance_uid'; 
    const SQL_BEGIN_TIME = 'begin_time';
    const SQL_PAGE_ID = 'page_id';
    const SQL_ROAD_ID = 'road_id';
    const SQL_BE_ROBBED_NUM = 'be_robbed_num';
    const SQL_USER_HAVE_RAGE = 'user_have_rage';
    const SQL_ASSISTANCE_HAVE_RAGE = 'assistance_have_rage';
    const SQL_TID = 'tid';
    
    
    /******t_charge_dart_road.sql******/
    //SQL_STAGE_ID
    //SQL_PAGE_ID
    //SQL_ROAD_ID
    const SQL_PREVIOUS_TIME = 'previous_time';
    
    
    /******t_charge_dart_record.sql******/
    const SQL_RECORD_ID = 'record_id';
    //SQL_STAGE_ID
    //SQL_UID
    const SQL_TIME = 'time';
    const SQL_BE_UID = 'be_uid';
    const SQL_TYPE = 'type';
    const SQL_QUALITY = 'quality';
    //SQL_BE_ROBBED_NUM
    const SQL_SUCCESS = 'success';
    const SQL_BRID1 = 'brid1';
    const SQL_BRID2 = 'brid2';
    const SQL_VA_INFO = 'va_info';
    
    
    /******record表中的type对应的类型******/
    const TYPE_BATTLE = 1;      //战斗,抢夺
    const TYPE_LOOK = 2;        //瞭望
    const TYPE_FINISH = 3;      //到终点

    
    //默认取出第四区域的第一页
    const DEFAULT_MAX_STAGE = 4;
    const DEFAULT_MAX_WEIGHT = 10000;
    
    //取出的各种信息，只取两天以内的
    const GETINFO_INTERVAL = 172800;
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */