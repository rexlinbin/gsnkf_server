<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMysteryShop.class.php 242030 2016-05-11 06:50:26Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysteryshop/IMysteryShop.class.php $
 * @author $Author: DuoLi $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-05-11 06:50:26 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242030 $
 * @brief 
 *  
 **/
interface IMysteryShop
{
    /**
     * 武将分解
     * @param array $arrHid
     * @return array
     * <code>
     * [
     *     soul:int
     *     silver:int
     *     jewel:int
     * ]
     * </code>
     */
    public function resolveHero($arrHid);
    
    public function previewResolveHero($arrHid);
    
    /**
     * 武将化魂
     * @param array $arrHid
     * @return array
     * <code>
     * [
     *      item_template_id=>num
     * ]
     * </code>
     */
    public function resolveHero2Soul($arrHid);
    
    public function previewResolveHero2Soul($arrHid);
    
    /**
     * 武将精华分解
     * 
     * @param array {itemId => num}
     * @return array
     * {
     * 		'jh' => num
     * }
     */
    public function resolveHeroJH($arrItemInfo);
    
    public function previewResolveHeroJH($arrItemInfo);
    
    /**
     * 装备分解
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function resolveItem($arrItemId);
    
    public function previewResolveItem($arrItemId);
    
    /**
     * 宝物分解
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function resolveTreasure($arrItemId);
    
    public function previewResolveTreasure($arrItemId);
    /**
     * 装备重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornItem($arrItemId);
    
    public function previewRebornItem($arrItemId);
    /**
     * 武将重生
     * @param int $hid
     * @return array
     * <code>
     * [
     *     soul:int
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     *     hero:array
     *         [
     *             array
     *             [
     *                 htid:int
     *                 level:int
     *                 num:int
     *             ]
     *         ]
     * ]
     * </code>
     */
    public function rebornHero($hid);
    
    public function previewRebornHero($hid);
    /**
     * 时装炼化
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function resolveDress($arrItemId);
    
    public function previewResolveDress($arrItemId);
    /**
     * 时装重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornDress($arrItemId);
    
    public function previewRebornDress($arrItemId);
    /**
     * 符印炼化
     * @param array $arrRuneItemId 符印
     * @param array $arrTreasItemId 宝物
     * @return array
     * <code>
     * [
     *     silver:int
     *     tg:int
     * ]
     * </code>
     */
    public function resolveRune($arrRuneItemId, $arrTreasItemId = array());
    
    public function previewResolveRune($arrRuneItemId, $arrTreasItemId = array());
    
    /**
     * 锦囊重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornPocket($arrItemId);
    
    public function previewRebornPocket($arrItemId);
    /**
     * 购买物品
     * @param int $goodsId
     * @return array
     * <code>
     * [
     *     ret:string            'ok'
     *     drop:array            
     * ]    
     * </code>
     */
    public function buyGoods($goodsId);
    /**
     * @return array
     * <code>
     * [
     *     goods_list:array
     *     [
     *         goodsId=>canBuyNum
     *     ]
     *     refresh_cd:int            系统刷新CD
     *     refresh_num:int            玩家刷新次数
     *     sys_refresh_num:int        免费系统刷新次数
     * ]
     * </code>
     */
    public function getShopInfo();
    /**
     * 
     * @param int $type 1.金币刷新  2.物品刷新 3.免费系统刷新
     * @return array
     * <code>
     * [
     *     goods_list:array
     *     [
     *         goodsId=>canBuyNum
     *     ]
     *     refresh_cd:int            系统刷新CD
     *     refresh_num:int            玩家刷新次数
     *     sys_refresh_num:int        免费系统刷新次数
     * ]
     * </code>
     */
    public function playerRfrGoodsList($type);
    
    /**
     * 宝物重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornTreasure($arrItemId);
    
    public function previewRebornTreasure($arrItemId);
    
    /**
     * 橙卡重生  需要橙卡下阵 
     * @param int $hid  重生的橙卡武将id
     /**
     * 武将重生
     * @param int $hid
     * @return array
     * <code>
     * [
     *     reborn_get:array
     *     [
     *         soul:int
     *         silver:int
     *         item:array
     *         [
     *             item_template_id=>num
     *         ]
     *         hero:array
     *         [
     *             array
     *             [
     *                 htid:int
     *                 level:int
     *                 num:int
     *             ]
     *         ]
     *     ]
     *     hero_info:array
     *     [
     *         同getAllHeroes中单个武将信息结构
     *     ]
     * ]
     * </code>
     */
    public function rebornOrangeHero($hid);
    
    public function previewRebornOrangeHero($hid);
    
    /**
     * 红卡武将重生
     * @param int $hid
     * @return array
     * <code>
     * [
     *     reborn_get:array
     *     [
     *         soul:int
     *         silver:int
     *         item:array
     *         [
     *             item_template_id=>num
     *         ]
     *         hero:array
     *         [
     *             array
     *             [
     *                 htid:int
     *                 level:int
     *                 num:int
     *             ]
     *         ]
     *     ]
     *     hero_info:array
     *     [
     *         同getAllHeroes中单个武将信息结构
     *     ]
     * ]
     * </code>
     */
    public function rebornRedHero($hid);
    
    public function previewRebornRedHero($hid);
    
    /**
     * 战魂重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     silver:int
     *     fs_exp:int
     *     item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornFightSoul($arrItemId);
    
    public function previewRebornFightSoul($arrItemId);
    
    /**
     * 兵符分解
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     *     tally_point:int 兵符积分
     * ]
     * </code>
     */
    public function resolveTally($arrItemId);
    
    public function previewResolveTally($arrItemId);
    
    /**
     * 兵符重生
     * @param array $arrItemId
     * @return array
     * <code>
     * [
     * 	   	silver:int
     * 		jewel:int
     *     	item:array
     *         [
     *             item_template_id=>num
     *         ]
     * ]
     * </code>
     */
    public function rebornTally($arrItemId);
    
    public function previewRebornTally($arrItemId);
} 
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */