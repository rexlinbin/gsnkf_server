<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IDivine.class.php 257977 2016-08-23 10:29:54Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/IDivine.class.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-23 10:29:54 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257977 $
 * @brief 
 *  
 **/
interface IDivine
{
	/**
	 * 获取占星信息
	 * @return array
	 * <code>
	 * array
	 *(
	 *		'uid' 						,用户uid
	 *		'divi_times' 				,今日占星次数
	 *		'free_refresh_num' 			,剩余免费刷新次数（0为没有免费次数）
	 *		'prize_step' 				,奖励领取次数（0为一次也没领）
	 *		'target_finish_num' 		,今日目标星座完成次数 （0为一次也没完成）
	 *		'integral' 					,积分 
	 *		'ref_prize_num'				,今日已刷新次数
	 *		'prize_level' 				,奖励表级别 
	 *	
	 *		'va_divine'  => 
	 *	 			array	( 
	 *							'target'  => array( id1,id2... )    	当前目标星座的星星id组
	 *							'current' => array( id1,id2... ) 		当前占星星座的星星id组
	 *							'lighted' => array( pos => 1,pos=> 0,pos=>1... )	已经点亮的位置组,1为亮,0为不亮
	 *							'newreward' => array( 2, 1, 3, 2...  ) 每个位置用的相应配置中的第几个
	 *						)
	 *	)
	 *	</code>
	 */
	public function getDiviInfo();
	
	/**
	 * 占卜一颗星星
	 * @param int $pos 占星星座中星星的位置（ 0 为起始）
	 */
	public function divi( $pos );
	
	/**
	 * 刷新当前占星星座（有免费的直接用，没有的话用金币）
	 */
	public function refreshCurstar();
	
	/**
	 * 领取奖励 
	 * @param int $step 领取哪一步的奖励（ 0为起始 ）
	 */
	public function drawPrize( $step );
	
	/**
	 * 升级奖励表
	 */
	public function upgrade();
	
	/**
	 * 手动刷新奖励
	 * @return
	 * array( 2, 1, 3, 2...  ) 每个位置用的相应配置中的第几个
	 */
	public function refPrize();
	
	/**
	 * 领取所有奖励
	 */
	public function drawPrizeAll();
	
	
	/**
	 * 一键占星
	 */
	public function oneClickDivine();
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */