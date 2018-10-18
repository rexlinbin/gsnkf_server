<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IPet.class.php 248679 2016-06-29 03:49:24Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/IPet.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-06-29 03:49:24 +0000 (Wed, 29 Jun 2016) $
 * @version $Revision: 248679 $
 * @brief 
 *  
 **/
interface IPet
{
	/**
	 * 
	 * 获取所有宠物
	 * @return array
	 * <code>
	 * {
	 * 		array(
	 * 			petInfo =>array(
	 * 			   'petid => array (
	 *					'petid' => int,宠物id
	 *					'pet_tmpl' => int, 宠物模板id
	 *					'level' => int ,宠物等级
	 *					'exp' => int ,宠物经验
	 *					'swallow' => int, 已经吞噬宠物的数量
	 *					'skill_point' => int, 宠物拥有的技能点
	 *					'va_pet' => array(
	 *							skillTalent => array(0 => array(id => 0, level => int, status => int)),
	 *							skillNormal => array(0 => array(id => 0level => int, status => int)),
	 *							skillProduct => array(0 => array(id => 0, level => int, status => int)
     *                          evolveNum => int 进阶等级,
     *                          confirm => array(id属性id => int属性的数值, id => int, id =>int, id =>int)大小为4的数组,
     *                          toConfirm => array(int, int, int, int)大小为4的数组,
     *                          ),
	 *					), 宠物技能相关
	 *				),),
	 *			keeperInfo => 
	 *					array(
	 *							pet_slot => int,宠物仓库已开启数量
	 *							va_keeper => array(0 => array(petid => int, status => int[0未出战1已出战], producttime => int, traintime => int)),
	 *							拥有者信息
	 *							),
	 *	);
	 * }
	 * </code>
	 */
	public function getAllPet();
	
	/**
	 * 开启宠物仓库栏位
	 * @param int $num
	 * @param int $prop 是用物品(1)还是金币(0)
	 */
	public function openKeeperSlot( $prop, $num );

	
	/**
	 * 喂养宠物（物品）
	 * @param int $petid 宠物id
	 * @param int $itemId 物品id
	 * @param int $num	物品数量
	 * 
	 * @return array
	 * <code>
	 * {
	 * 			'expFeed'=> int;
	 *	);
	 * }
	 * </code>
	 */
	public function feedPetByItem( $petid , $itemId , $num );
	
	/**
	 * 喂养宠物 （一键喂养）
	 * @param int $petid 宠物id
	 * 
	 * @return array
	 * <code> 
	 * array{
	 * 			'feedArr'=> array( 
	 * 					'totalExp' => int 总的喂养经验
	 * 					'criTimes' => int 暴击次数
	 * 			
	 * ),
	 * 		
	 *	),			
	 * )
	 * </code>
	 */
	public function feedToLimitation( $petid );
	

	/**
	 * 吞噬宠物
	 * @param int $petid 吞噬宠物的id
	 * @param array $bepetidArr 被吞噬的id组
	 */
	public function swallowPetArr( $petid, $bepetidArr );
	
	/**
	 * 领悟技能或栏位
	 * @param int $petid 要领悟的宠物id
	 */
	public function learnSkill( $petid );
	
	/**
	 * 锁定技能栏
	 * @param int $petid 宠物id
	 * @param int $skillId 要锁定的技能的id
	 */
	public function lockSkillSlot( $petid, $skillId );
	
	/**
	 * 解锁技能栏
	 * @param int $petid 宠物id
	 * @param int $skillId 要解锁技能的id
	 */
	public function unlockSkillSlot( $petid, $skillId );
	
	/**
	 * 宠物技能重置
	 * @param int $petid 要重置的宠物id
	 */
	public function resetSkill( $petid );
	
	/**
	 * 开启上阵栏位
	 * @param $flag int 0用金币，1用物品
	 */
	public function openSquandSlot($flag);
	
	/**
	 * 宠物上阵
	 * @param int $petid 要上阵的宠物id
	 * @param int $pos 要上阵的位置
	 */
	public function squandUpPet( $petid, $pos ); 
	
	/**
	 * 
	 * @param int $petid
	 */
	public function squandDownPet( $petid );
	
	/**
	 * 宠物出战
	 * @param int $petid
	 */
	public function fightUpPet( $petid );
	
	/**
	 * 获取宠物特殊技能产生的东西
	 * @param int $petid
	 */
	public function collectProduction ( $petid );
	
	/**
	 * 一键领取宠物特殊技能产出的东西
	 * @return array
	 * <code>
	 * {
	 *     'err' => 'ok' or 'addProErr' 后者表示添加物品没有成功
	 *     'petIdsArr' => array($petid....)已经领取的宠物id
	 * }
	 * </code>
	 */
	public function collectAllProduction();
	
	/**
	 * 卖出宠物
	 * @param int $petid 卖出宠物的id
	 */
	public function sellPet($petidArr);
	
	/**
	 * 
	 * 
	 * 
	 array(
    ["uid"]=>
    int(32218)
    ["pet_fightforce"]=>
    int(0)
    ["pet_tmpl"]=>
    int(1)
    ["level"]=>
    int(1)
    ["exp"]=>
    int(0)
    ["va_pet"]=>
    array(3) {
      ["skillNormal"]=>
      array(1) {
        [0]=>
        array(3) {
          ["id"]=>
          int(1305)
          ["level"]=>
          int(1)
          ["status"]=>
          int(0)
        }
      }
      ["skillTalent"]=>
      array(2) {
        [0]=>
        array(2) {
          ["id"]=>
          int(2011)
          ["level"]=>
          int(1)
        }
        [1]=>
        array(2) {
          ["id"]=>
          int(2012)
          ["level"]=>
          int(1)
        }
      }
      ["skillProduct"]=>
      array(1) {
        [0]=>
        array(2) {
          ["id"]=>
          int(2011)
          ["level"]=>
          int(2)
        }
      }
    }
    ["uname"]=>
    string(6) "t62258")
     * myrank -1 没有上阵宠物 -2 没有宠物
	 * 
	 */
    /**
     * @return array
     * array
     * {
     *  'myRank' => int -1 没有上阵宠物 -2 没有宠物,
     *  'rankList' => array
     *  [
     *      uid => int,
     *      pet_fightforce => int,
     *      pet_tmpl => int,
     *      level => int,
     *      exp => int,
     *      va_pet => array
     *      {
                skillNormal => array(),
     *          skillTalent => array(),
     *          skillProduct => array(),
     *          confirmed => array(attrId => level) 洗练属性,
     *          evolveLevel => int 进阶等级 ,
     *      }
     *  ]
     * }
     */
    public function getRankList();
	
	/**
	 * 获取阵上的宠物信息
	 * @param int $uid
     *
     * return  => array 
     * 
     *    pet_tmpl => int, 
     *    pet_fightforce => int,
     *    level => int,
     *    va_pet => array 
     *    [
     *    		skillNormal => array(),
     *          skillTalent => array(),
     *          skillProduct => array(),
     *          confirmed => array(attrId => level) 洗练属性,
     *          evolveLevel => int 进阶等级 ,
     *      }
	 */
	public function getPetInfoForRank($uid);
	
	/**
	 * 宠物图鉴相关信息
	 * @param int $uid
	 * 
	 * @return arrray
	 * [
	 * 		模板id，模板id...
	 * ]
	 * 
	 */
	public function getPetHandbookInfo();

    /**
     * 宠物进阶
     * @param $petId int 宠物id
     * @return string 'ok'
     */
    public function evolve($petId);

    /**
     * 宠物洗练
     * @param $petId int 宠物id
     * @param $grade int 档次 1 2 3
     * @param $num int 洗练次数
     * @param $ifForce int 是否强制洗练（无视当前已经洗出的属性）0, 1
     * @return array
     * {
     *  id属性id => int等级, ...
     * }
     */
    public function wash($petId, $grade, $num=1, $ifForce=0);

    /**
     * 属性交换
     * @param $petId1 int 宠物id1
     * @param $petId2 int 宠物id2
     * @return string 'ok'
     */
    public function exchange($petId1, $petId2);

    /**
     * 洗练属性确认
     * @param $petId int 宠物id
     * @return string 'ok'
     */
    public function ensure($petId);

    /**
     * 洗练属性放弃
     * @param $petId int 宠物id
     * @return string 'ok'
     */
    public function giveUp($petId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
