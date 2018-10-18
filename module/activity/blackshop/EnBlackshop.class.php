<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(pengnana@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
class EnBlackshop
{
	//黑市兑换解析函数  blackshop.csv
	public static function readblackshopCSV( $arr )//传过来的是去掉前两行的表格配置数据
	{
		$arrConf = array();
		$index = 0;
		foreach ( $arr as $data )//按行遍历
		{
			if ( empty( $data ) || empty( $data[ 0 ] ) )
			{
				break;
			}
			$shopConf = array();
			if ( empty( $data[ $index ] )  )
			{
				break;
			}
			$id = intval($data[0]);
			if($id == 1 )//读取第一行时,将持续天数初始化
			{
				$count = 1;
				$show_exchange = explode( ',' , $data[4] );//取天数配置信息,判断活动有几天
				$dayId = array();
				foreach($show_exchange as $jud => $dayInfo)
				{
					$dayId [$count] =  array_map('intval', explode( '|' , $dayInfo ));//第一天的id记录
					$count++;
				}
				
				$arrConf=array( 'lastDay'=>intval($count - 1),'dayInfo' =>$dayId);		
			}
			$req = explode( ',' , $data[1] );//req
			$midArr = array();
			foreach($req as $day => $reqInfo)//req解析
			{
				$midArr =  array_map('intval', explode( '|' , $reqInfo ));
				if($midArr[0]==RewardConfType::GOLD)//金币!
				{
					$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_GOLD]=intval ($midArr[2]);
				}
				else if($midArr[0]==RewardConfType::PRESTIGE)//声望!
				{
					$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_PRESTIGE]= intval ($midArr[2]);
				}
				else if($midArr[0]==RewardConfType::ITEM_MULTI)//多个物品
				{
					$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_ITEM][intval($midArr[1])]=intval( $midArr[2]);//需要物品，数组：itemId => itemNum
				}
				else if($midArr[0]==RewardConfType::SILVER)//银币!
				{
					$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_SILVER]=intval ($midArr[2]);
				}
				else if($midArr[0]==RewardConfType::HORNOR)//荣誉
				{
					$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA]=intval ($midArr[2]);
				}
				else 
				{
					throw new ConfigException('blackshop item type invalid!');
				}
				$shopConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = intval ( $data[3] );//限制购买次数
			}
			
			
			$acq = array_map('intval', explode( '|' , $data[2] ));//acq解析
			if($acq[0] == RewardConfType::GOLD)
			{
				$shopConf[MallDef::MALL_EXCHANGE_ACQ ][MallDef::MALL_EXCHANGE_GOLD]=intval ( $acq[2] );
			}
			else if($acq[0]== RewardConfType::PRESTIGE)
			{
				$shopConf[MallDef::MALL_EXCHANGE_ACQ ][MallDef::MALL_EXCHANGE_PRESTIGE]=intval ( $acq[2] );
			}
			else if($acq[0]== RewardConfType::ITEM_MULTI)
			{
				$shopConf[MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM][intval ($acq[1])]= intval ( $acq[2]);//需要物品，数组：itemId => itemNum
			}
			else if($acq[0]== RewardConfType::SILVER)
			{
				$shopConf[MallDef::MALL_EXCHANGE_ACQ ][MallDef::MALL_EXCHANGE_SILVER]=intval ( $acq[2] );
			}
			else if($acq[0]== RewardConfType::HORNOR)
			{
				$shopConf[MallDef::MALL_EXCHANGE_ACQ ][MallDef::MALL_EXCHANGE_EXTRA]=intval ( $acq[2] );
			}
			if(empty($data[5]))
			{
				$shopConf[MallDef::MALL_EXCHANGE_TYPE] = MallDef::REFRESH_EVERYDAY;
			}
			else 
			{
				$shopConf[MallDef::MALL_EXCHANGE_TYPE] = intval($data[5]);//新加商品刷新类型2015-10-19
			}		
			$arrConf[$id] = $shopConf;
			/*
			 * arrConf  [lastDay] => 3 int
			 *          [dayInfo] => 1 int =>array(0=>1,1=>2,2=>3) 第一天配置id  1,2,3
			 *          		  => 2 int =>array(0=>1,1=>4,3=>5) 第二天配置id  1,4,5
			 *          [type]    => int
			 *          1=>[req]  => [gold] =>int
			 *          	      => [silver]=>int
			 *           	      => [item] =>[itemId] =>int  数量
			 *                    => [num]  =>int  限制兑换数量
			 *           
			 *             [acq]  => [gold] =>int
			 *          	      => [silver]=>int
			 *           	      => [item] =>[itemId] =>int  数量
			 *           
			* */
		}
		return $arrConf;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */