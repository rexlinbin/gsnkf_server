<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: IFormation.class.php 215377 2015-12-14 03:11:40Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/IFormation.class.php $
 * @author $Author: ShijieHan $(lanhongyu@babeltime.com)
 * @date $Date: 2015-12-14 03:11:40 +0000 (Mon, 14 Dec 2015) $
 * @version $Revision: 215377 $
 * @brief 
 *  
 **/


interface IFormation
{
	/**
	 * 返回阵型信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		0:hid
	 * 		2:hid
	 * 		5:hid
	 * }
	 * </code>
	 */
	public function getFormation();

	/**
	 * 返回阵容信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		0:hid
	 * 		1:hid
	 * 		2:hid
	 * }
	 * </code>  
	 */
	public function getSquad();

	/**
	 * 在阵容中添加一个武将
	 * 
	 * @param int $hid
	 * @param int $index	
	 * @return array $formation 	 当前阵型
	 * <code>
	 * {
	 * 		0:hid
	 * 		2:hid
	 * 		5:hid
	 * }
	 * </code>
	 */
	public function addHero($hid, $index);
	
	/**
	 * 从阵容中删除一个武将
	 * 
	 * @param int $hid
	 * @return string 'ok'
	 */
	public function delHero($hid);
	
	/**
	 * 保存用户设置的阵型信息
	 * 
	 * @param array $formation	
	 * <code>
	 * {
	 * 		0:hid,
	 * 		1:hid,
	 * 		2:hid,
	 * 		3:hid,
	 * 		4:hid,
	 * 		5:hid,
	 * }
	 * </code>
	 * @return string 'ok'
	 */
	public function setFormation($formation);

	/**
	 * 返回小伙伴信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		0:hid -1未开,0开了,N武将id
	 * 		1:hid
	 * 		2:hid
	 * }
	 * </code>
	 */
	public function getExtra();
	
	/**
	 * 加小伙伴
	 * 
	 * @param int $hid
	 * @param int $index
	 * @return string 'ok'
	 */
	public function addExtra($hid, $index);
	
	/**
	 * 减小伙伴
	 *
	 * @param int $hid
	 * @param int $index
	 * @return string 'ok'
	 */
	public function delExtra($hid, $index);
	
	/**
	 * 开小伙伴位置
	 * 
	 * @param int $index 位置下标从0开始
	 * @return string 'ok'
	 */
	public function openExtra($index);
	
	/**
	 * 返回属性小伙伴信息
	 *
	 * @return array
	 * [
	 * 		0:hid -1未开，0开了，N武将id
	 * 		1:hid
	 * 		2:hid
	 * ]
	 */
	public function getAttrExtra();
	
	/**
	 * 加属性小伙伴
	 *
	 * @param int $hid
	 * @param int $index
	 * @return string 'ok'
	 */
	public function addAttrExtra($hid, $index);
	
	/**
	 * 减属性小伙伴
	 *
	 * @param int $hid
	 * @param int $index
	 * @return string 'ok'
	 */
	public function delAttrExtra($hid, $index);
	
	/**
	 * 开属性小伙伴位置
	 *
	 * @param int $index 位置下标从0开始
	 * @return string 'ok'
	*/
	public function openAttrExtra($index);
	
	/**
	 * 获取阵法信息
	 * @return
	 * [
	 * 		'craft_id' => 10001,
			'warcraft' => array(	
									int => array('level' => int),
									...
								),
	 * ]
	 */
	public function getWarcraftInfo();
	
	/**
	 * 升级阵法
	 * @param unknown $craftId
	 */
	public function craftLevelup( $craftId );
	
	/**
	 * 启用阵法
	 * @param unknown $craftId
	 */
	public function setCurWarcraft( $craftId );

    /**
     * 返回属性小伙伴位置的等级
     * @return array
     * [
     *      0=>level(-1未开，0开了，N等级),1=>level,...
     * ]
     */
    public function getAttrExtraLevel();

    /**
     * 强化某个属性小伙伴位置
     * @param $index int 位置
     * @return string 'ok'
     */
    public function strengthAttrExtra($index);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */