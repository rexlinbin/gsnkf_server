<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMineral.class.php 135490 2014-10-09 03:46:40Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/IMineral.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-10-09 03:46:40 +0000 (Thu, 09 Oct 2014) $
 * @version $Revision: 135490 $
 * @brief 
 *  
 **/
/**
1.	占领第二个矿时，判断玩家是否已经拥有第二个矿，本矿为独立存在，不同于之前资源矿；
2.	金币矿区占领需要花费固定金币，如果在特定时间段中占领，那么需要花费额外的金币；
3.	金币矿区无法进行协助；
4.	矿的类型花费等根据配置读表即可；
5.	其他相关规则与之前矿相同，功能包括延迟，占领，抢夺等；
 * @author dell
 *
 */
interface IMineral
{
	/**
	 * 占领某个矿坑        capturePit\grabPitByGold\grabPit三个接口都是占领资源矿，返回值基本相同，
	 * capturePit是占领无人占领的资源矿  会打守护资源矿的部队
	 * grabPit是在策划配置时间内抢夺别人的资源矿
	 * grabPitByGold是在策划配置时间之外使用金币抢夺别人的资源矿  返回值里包含消耗的金币数目
	 * @param int $domainId				对应配置表中的资源区ID
	 * @param int $pitId				对应配置表中的资源ID
	 * @return array
	 * <code>
	 * [
	 * 	err:string	'err';'numlimit';'execution';'cd';'formation';'protect';'self';'ok';'captured';'notcaptured';viplimit 占领第二矿坑vip等级不足;
	 * 	fight_ret:string
	 * 	fight_cd:int
	 * 	appraisal:int
	 * ]
	 * </code>
	 * 
	 */
	function capturePit($domainId,$pitId);
	function grabPitByGold($domainId,$pitId);
	function grabPit($domainId,$pitId);
	/**
	 * 放弃某个矿坑
	 * @param int $domainId
	 * @param int $resId
	 * @return 	array
	 * [
	 * 	time:int
	 * 	silver:int
	 * ]
	 */
	function giveUpPit($domainId,$resId);
	/**
	 * 获取某个资源区的所有矿坑信息
	 * @param int $domainId
	 * @return array
	 * <code>
	 * [
	 * 			[
	 * 				domain_id:int
	 * 				pit_id:int
	 * 				domain_type:int
	 * 				uid:int
	 *              uname:string
	 *              level:int
	 * 				due_time:int//倒计时
	 *              guards:array
	 *              [
	 *                  array
	 *                  [
	 *                      uid=>int
	 *                      uname=>string
	 *                      level=>int
	 *                      htid=>int
	 *                      dress=>array
	 *                  ]
	 *              ]
	 * 			]
	 * ]
	 * </code>  
	 */
	function getPitsByDomain($domainId);

	/**
	 * 获取玩家占领的矿坑的信息
	 * @return array
	 * <code>
	 * [
	 *     pits:array
	 *     [
	 *         domain_id:int
	 * 		    pit_id:int
	 * 			domain_type:int
	 * 			uid:int
	 *          uname:string
	 *          level:int
	 * 		    due_time:int//倒计时
	 *          guards:array
	 *          [
	 *              array
	 *              [
	 *                  uid=>int
	 *                  uname=>string
	 *              ]
	 *          ]
	 *     ]
	 *     guard_start_time=>int
	 * ]
	 * </code>
	 */
	function getSelfPitsInfo();
	/**
	 * 探索空旷（一键探索）  找出没有空旷的矿页  返回此页的矿信息
	 * @param int $pitType 矿坑类型 金、银、铜
	 * @return array
	 *<code>
	 * [
	 * 				domain_id:int
	 * 				pit_id:int
	 * 				domain_type:int
	 * 				uid:int
	 *              uname:string
	 *              level:int
	 * 				due_time:int//倒计时
	 *              guards:array
	 *              [
	 *                  array
	 *                  [
	 *                      uid=>int
	 *                      uname=>string
	 *                  ]
	 *              ]
	 * ]
	 * </code>  
	 */
	function explorePit($pitType=0);
	/**
	 * 不需要前端调用 资源矿到期
	 * @param int $uid
	 * @param int $domainId
	 * @param int $pitId
	 */
	function duePit($uid,$domainId,$pitId);
	
	/**
	 * 邮件中的反击使用此接口    玩家收到被抢矿的邮件  反击抢矿的玩家  需要知道抢矿的玩家有哪些矿
	 * 如果抢了金矿  抢矿的玩家必须有金矿才能反击 
	 * 如果抢了非金矿  抢矿的玩家必须有非金矿才能反击
	 * @param int $uid
	 * @return int domainId 0表示此用户没有占矿
	 */
	public function getDomainIdOfUser($uid,$domainType);

    /**
     * 成为协助军
     *
     * @param $domainId
     * @param $pitId
     * @return array
     * <code>
     * [
     *  errcode:    0成功 1矿坑不存在 2你已经是协助军 3你是矿主 4该矿守卫军数量达到上限
     * ]
     * </code>
     */
    function occupyPit($domainId, $pitId);

    /**
     * 放弃做该矿协助军
     *
     * @param $domainId
     * @param $pitId
     * @return array
     * <code>
     * [
     *  errcode:    0成功 1矿坑不存在  2你不是该矿协助军
     * ]
     * </code>
     */
    function abandonPit($domainId, $pitId);

    /**
     * 抢夺某个资源矿的小弟
     *
     * @param int $domainId1 防守方资源区id
     * @param int $pitId1 防守方矿坑id
     * @param int $tuid 目标协助军id
     * @return array
     * <code>
     * [
     *  'errcode' 0成功   1进攻方不是矿主 2防守方是空矿 3(进攻方和防守方是同一个矿主)不能抢自己矿的小弟 4行动力不足
     *   'battleRes' 战报
     * ]
     * </code>
     */
    function robGuards($domainId1, $pitId1, $tuid);

    /**
     * 延时占矿指的是玩家花费体力和金币增加已经占领资源矿的占领时间
     *
     * @param int $domainId 资源区id
     * @param int $pitId    矿坑id
     * @return string $err  'ok'成功; 'notself'矿不属于自己; 'delaylimit'延期次数超标; 'execution'体力不足; 'gold'金币不足
     */
    function delayPitDueTime($domainId, $pitId);
    function leave();
    /**
     * 获取抢矿信息
     * @return
     * [
     *     array
     *     [
     *         rob_time:int
     *         pre_capture:int
     *         now_capture:int
     *         domain_id:int
     *         pit_id:int
     *     ]
     * ]
     */
    function getRobLog();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */