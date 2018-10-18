<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: ITopupReward.class.php 122072 2014-07-22 08:05:51Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/topupreward/ITopupReward.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-07-22 16:05:51 +0800 (星期二, 22 七月 2014) $$
 * @version $$Revision: 122072 $$
 * @brief 
 *  
 **/

interface IFestivalAct
{
    /**
     * 获得奖励数据
     * 
     * @return array
     * [
     *  'exchange' => array
     *      [
     *          301001 => array[m, 0], （参数一：已经使用的次数，参数二：0没买完1次数用完）
     *          301002 => array[1, 0], 
     *      ]
     *  'data' => array
     *      [
     *          1 => array 第一季的信息
     *          [
     *              1 => type:1 任务完成情况
     *              [
     *                  101001 => array[n, 1], （参数一：这个任务完成的数量，参数二：0没完成1完成2领过奖励）
     *                  ......
     *              ]
     *              2 => type:2 限时折扣
     *              [
     *                  101002 => array[m, 0], （参数一：已经使用的次数，参数二：0没买完1次数用完）
     *                  ......
     *              ]
     *              4 => type:4 充值领奖情况
     *              [
     *                  110003 => array[2, 3], （对于充值领奖，参数一：已经领过奖励的次数， 参数二：充值过但还没有领奖的次数）
     *                                          参数一 + 参数二 = 对应档位已经充过值的次数
     *                  ......
     *              ]
     *              ......
     *          ]
     *          2 => array 第二季的信息
     *          ......
     *      ],
     *  'period' => int 当前是第几季 （从1开始）
     *  'day' => int 活动第几天（从1开始）
     * ]
     */
    public function getInfo();


	/**
	 * 完成任务领取奖励
	 * 
	 * @param int $id 对应任务ID
	 * @return 'ok':成功领取 
	 */
	public function taskReward($id);


	/**
	 * 购买商品
	 *
	 * @param $Id:对应任务ID
	 * @return 'ok':购买成功 
	 */
	public function buy($id, $num);


	/**
	 * 兑换商品
	 *
	 * @param $Id:对应任务ID
	 * @return 'ok':兑换成功
	 */
	public function exchange($id, $num = 1);


	/**
	 * 登陆补签
	 *
	 * @param $Id:对应任务ID
	 * @return 'ok':补签并领奖成功
	 */
	public function signReward($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */