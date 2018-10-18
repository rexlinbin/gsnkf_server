<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pill.class.php 245084 2016-06-02 03:25:05Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pill/Pill.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-06-02 03:25:05 +0000 (Thu, 02 Jun 2016) $
 * @version $Revision: 245084 $
 * @brief 
 *  
 **/
class Pill implements IPill
{
	public function fuse($index,$isAll=0)
	{
		//看看参数对不对
		$itemConf=btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PILL_PORMULA]->toArray();
		if (!isset($itemConf[0][$index]))
		{
			throw new FakeException('wrong index:%d',$index);
		}
		$bag=BagManager::getInstance()->getBag();
		$itemNum1= floor($bag->getItemNumByTemplateID($itemConf[0][$index][0])/$itemConf[0][$index][1]);
		$itemNum2=floor($bag->getItemNumByTemplateID($itemConf[1][0])/$itemConf[1][1]);
		$num=$itemNum1<$itemNum2?$itemNum1:$itemNum2;
		if ($num<=0)
		{
			return  'err';
		}
		if ($isAll==0)
		{
			$num=1;
		}
		//看看背包里的东西够不够
		if ($bag->deleteItembyTemplateID($itemConf[0][$index][0], $itemConf[0][$index][1]*$num)==false
				||$bag->deleteItembyTemplateID($itemConf[1][0], $itemConf[1][1]*$num)==false)
		{
			return  'err';
		}
		$getItemConf=btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PILL_RESULT]->toArray();
		$bag->addItemByTemplateID($getItemConf[$index][0], $getItemConf[$index][1]*$num);
		$bag->update();
		
		return array(
				'itemTmpId'=>$getItemConf[$index][0],
				'itemNum'=>$getItemConf[$index][1]*$num);
	}
	/**
	 * 给武将重生返还丹药调用的，想了想好像只有在这里边的才好确定这是个6品丹药
	 * @return multitype:unknown
	 */
	public static function getPillItemTmpIdArr()
	{
		$conf=btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_PILL_RESULT]->toArray();
		$ret=array();
		foreach ($conf as $v)
		{
			$ret[]=$v[0];
		}
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */