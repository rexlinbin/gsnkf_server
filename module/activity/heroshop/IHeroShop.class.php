<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IHeroShop.class.php 84313 2014-01-02 03:37:02Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/IHeroShop.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-02 03:37:02 +0000 (Thu, 02 Jan 2014) $
 * @version $Revision: 84313 $
 * @brief 
 *  
 **/
interface IHeroShop
{
    /**
     * @return array
     * <code>
     * [
     *     rank_info:array
     *     [
     *         index=>array
     *         [
     *             uid:int
     *             score:int
     *             uname:string
     *         ]
     *     ]
     *     shop_info:array
     *     [
     *         uid:int
     *         score:int                    活动期间内的积分
     *         free_cd:int                下一次可以免费购买的时间
     *         free_num:int                 可以免费购买的次数       
     *         gold_buy_num:int            活动期间内，金币购买次数
     *     ]
     *     rank:int
     * ]
     * </code>
     */
    public function getMyShopInfo();
    /**
     * 
     * @param int $type   取值：1免费抽将  2免费金币抽将 3金币招将
     * @return array
     * <code>
     * [
     *     htid:int
     *     shop_info:array
     *     rank:int
     * ]
     * </code>
     */
    public function buyHero($type);
    
    public function leaveShop();
    
    public function refreshRank($score);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */