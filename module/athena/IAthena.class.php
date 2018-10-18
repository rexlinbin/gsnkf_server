<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IAthena.class.php 219845 2016-01-06 10:14:18Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/IAthena.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-01-06 10:14:18 +0000 (Wed, 06 Jan 2016) $$
 * @version $$Revision: 219845 $$
 * @brief 
 *  
 **/

interface IAthena
{
    /**
     * 拉取雅典娜详细信息
     * @return array[
     *      detail => array[
     *          index(页数) => array{
     *              attrId 属性id => level 等级, ...
     *          }
     *      ]
     *      treeNum => num(开启最大页数)
     *      buyNum => array[ 如果当天没有购买过，则是空数组
     *          itemTplId => num, ...
     *      ]
     * ]
     */
    public function getAthenaInfo();

    /**
     * 升级技能
     * @param $index int 页数
     * @param $attrId int 属性id
     * @return string 'ok'
     */
    public function upGrade($index, $attrId);

    /**
     * 星魂合成
     * @param $amount int 个数
     * @return string ok
     */
    public function synthesis($amount);

    /**
     * 购买物品
     * @param $itemTplId
     * @param $num
     * @return string ok
     */
    public function buy($itemTplId, $num);

    /**
     * 切换技能
     * @param $skillType int 技能类型：1普通攻击 2怒气攻击
     * @param $skillId int 技能Id
     * @return string ok
     */
    public function changeSkill($skillType, $skillId);

    /**
     * 主角可装备的天赋
     * @return array
     * [
     *  $talentId(天赋id)
     * ]
     */
    public function getArrMasterTalent();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */