<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IRobTomb.class.php 202938 2015-10-17 10:46:51Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/IRobTomb.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:46:51 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202938 $
 * @brief 
 *  
 **/
interface IRobTomb
{
    /**
     * 获取自己的信息
     * @return array
     * <code>
     * [
     *     uid:int
     *     today_free_num:int        今天免费挖宝的次数
     *     today_gold_num:int        今天金币挖宝的次数
     *     accum_free_num:int        活动期间内免费挖宝的次数
     *     accum_gold_num:int        活动期间内金币挖宝的次数
     *     va_rob_tomb:array            主要存储黑名单的物品被挖到的次数        
     *     [
     *         black_list:array
     *         [
     *             itemTmplId=>robNum
     *         ]
     *     ]
     *     last_refresh_time:int      上一次刷新数据库信息的时间（前端暂时无用）  
     * ]
     * </code>
     */
    function getMyRobInfo();
    /**
     * @param int $num 挖宝次数
     * @param int $robType   消费类型  1是免费  2是金币
     * @return array
     * <code>
     * [
     *     array
     *     [
     *         'item'=>array
     *         [
     *             itemTmplId=>itemNum
     *         ]
     *         'hero'=>array
     *         [
     *             htid=>heroNum
     *         ]
     *         'treasFrag'=>array
     *         [
     *             treasFragId=>num
     *         ]
     *     ]
     * ]
     * </code>
     */
    function rob($num,$robType);
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */