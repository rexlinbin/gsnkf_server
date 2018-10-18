<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IRetrieve.class.php 257926 2016-08-23 09:15:28Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/IRetrieve.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-23 09:15:28 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257926 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : IRetrieve
* Description : 资源追回外部接口类
* Inherit     :
**********************************************************************************************************************/
interface IRetrieve
{
	/**
	 * 获取用户的资源追回信息
	 *
	 *@return array
	 *[
	 *		array
	 *		{
	 *			type			1 世界BOSS,2 擂台赛
	 *			endTime			资源追回的截止时间
	 *          num             可找回次数（只有烧鸡有）
	 *		}
	 *]
	 */
	public function getRetrieveInfo();
	
	/**
	 * 通过金币资源追回
	 *
	 *@param int/array type     1 世界BOSS,2 擂台赛，如果追回一个资源，传int，如果追回多个资源，传array
	 *@param int $isAll         0 表示单条追回 1表示一键追回
	 *
	 *@return array
	 *[
	 *		type=>ret
	 *]
	 *
	 *其中ret取值如下				
	 *ok						追回成功
	 *lack						金币不够
	 *nothing					无资源可被追回，正常状况下，不会返回这个值，例如不调用getRetrieveInfo，直接调用retrieveByGold
	 *already					资源已经被追回，正常状况下，不会返回这个值，例如不调用getRetrieveInfo，直接调用retrieveByGold
	 */
	public function retrieveByGold($type, $isAll=0);
	
	/**
	 * 通过银币资源追回
	 *
	 *@param int/array type     1 世界BOSS,2 擂台赛，如果追回一个资源，传int，如果追回多个资源，传array
	 *@param int $isAll         0 表示单条追回 1表示一键追回
	 *
	 *@return array
	 *[
	 *		type=>ret
	 *]
	 *
	 *其中ret取值如下				
	 *ok						追回成功
	 *lack						银币不够
	 *nothing					无资源可被追回，正常状况下，不会返回这个值，例如不调用getRetrieveInfo，直接调用retrieveBySilver
	 *already					资源已经被追回，正常状况下，不会返回这个值，例如不调用getRetrieveInfo，直接调用retrieveBySilver
	 */
	public function retrieveBySilver($type, $isAll=0);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */