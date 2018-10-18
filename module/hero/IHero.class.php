<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IHero.class.php 244991 2016-06-01 06:30:37Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/IHero.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2016-06-01 06:30:37 +0000 (Wed, 01 Jun 2016) $
 * @version $Revision: 244991 $
 * @brief
 *
 **/

interface IHero
{
	
	/**
	 * 返回用户所有的武将. 这个接口最好只在登录时调用一次，之后都传增量数据
	 * @return array
	 * <code>
	 * 		array
	 * 		[
	 * 				hid => array
	 * 					[
	 *                      hid:int       没有此字段？？？？？？
	 * 						htid:int      
	 * 						level:int     如果等级为1   没有此字段
	 *                      soul:int      如果将魂数目为0  没有此字段
	 *                      evolve_level:int   如果进阶等级为0  没有此字段
	 *                      lock:int        如果武将没有锁定  此字段没有  如果锁定 值为1
	 * 						equip:          如果武将没有装备  没有此字段  
	 * 							[
	 * 								arming:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      armReinforceLevel:int
	 *                                      armReinforceCost:int
	 *                                  ]
	 *                              ]
	 *                              treasure:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      treasureLevel:int
	 *                                      treasureExp:int
	 *                                      treasureEvolve:int
	 *                                      treasureInlay:array
	 *                                      [
	 *                                          index=>array
	 *                                          [
	 *                                              item_id:int
	 *                                              item_template_id:int
	 *                                              item_num:int
	 *                                              item_time:int
	 *                                              va_item_text:array
	 *                                          ]
	 *                                      ]
	 *                                  ]
	 *                              ]
	 *                              dress:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      
	 *                                  ]
	 *                              ]
	 *                              fight_soul:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      
	 *                                  ]
	 *                              ]
	 *                              pocket:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      
	 *                                  ]  
	 *                              ]
	 *                              tally:array
	 *                              [
	 *                                  item_id:int
	 *                                  item_template_id:int
	 *                                  item_num:int
	 *                                  item_time:int
	 *                                  va_item_text:array
	 *                                  [
	 *                                      
	 *                                  ]  
	 *                              ]
	 * 							]
	 *                          talent:array   如果下面三个字段都是空  没有此字段
	 *                          [
	 *                              to_confirm=>array   如果字段为空  没有此字段
	 *                              [
	 *                                  
	 *                              ]
	 *                              confirmed=>array   如果字段为空  没有此字段
	 *                              [
	 *                                  talentIndex => talentId
	 *                              ]
	 *                              sealed=>array        如果字段为空  没有此字段
	 *                              [
	 *                                  talentIndex=>status  如果talentid是1，表示此位置被封印了  0或者没有此位置表示没有被封印或者不能洗练觉醒能力
	 *                              ]
	 *                          ]
	 *                          transfer:int        如果没有变身   没有此字段
	 *                          dxtrans:int			如果不是定向变身，没有此字段，如果是定向变身，字段值为1
     *                          pill:array
     *                          [
     *                              $index 页数 => array[
     *                                  $itemTplId 丹药模板Id => num数量,...
     *                              ]
     *                          ]
     *                          destiny:int 		天命，默认0
	 * 					]
	 * 		]
	 * </code>
	 */
	public function getAllHeroes();
	
	
	/**
	 * 这个接口不用实现，前端可以从背包数据中得到所有的武将碎片数据
	 */
	public function getAllFragments();
	
	/**
	 * 武将强化：卡牌强化方式
	 * @param int $hid
	 * @param array $consumeHids
	 * @return array
	 * <code>
	 * [
	 *     err:string                     ok    
	 *     soul:int                       转移的将魂数
	 *     silver:int					      消耗的银币数
	 *     level:int                      强化之后的武将级别
	 * ]
	 * </code>
	 */
	public function enforceByHero($hid,$consumeHids);
	/**
	 * 武将强化：将魂强化方式
	 * @param int $hid                    需要强化的武将id
	 * @param int $enforceNum            需要强化的次数
	 * @return array
	 * <code>
	 * 	array
	 * [
	 *     'soul':int                    消耗将魂数目
	 *     'silver':int                    消耗金币数目
	 *     'level':int                        武将等级
	 *     'hero_soul':int                    武将将魂数目
	 * ]
	 * </code>
	 */
	public function enforce($hid,$enforceNum);

	/**
	 * 卖出武将
	 * @param array $hids 武将hid
	 * @return    int 卖出武将获得的银两数目
	 */
	public function sell($hids);	

	/**
	 * 武将进化
	 * @param int $hid 武将hid 
	 * @param array  $hidArr 进化需要消耗的武将hid数组
	 * @param array $arrItem
	 * [
	 *     itemId=>num
	 * ]
	 * @return array
	 * <code>
	 * array
	 * [
	 * 	 'star':int                        进化需要的名将星数
	 *   'silver':int                    消耗的银两数目
	 *   'hero':array                    消耗的武将id数组
	 *   'evolve_level':int                武将的进化等级
	 *  ]
	 * </code>
	 */
	public function evolve($hid , $hidArr, $arrItem);
		
	/**
	 *
	 * 装备物品
	 *
	 * @param int $hid								英雄ID
	 * @param int $armPos							装备位置ID 1-6
	 * @param int $itemId							物品ID
	 * @param int $fromHid                            从其他武将身上换下装备
	 * @return string 'ok'
	 */
	public function addArming($hid, $armPos, $itemId, $fromHid = 0);
	
	public function addFightSoul($hid,$pos,$itemId,$fromHid=0);
	
	public function addGodWeapon($hid, $pos, $itemId, $fromHid = 0);
	
	/**
	 * 卸下战魂
	 * @param int $hid
	 * @param int $pos
	 * @return string 'ok'
	 */
	public function removeFightSoul($hid,$pos);
	
	
	/**
	 * 装备锦囊
	 * @param int $hid    装备锦囊的武将id
	 * @param int $pos    装备锦囊的位置
	 * @param int $itemId    装备的锦囊物品id
	 * @param int $fromHid    锦囊原来属于的武将id  如果是从背包装备  此参数是0
	 * @return string 'ok'
	 */
	public function addPocket($hid, $pos, $itemId, $fromHid = 0);
	
	/**
	 * 卸下锦囊
	 * @param int $hid
	 * @param int $pos
	 * @return string 'ok'
	 */
	public function removePocket($hid, $pos);
	
	/**
	 * 装备兵符
	 * @param int $hid    装备兵符的武将id
	 * @param int $pos    装备兵符的位置
	 * @param int $itemId    装备的兵符物品id
	 * @param int $fromHid    兵符原来属于的武将id  如果是从背包装备  此参数是0
	 * @return string 'ok'
	 */
	public function addTally($hid, $pos, $itemId, $fromHid = 0);
	
	/**
	 * 卸下兵符
	 * @param int $hid
	 * @param int $pos
	 * @return string 'ok'
	*/
	public function removeTally($hid, $pos);
		
	/**
	 * 装备时装
	 * @param int $pos							          装备位置ID 1
	 * @param int $fashionId							时装物品ID
	 * @return string 'ok'
	 */
	public function addFashion($pos,$fashionId);
	/**
	 *
	 * 卸载时装
	 * @param int $pos
	 * @return string 'ok'
	 */
	public function removeFashion($pos);
	/**
	 *
	 * 卸载装备
	 * @param int $hid
	 * @param int $armPos
	 * @return string 'ok'
	 */
	public function removeArming($hid, $armPos);	
	/**
	 * 卸载神兵装备
	 * @param int $hid
	 * @param int $weaponPos
	 */
	public function removeGodWeapon($hid, $weaponPos);
	/**
	 * 将背包中最好的武器添加到武将身上
	 * @param int $hid
	 * @return array
	 * <code>
	 * array
	 * [
	 *       arming:array
	 *       [  
	 *           posID:itemId
	 *       ]
	 *       treasure:array
	 *       [  
	 *           posID:itemId
	 *       ]
	 * ]
	 * </code>
	 */
	public function equipBestArming($hid);
	/**
	 * 
	 * @param int $uid
	 * @return array
	 * <code>
	 * [
	 *     htid:int
	 * ]
	 * </code>
	 */
	public function getHeroBook($uid=0);
	/**
	 * 
	 * @param int $hid
	 * @param int $pos
	 * @param int $itemId
	 * @param int $fromHid
	 */
	public function addTreasure($hid, $pos, $itemId, $fromHid = 0);
	/**
	 * 
	 * @param int $hid
	 * @param int $pos
	 * @return string 'ok'卸宝物成功  'err'卸宝物失败
	 */
	public function removeTreasure($hid=0,$pos=-1);
	
	/**
	 * 一键装备战魂
	 * @param int $hid
	 * @return array
	 * [
	 *     fightSoul=>array
	 *     [
	 *         posId=>itemId
	 *     ]
	 * ]
	 */
	public function equipBestFightSoul($hid);
	
	/**
	 * 锁定某个武将
	 * @param int $hid
	 * @return string 'ok'
	 */
	public function lockHero($hid);
	
	/**
	 * 解锁某个武将
	 * @param int $hid
	 * @return string 'ok'
	 */
	public function unlockHero($hid);
	/**
	 * 激活武将天赋
	 * @param int $hid
	 * @param int $talentIndex 第几天赋   1、2、3、4
	 * @param int $spendIndex 消耗物品的类型  1、2、3
	 * @param bool $batchOp 是否是批量洗练
	 * @param int $num 激活天赋的次数
	 * @return int 天赋id
	 * <code>
	 */
	public function activateTalent($hid,$talentIndex,$spendIndex,$batchOp,$num=1);
	
	/**
	 * 洗练武将天赋确认
	 * @param int $hid
	 * @param int $talentIndex 第几天赋   1、2、3、4
	 * @param int $talentId 
	 * @return string 'ok'
	 */
	public function activateTalentConfirm($hid,$talentIndex,$talentId);
	/**
	 * 保留原来的天赋
	 * @param int $hid
	 * @param int $talentIndex 第几天赋   1、2、3、4
	 * @return string 'ok'
	 */
	public function activateTalentUnDo($hid,$talentIndex);
	/**
	 * 武将觉醒能力传承
	 * @param int $fromHid
	 * @param int $toHid
	 * @param array $arrTalentIndex
	 * @return string 'ok'
	 */
	public function inheritTalent($fromHid,$toHid,$arrTalentIndex);
	
	/**
	 * 武将变身
	 * @param int $hid
	 * @param int $countryId 1魏国2蜀国3吴国4群雄,13资质的国家id是5
	 * @param int $toHtid 指定的武将htid
	 * @return int $htid
	 */
	public function transfer($hid, $countryId, $htid = 0);
	
	/**
	 * 武将变身确认
	 * @param int $hid
	 * @return int $hid 变身后的武将id
	 */
	public function transferConfirm($hid);
	
	/**
	 * 武将变身取消
	 * @param int $hid
	 * @return string 'ok'
	 */
	public function transferCancel($hid);

	/**
	 * 武将进化
	 * 1.将进化之后的武将加到武将图鉴里
	 * 2.只有+7的紫色武将可以进化
	 * 3.武将的觉醒能力怎么办？ 直接继承  判断一下新武将的hcopy和老物件的hcopy是否一样
	 * @param int $hid
	 * @param array $arrHero
	 * [
	 *     hid
	 * ]
	 * @param array $arrItem
	 * [
	 *     itemId=>itemNum
	 * ]
	 * @return string 'ok'
	 */
	public function develop($hid,$arrHero,$arrItem);
	
	/**
	 * 武将进化红卡
	 * 1.将进化之后的武将加到武将图鉴里
	 * 2.只有橙卡武将+5可以进化为红卡
	 * @param int $hid
	 * @param array $arrHero
	 * [
	 *     hid
	 * ]
	 * @param array $arrItem
	 * [
	 *     itemId=>itemNum
	 * ]
	 * @return string 'ok'
	 */
	public function develop2red($hid,$arrHero,$arrItem);

    /**
     * 吃丹药
     * @param $hid int 武将Id
     * @param $index int 页数(丹药配置表Id)
     * @param $itemId int 丹药物品Id
     * @return string 'ok'
     */
    public function addPill($hid, $index, $itemId);

	/**
	 * 卸掉一个丹药
	 * @param $hid int 武将id
	 * @param $index int 页数(丹药配置表Id)
	 * @return string 'ok'
	 */
	public function removePill($hid, $index);

	/**
	 * 一键装备丹药
	 * 
	 * @param hid 武将ID
	 * @param pillType 丹药类型
	 * 
	 * @return pillInfo @see getAllHeroes
	 * */
	public function addArrPills($hid, $pillType);
	
	/**
	 * 按照丹药类型 卸载
	 * @param $hid int 武将id
	 * @param $type int 丹药类型
	 * @return string 'ok'
	 */
	public function removePillByType($hid, $type);

    /**
     * 激活主角天赋
     * @param $index int 天赋装配的位置 从1开始 1,2...
     * @param $talentId int 天赋id
     * @return string 'ok'
     */
    public function activeMasterTalent($index, $talentId);
    
    /**
     * 激活天命
     * @param int $hid 武将id
     * @param int $id 天命id
     * @return string 'ok'
     */
    public function activeDestiny($hid, $id);

    /**
     * 重置天命
     * @param int $hid
     * @return string 'ok'
     */
    public function resetDestiny($hid);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */