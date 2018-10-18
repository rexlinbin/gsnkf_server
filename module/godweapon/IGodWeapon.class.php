<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IGodWeapon.class.php 242270 2016-05-12 05:49:51Z DuoLi $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/godweapon/IGodWeapon.class.php $$
 * @author $$Author: DuoLi $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-05-12 05:49:51 +0000 (Thu, 12 May 2016) $$
 * @version $$Revision: 242270 $$
 * @brief 
 *  
 **/

interface IGodWeapon
{
    /**
     * 神兵强化
     *
     * @param $itemId int 神兵id
     * @param $arrItemId array 材料数组
     * @param $arrItemNum array 材料数组对应的数量
     * <code>
     *      '$itemId'(物品id),
     * </code>
     * @return array
     * <code>
     *      'reinForceLevel':int    强化等级
     *      'reinForceCost':int 本次强化费用
     *      'reinForceExp':int  当前总强化经验(炼化返还用)
     * </code>
     */
    public function reinForce($itemId, $arrItemId, $arrItemNum);

    /**
     * 神兵进化
     * @param $itemId int 物品id
     * @param $arrGodMaterialId array 要消耗的神兵材料id,如果没有神兵消耗，可以不传，或给空array()
     * @return array
     * <code>
     *      'evolveNum' => int 进化次数
     *      'reinForceLevel':int    强化等级
     *      'reinForceExp':int  当前总强化经验(炼化返还用)
     * </code>
     */
    public function evolve($itemId, $arrGodMaterialId=array());

    /**
     * 神兵炼化
     * @param $arrItemId array {1, 2, 3}
     * @return array
     * <code>
     *      'silver':int,
     *      'items':array
     *          [
     *              item_template_id=>num
     *          ]
     *      'drop':array
     * <code>
     */
    public function resolve($arrItemId);

    public function previewResolve($arrItemId);
    /**
     * 神兵重生
     * @param $itemId
     * @return array
     * <code>
     *      'silver':int
     *      'item':array
     *      [
     *          item_template_id=>num
     *      ]
     * </code>
     */
    public function reborn($itemId);
    
    public function previewReborn($itemId);

    /**
     * 神兵洗练
     * @param $itemId int 物品id
     * @param $type int 洗练类型0:普通洗练 1:金币洗练
     * @param $index int 洗练第几层 从1开始
     * @return int attrId 洗练出的属性id
     */
    public function wash($itemId, $type, $index);

    /**
     * 替换洗练属性
     * @param $itemId int 物品id
     * @param $index int 替换第几层 从1开始
     * @return string ok
     */
    public function replace($itemId, $index);

    /**
     * 批量洗练
     * @param $itemId int 物品id
     * @param $type int 洗练类型0:普通洗练 1:金币洗练
     * @param $index int 洗练第几层 从1开始
     * @return array
     * [
     *  arrAttrId => array{attrId, ...} 洗出的属性数组
     *  num => int 洗练次数
     * ]
     */
    public function batchWash($itemId, $type, $index);

    /**
     * 批量洗练后，确认属性
     * @param $itemId int 物品id
     * @param $index int 替换第几层 从1开始
     * @param $attrId int 属性id
     * @return string ok
     */
    public function ensure($itemId, $index, $attrId);

    /**
     * 取消批量洗练的属性
     * @param $itemId int 物品id
     * @param $index int 层 从1开始
     * @return string ok
     */
    public function cancel($itemId, $index);

    /**
     * 洗练属性传承
     * @param $arrItemId array ($itemId1 洗练源, $itemId2 洗练目标)
     * @param $arrIndex array (1, 2, 3 ...) 层数 从1开始
     * @return string ok
     */
    public function legend($arrItemId, $arrIndex);

    /**
     * 神兵锁定
     * @param $itemId
     * @return string ok
     */
    public function lock($itemId);

    /**
     * 神兵接触锁定
     * @param $itemId
     * @return string ok
     */
    public function unLock($itemId);
    
    /**
     * 神兵转换
     * @param int $itemId 转换前的神兵id
     * @param int $itemTplId 待转换的神兵模板id
     * @return int $itemId 转换后的神兵id
     */
    public function transfer($itemId, $itemTplId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */