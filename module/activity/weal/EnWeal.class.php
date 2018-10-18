<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWeal.class.php 242373 2016-05-12 09:00:44Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/weal/EnWeal.class.php $
 * @author $Author: GuohaoZheng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-12 09:00:44 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242373 $
 * @brief 
 *  
 **/
class EnWeal
{
	public static function readWealCSV($arr)
	{
		$csvIndex = 0;
		
		$confIndex = array(
				WealDef::ID => $csvIndex++,
				ActivityDef::BEGIN_TIME => $csvIndex++,
				ActivityDef::END_TIME=> $csvIndex++,
				ActivityDef::NEED_OPEN_TIME=>$csvIndex++,
				WealDef::OPEN => ($csvIndex+=5)-1,
				WealDef::ACOPY_NUM => $csvIndex++,
				WealDef::NCOPY_FUND  => $csvIndex++,
				WealDef::NCOPY_DROP_HERO_FRAG => $csvIndex++,
				WealDef::ECOPY_DROP =>  $csvIndex++,
				WealDef::FRIEND_LOVE =>  $csvIndex++,
				WealDef::GUILD_CONTRI =>  $csvIndex++,
				WealDef::GUILD_GOODS_SALE =>  $csvIndex++,

				WealDef::STAR_GIFT => $csvIndex++,
				WealDef::FLOP_CARD => $csvIndex++,
				WealDef::KA_CONSUME => $csvIndex++,
				WealDef::KA_INTERGRAL_LIMIT =>$csvIndex++,
				WealDef::KA_OPEN => $csvIndex++,
				WealDef::COPY_TEAM_GUILD => $csvIndex++,
				WealDef::MINERAL_PRODUCE => $csvIndex++,
				WealDef::NS_NCOPY_EXP_NEED_LV => $csvIndex++,
		    
		        WealDef::KA_CONF_TYPE => $csvIndex++,
		        WealDef::KA_RFR_TYPE => $csvIndex,
		);

		$arrayOneD = array();
		$arrayTwoD = array(
				WealDef::ACOPY_NUM,
				WealDef::NCOPY_FUND,
				WealDef::NCOPY_DROP_HERO_FRAG,
				WealDef::ECOPY_DROP,
				WealDef::GUILD_CONTRI,
				WealDef::GUILD_GOODS_SALE,
		        WealDef::KA_CONF_TYPE,
		);
		
		//本数组只用于展示和提醒这些字段在脚本解析的时候特殊处理或特殊判定了，没有其他的作用
		$arraySpecail = array(
				WealDef::GUILD_CONTRI,
				WealDef::ACOPY_NUM,
				WealDef::NCOPY_FUND,
				WealDef::KA_OPEN,
				
				
		);
		
		//================兼容模式
		$compatible = $confIndex;
		unset( $compatible[ActivityDef::BEGIN_TIME] );
		unset( $compatible[ActivityDef::END_TIME] );
		unset( $compatible[ActivityDef::NEED_OPEN_TIME] );
		foreach ( $compatible as $compKey => $compIndexNum )
		{
			if ( $compIndexNum > 3 )
			{
				$compatible[$compKey] = $compIndexNum - 3;
			}
		}
		
		if( count( $arr[0] ) <=  20 ) //只可能是=20的时候需要兼容，依赖于版本
		{
			$confIndex = $compatible;
		}
		//================兼容模式
		
		//==============兼容模式，如果没有新服福利活动就unset掉
		if ( count( $arr[0] ) < 24 )
		{
		    foreach ( $arr as $keyArr => $data )
		    {
		        $arr[$keyArr][23] = 9999;
		    }
		}
		
		//==============兼容模式，更改翻牌规则
		foreach ( $arr as $keyArr => $data )
		{
		    if ( !isset( $data[24] ) )
		    {
		        $arr[$keyArr][24] = array();
		    }
		    if ( !isset( $data[25] ) )
		    {
		        $arr[$keyArr][25] = 1;
		    }
		}
		
		
		$conflist = array();
		foreach ( $arr as $data )
		{
			if ( empty( $data[ 0 ] )|| empty( $data ))
			{
				break;
			}
			Logger::debug('data 0 is: %d, %s',$data[ 0 ], $data  );
			if ( intval( $data[$confIndex[WealDef::OPEN]] ) != 1 )
			{
				//如果配置是关闭的，略过
				continue;
			}
			
			$onePiece = array();
			foreach ( $confIndex as $key => $index )
			{
				if ( !isset( $data[$index] ) ) 
				{
				    throw new ConfigException( 'undefine index %d', $index );
				}
				
				if ( in_array( $key , $arrayOneD) ) 
				{
					if ( empty( $data[ $index ] ) )
					{
						$onePiece[$key] = array();
					}
					else
					{
						$onePiece[$key] = array_map('intval', explode( ',' , $data[$index]));
					}
				}
				else if ( in_array( $key , $arrayTwoD) ) 
				{
					if ( empty( $data[ $index ] ) )
					{
						$onePiece[$key] = array();
					}
					else 
					{
						$tmp4array2D = explode( ',' , $data[$index]);
						foreach ( $tmp4array2D as $key2 => $val2 )
						{
							$tmp4array2D[ $key2 ] = array_map( 'intval' , explode( '|' , $val2));
						}
						$onePiece[$key] = $tmp4array2D;
					}
				}
				else
				{
					$onePiece[$key] = intval( $data[$index] );
				}
			}
			
			$empty = true;
			foreach ( $onePiece as $keyafter => $valAfter )
			{
				if ( !empty( $valAfter ) )
				{
					$empty = false;
					
					//以下为对数组格式进行特殊处理,还有配置合法性的判定
					if( $keyafter == WealDef::GUILD_CONTRI )
					{
						$arrGuildContri = array();
						foreach ( $valAfter as $key3=>$val3 )
						{
							$arrGuildContri[$val3[0]] = array(
									$val3[1],$val3[2],
							);
						}
						
						$onePiece[$keyafter] = $arrGuildContri;
					}
					
					if( $keyafter == WealDef::ACOPY_NUM )
					{
						$arrAcopyNum = array();
						foreach ( $valAfter as $key4=>$val4 )
						{
							$arrAcopyNum[$val4[0]] = $val4[1];
						}
					
						$onePiece[$keyafter] = $arrAcopyNum;
					}
					
					if( $keyafter == WealDef::NCOPY_FUND || $keyafter == WealDef::KA_CONF_TYPE)
					{
						$arrNcopyFund = array();
						foreach ( $valAfter as $key5=>$val5 )
						{
							$arrNcopyFund[$val5[0]] = $val5[1];
						}
					
						$onePiece[$keyafter] = $arrNcopyFund;
					}
					
					if ( $keyafter == WealDef::KA_OPEN && $valAfter == 1 ) 
					{
						if( empty( $onePiece[ WealDef::KA_CONSUME ] )||empty($onePiece[ WealDef::KA_INTERGRAL_LIMIT ] ) )
						{
							throw new ConfigException( 'ka is open but conf is not enough' );
						} 
					}
				}
				
			}
			
			if ($empty)
			{
				//TODO 这里要保证所有字段的空以及0都是指没有配置,无其他含义
				continue;
			}
			
			$conflist[$onePiece['id']] = $onePiece;
		}
// 		if ( count($conflist) > 1 )
// 		{
// 			throw new ConfigException( 'just one piece is allowed during one period' );
// 		}
		
		if( empty( $conflist ) )
		{
			$conflist = array( 'dummy' => true );
		}
		
		return $conflist;
	}
	/**
	 * 因为返回的形式不同，所以当没有福利活动，没有该福利活动，数据不合法的情况会返回false，需要调用者判定一下
	 * @param unknown $wealType 具体类型详见：WealDef
	 * @throws FakeException
	 * @return boolean|mixed
	 * 活动副本次数
	 * {
	 * 		array(id,num),
	 * 		.
	 * 		.
	 * }
	 * 普通副本加成
	 * {
	 * 		array(type,num),
	 * 		.
	 * 		.
	 * }
	 * 军团贡献加长
	 * {
	 * 		key => array(int, int),
	 * 		.
	 * 		.
	 * }
	 * 
	 */
	public static function getWeal( $wealType )
	{
		Logger::debug('weak mark getweal');
		if ( !isset( WealDef::$type[$wealType] ) )
		{
			throw new FakeException( 'invalid weal type: %s', $wealType );
		}
		
		if ( WealDef::WEAL_OPT )
		{
			$wealInst = new Weal();
			$wealInfo = $wealInst->getWealConf( $wealType );
			Logger::debug('weal info are: %s',$wealInfo);
			return $wealInfo;
		}
		else 
		{
			if ( !EnActivity::isOpen( ActivityName::WEAL ) )
			{
				//没有福利活动
				return false;
			}
			$wealInMem = EnActivity::getConfByName( ActivityName::WEAL );
			$wealInMemFirst = current( $wealInMem );
			if ( empty( $wealInMemFirst ) )
			{
				return false;
			}
			if ( !isset( $wealInMemFirst[$wealType] ) )
			{
				return false;
			}
			return $wealInMemFirst[$wealType];
		}
		
	}
	
	public static function refreshWeal()
	{
		$wealInst = new Weal();
		$wealInst->refreshWealSession();
	}
	
	/**
	 * 增加积分（翻卡用）
	 * @param int $kaPointType 详见：KaDef
	 * @param int $num 进行几次这个活动
	 * @return fail , success 不需要处理
	 */
	public static function addKaPoints( $kaPointType, $num = 1 )
	{		
		Logger::debug('weak mark $kaPointType');
		if (!Weal::checkKaValid())
		{
			return 'fail';
		}
		
		$kaPointActConf = self::getKaConfType();
		
		$points = 0;
		if ( !empty( $kaPointActConf ) )
		{
		    if ( empty( $kaPointActConf[$kaPointType] ) )
		    {
		        Logger::warning('type %d conf empty.', $kaPointType);
		        return 'done';
		    }
		    $points = $kaPointActConf[$kaPointType] * $num;
		}
		else 
		{
		    $kaPointConf = btstore_get()->KAPOINT;
		    if (!isset( $kaPointConf[$kaPointType] ))
		    {
		        Logger::warning('type %d invalid', $kaPointType );
		        return 'fail';
		    }
		    $points = $kaPointConf[$kaPointType] * $num;
		}
		
		$kaObj = KaObj::getInstance();
		$kaInfo = $kaObj->getKaInfo();
		$kaLimit = EnWeal::getWeal( WealDef::KA_INTERGRAL_LIMIT );
		if ( $kaInfo['point_add'] >= $kaLimit )
		{
			return 'fail';
		}
		if ($kaInfo['point_add'] + $points >= $kaLimit)
		{
			$points = $kaLimit - $kaInfo['point_add'];
		}
		
		$kaObj->addKaPoint( $points );
		$kaObj->update();
		
		$uid = RPCContext::getInstance()->getUid();
		$kaInfoNow = $kaObj->getKaInfo(); 
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::WEAL_KA_POINTS, array( 'point_today' => $kaInfoNow['point_today'] ));
		
		return 'success';
	}
	
	public static function getNSWeal($uid = 0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		
		if (FALSE == ActivityNSLogic::inNS())
		{
			return 1;
		}
		
		if (FALSE == EnActivity::isOpen(ActivityName::WEAL))
		{
			return 1;
		}
		
		$conf = EnActivity::getConfByName(ActivityName::WEAL);
		
		$needLv = empty($conf['data'][WealDef::NS_WEAL_ID][WealDef::NS_NCOPY_EXP_NEED_LV]) ? 0 : $conf['data'][WealDef::NS_WEAL_ID][WealDef::NS_NCOPY_EXP_NEED_LV];
		
		$level = EnUser::getUserObj($uid)->getLevel();
		
		if ($level < $needLv)
		{
			return 1;
		}
		
		return WealDef::NCOPY_EXP_RATIO;
	}

	public static function getKaConfType()
	{
	    $wealConf = EnWeal::getWeal(WealDef::KA_CONF_TYPE);
	    
	    if ( false === $wealConf )
	    {
	        $wealConf = array();
	    }
	    
	    return $wealConf;
	}
	
	public static function getKaRfrType()
	{
	    $wealConf = EnWeal::getWeal(WealDef::KA_RFR_TYPE);
	    
	    if ( false === $wealConf )
	    {
	        $wealConf = KaDef::KA_RFR_TYPE_DAY;
	    }
	    
	    return $wealConf;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
