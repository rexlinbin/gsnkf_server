<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IDragon.class.php 160587 2015-03-09 06:36:25Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/IDragon.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-03-09 06:36:25 +0000 (Mon, 09 Mar 2015) $$
 * @version $$Revision: 160587 $$
 * @brief 
 *  
 **/
interface IDragon
{
    /**
     * 拉取地图
     *
     * @return array
     * <code>
     * [
     *  'map'=>array 当前寻龙地图
     *      [
     *          0=>array('eid' => 10000, 'status' => array(0=>0是否走过, 1=>1是否被炸，2=>1雾是否被驱散, 3=>1是否触发)), 1=>100001, ..., 24=>30000 事件id
     *      ]
     *  'posid'=>0 int 当前坐标点
     *  'mode'=>0 int 模式 0普通模式 1寻龙试炼模式
     *  'floor'=>0 int 当前所在层
     *  'hppool'=>0 当前血池血量
     *  'resetnum'=> 当天已重置次数
     *  'free_reset_num' => 可免费重置次数
     *  'free_ai_num' => 剩余的自动寻龙免费步数
     *  'buyactnum' => 当天购买行动力次数
     *  'buyhpnum' => 当天购买hp次数
     *  'act' => 当前行动力
     *  'point' => 积分
     *  'total_point' => 总积分
     *  'once_max_point' => 单次最高积分(用来判断能否开启寻龙试炼模式)
     *  'hasmove' => 0否 1是 int 是否移动过（能否一键寻龙)
     *  'movedata' => array('point' => int（积分增量）, 'act' => 行动力增量, 'hp' => hp增量, 'drop'=> array()掉落出的物品, 'sj' => 守军id, 'fb'=> array('isNpc'=>bool, 'enemy'=>enemyid ('uid','uname','level','dress','htid') ))
     * ]
     * </code>
     */
    function getMap();

    /**
     * @return mixed
     */
    function getUserBf();

    /**
     * 移动
     * @param $posid
     * @return array
     * 'ret' =>
     * [
     *  'eid'=>int 事件id
     *  'hppool' =>int 当前血池血量
     *  'act' => int 行动力
     *  'point' => int 当前积分
     *  'total_point' => 总积分
     *  'other' => array('point' => int（积分增量）, 'act' => 行动力增量, 'hp' => hp增量, 'drop'=> array()掉落出的物品, 'sj' => 守军id,
     *          'fb'=> array('isNpc'=>bool, 'enemy'=>enemyid ('uid','uname','level','dress','htid') )
     *          'defeated'=> int index打败的boss的index， 'conNum'=>int(捐献的次数), 'bought'=>array(买到的东西的index))
     * ]
     *
     */
    function move($posid);

    /**
     * 双倍
     * @param $eventId int 事件id
     * @return bool true 成功
     */
    function doublePrize($eventId);

    /**
     * 贿赂怪物
     * @param $eventId int 事件id
     * @return bool true成功
     */
    function bribe($eventId);

    /**
     * 答题
     * @param $eventId int 事件id
     * @param $answer int 所选答案
     * @return bool 正确true 错误false
     */
    function answer($eventId, $answer);

    /**
     * 继续探宝(废弃)
     * @param $posid int 位置id
     * @return mixed
     */
    function goon($posid);

    /**
     * 战斗
     * @param $eventId int 事件id
     * @return array
     * [
     *  'atkRet' => $atkRet 战报
     *  'hppool' => int 当前血池血量
     * ]
     */
    function fight($eventId);

    /**
     * 跳过战斗
     * @param $posid int 位置id
     * @return bool true 调过成功
     */
    function skip($posid);

    /**
     * 一键完成
     * @param $eventId int 事件id
     * @return bool true 成功
     */
    function onekey($eventId);

    /**
     * 买血
     * @param $index int
     * @return $hppool int 血池血量
     */
    function buyHp($index);

    /**
     * 买行动力
     * @param $index 位置下标 从0开始
     * @param $num 连续买num次
     * @return $act int 当前行动力
     */
    function buyAct($index, $num);

    /**
     * 重置
     * @return array
     * <code>
     * [
     *  'map'=>array 当前寻龙地图
     *      [
     *          0=>array('eid' => 10000, 'status' => array(0=>0是否走过, 1=>1是否被炸，2=>1雾是否被驱散)), 1=>100001, ..., 24=>30000 事件id
     *      ]
     *  'posid'=>0 int 当前坐标点
     *  'floor'=>0  int 当前所在层
     *  'gold'=>0 int 重置金币
     *  'hp'=>0 当前血池血量
     *  'resetnum'=> 当天已重置次数
     *  'free_reset_num'
     *  'hasmove' => 0 int 是否移动过（能否一键寻龙)
     *  'act' => 当前行动力
     *  'point' => 积分
     * ]
     * </code>
     */
    function reset();

    /**
     * @param $arrPosid array
     *  [
     *      posid: int 地图点
     *  ]
     * @return bool true 成功
     */
    function autoMove($arrPosid);

    /**
     * @param $floor int 终点探宝层
     * @param $actIndex int 体力档 从0开始
     * @return array
     * <code>
     * [
     *  'event' => array
     *      [
     *          1 => array(123, 245, ...), {层 => array(事件id...)}
     *          2 => array(),
     *      ]
     *  'drop' => array
     *      [
     *          掉落的奖品
     *      ]
     * ]
     * </code>
     */
    function aiDo($floor, $actIndex);

    /**
     * 进入试炼
     * @return array 和重置接口的返回值格式一样
     * <code>
     * [
     *  'map'=>array 当前寻龙地图
     *      [
     *          0=>array('eid' => 10000, 'status' => array(0=>0是否走过, 1=>1是否被炸，2=>1雾是否被驱散)), 1=>100001, ..., 24=>30000 事件id
     *      ]
     *  'posid'=>0 int 当前坐标点
     *  'floor'=>0  int 当前所在层
     *  'gold'=>0 int 重置金币
     *  'hp'=>0 当前血池血量
     *  'resetnum'=> 当天已重置次数
     *  'free_reset_num' => int
     *  'hasmove' => 0 int 是否移动过（能否一键寻龙)
     *  'act' => 当前行动力
     *  'point' => 积分
     * ]
     * </code>
     */
    function trial();

    /**
     * 购买商品（对应商人事件）
     * @param $eventId
     * @param $goodIndex int 购买物品的index
     * @return array 已经购买的商品id
     * <code>
     * [
     *   'bought' => array($goodIndex, ...)
     * ]
     * </code>
     */
    function buyGood($eventId, $goodIndex);

    /**
     * 捐献物品(对应捐献事件)
     * @param $eventId
     * @param $goodId
     * @return array
     * <code>
     *  'conNum' => int 已捐献次数(没捐献过，返回0)
     * </code>
     */
    function contribute($eventId, $goodId);

    /**
     * 试炼boss
     * @param $eventId
     * @param $armyIndex int 索引(从0开始)
     * @return array
     * <code>
     * [
     *  'defeated' => array(armIndex, ...),
     *  'drop' => array()
     *  其他返回值，和fight接口一致
     * ]
     * </code>
     */
    function fightBoss($eventId, $armyIndex);

    /**
     * boss 花费金币，直接通关
     * @param $eventId
     * @param $armyIndex
     * @return array
     * <code>
     * [
     *  'defeated' => array(armIndex, ...),
     *  'drop' => array()
     * ]
     * </code>
     */
    function bossDirectWin($eventId, $armyIndex);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */