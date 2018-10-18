<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardLogic.class.php 259443 2016-08-30 09:35:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/RewardLogic.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-08-30 09:35:36 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259443 $
 * @brief 
 *  
 **/


class RewardLogic
{
	//已经通知的uid列表
	public static $arrNotifyUid = array();
	
	
	
	public static function sendReward($uid, $source, $reward, $db = '' )
	{
		Logger::trace('send reward. uid:%d, source:%d, reward:%s', $uid, $source, $reward);
		
		$needUpdateItem = false;
		//检查一下reward里面的东西
		foreach($reward as $key => $value)
		{
			if( empty($value) )
			{
				Logger::debug('invalid reward. key:%s, value:%s', $key, $value);
				unset($reward[$key]);
				continue;
			}
			switch ($key)
			{
				case RewardType::ARR_ITEM_ID:
					$needUpdateItem = true;
					if(!is_array($value))
					{
						throw new InterException('invalid reward. arrItemId need array');
					}
					break;	
								
				case RewardType::ARR_ITEM_TPL:
					if(!is_array($value))
					{
						throw new InterException('invalid reward. arrItemTpl need array');
					}
					foreach($value as $tplId => $num)
					{
						if($num == 0)
						{
							Logger::fatal('invalid reward. itemTpl:%d num=0', $tplId);
							unset($reward[$key][$tplId]);
						}
					}
					break;

				case RewardType::ARR_HERO_TPL:
					if(!is_array($value))
					{
						throw new InterException('invalid reward. arrHeroTpl need array');
					}
					foreach($value as $heroTplId => $heroNum)
					{
						if($heroNum == 0)
						{
							Logger::fatal('invalid reward. itemTpl:%d num=0', $tplId);
							unset($reward[$key][$heroTplId]);
						}
					}
					break;
					
				case RewardType::ARR_TF_TPL:
					if(!is_array($value))
					{
						throw new InterException('invalid reward. arrTreasureFragTpl need array');
					}
					foreach($value as $tplId => $num)
					{
						if($num == 0)
						{
							Logger::fatal('invalid reward. tfTpl:%d num=0', $tplId);
							unset($reward[$key][$tplId]);
						}
					}
					break;
					
				case RewardType::GOLD:			
					if( empty( RewardDef::$GOLD_STATISTICS_TYPE[$source] ) )		
					{
						throw new InterException('invalid reward. need gold type. source:%d, type:%s', $source, $key);
					}
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. gold need int');
					}
					$reward[$key] = intval($value);
					break;
					
				case RewardType::SILVER:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. silver need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::WM:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. wm need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::FAME_NUM:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. fame need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::HELL_POINT:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. hell point need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::CROSS_HONOR:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. cross honor need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::COPOINT:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. copoint need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::SOUL:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. soul need int');
					}
					$reward[$key] = intval($value);
					break;
					
				case RewardType::EXE:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. excution need int');
					}
					$reward[$key] = intval($value);
					break;
					
				case RewardType::STAMINA:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. stamina need int');
					}
					$reward[$key] = intval($value);
					break;
					
				case RewardType::PRESTIGE:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. prestige need int');
					}
					$reward[$key] = intval($value);
					break;
					
				case RewardType::JEWEL:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. jewel need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::HORNOR:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. honor need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::GRAIN:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. grain need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::COIN:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. coin need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::ZG:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. zg need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::TG:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. tg need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::TALLY_POINT:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. tally point need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::BOOK_NUM:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. book num need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::EXP_NUM:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. exp num need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::GUILD_CONTRI:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. contri need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::GUILD_EXP:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. guildExp need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::FS_EXP:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. fxExp need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::JH:
					if( !is_int($value) )
					{
						throw new InterException('invalid reward. jh need int');
					}
					$reward[$key] = intval($value);
					break;
				case RewardType::HELL_TOWER:
				    if( !is_int($value) )
				    {
				        throw new InterException('invalid reward. hell_tower need int');
				    }
				    $reward[$key] = intval($value);
				    break;
				case RewardDef::EXT_DATA:
					break;
				case RewardDef::MSG:
					break;
				case RewardDef::TITLE:
					break;
				default:
					throw new InterException('invalid reward type:%s', $key);
					break;
			}			
		}		
		
		$arrField = array(
				RewardDef::SQL_UID => $uid,
				RewardDef::SQL_SOURCE => $source,
				RewardDef::SQL_SEND_TIME => Util::getTime(),
				RewardDef::SQL_RECV_TIME => 0,
				RewardDef::SQL_DELETE_TIME => 0,
				RewardDef::SQL_VA_REWARD => $reward
				);
		
		//发的itemId要update
		if($needUpdateItem)
		{
			ItemManager::getInstance()->update();
		}
		$ret = RewardDao::insert($arrField, $db);
		
		
		//是否设置不推送通知
		if ( in_array($uid, self::$arrNotifyUid) == false && RewardCfg::$NO_CALLBACK == false )
		{
			//通知前端有新的邮件
			RPCContext::getInstance ()->sendMsg ( array (intval($uid) ), PushInterfaceDef::REWARD_NEW, array () );
			self::$arrNotifyUid[] = $uid;
		}

		return $ret;
	}
	
	public static function getRewardList($uid, $offset, $limit)
	{
		//取出奖励的数组
		$arrField = array(
				RewardDef::SQL_RID,
				RewardDef::SQL_SOURCE,
				RewardDef::SQL_SEND_TIME,
				RewardDef::SQL_VA_REWARD
		);
		
		$arrReward = array();
		//是要全拉的
		if (  $limit <= 0 )
		{
			$arrReward = RewardDao::getByUid($uid, $arrField );
		}
		else
		{
			$arrReward = RewardDao::getByUid($uid, $arrField, $offset, $limit);
		}
		
		if ( !empty( $arrReward ) )
		{
			foreach ( $arrReward as $key => $val )
			{
				$arrReward[ $key ][ RewardDef::EXPIR_TIME ] = intval( $val[ RewardDef::SQL_SEND_TIME ] ) + RewardCfg::REWARD_LIFE_TIME;
			}
		}
		

		//获取第一批奖励时，把系统补偿带上
		if($offset == 0 || $limit <= 0)
		{
			$arrPayBack = PayBackLogic::getAvailablePayBack($uid);				
			$arrActPayBack = ActPayBackLogic::getAvailableRewardList($uid);
			$arrReward = array_merge($arrPayBack, $arrReward, $arrActPayBack);
		}
	
		if ( empty( $arrReward ) )
		{
			return array();
		}
		
		//数据整理, 调整一下物品信息的数据结构
		$rewardList = self::_standardRewardList( $arrReward );
		
		Logger::trace('get reward list. uid:%d, rewardList:%s', $uid, $rewardList);
		
		return $rewardList;
	}
	

	
	public static function _standardRewardList( $arrRet )
	{
		$rewardList = array();
		
		if ( empty( $arrRet ) )
		{
			return $rewardList;
		}
		
		//把所有的物品一次拉入缓存，此后不再从数据库拉取
		$allItemId = array();
		foreach ( $arrRet as $onepieceRet )
		{
			if ( !empty( $onepieceRet[RewardDef::SQL_VA_REWARD][RewardType::ARR_ITEM_ID] ) )
			{
				$partItemId = $onepieceRet[RewardDef::SQL_VA_REWARD][RewardType::ARR_ITEM_ID];
				$allItemId = array_merge( $allItemId, $partItemId );
			}
		}
		if ( !empty( $allItemId ) )
		{
			ItemManager::getInstance()->getItems( $allItemId );
		}
		
		foreach($arrRet as $ret)
		{
			$arrItem = array();
			$arrHero = array();
			$arrTf = array();
			
			$reward = $ret[RewardDef::SQL_VA_REWARD];
			if( !empty( $reward[RewardType::ARR_ITEM_ID] ) )
			{
				$arrItemObj = ItemManager::getInstance()->getItems($reward[RewardType::ARR_ITEM_ID]);
				foreach( $arrItemObj as $item )
				{
					if( $item == null)
					{
						Logger::fatal('miss some item in rid:%d', $ret[RewardDef::SQL_RID]);
						continue;
					}
					$arrItem[] = array(
							'tplId' => $item->getItemTemplateID(),
							'num' => $item->getItemNum(),
					);
				}
				unset($reward[RewardType::ARR_ITEM_ID]);
			}
			if( !empty( $reward[RewardType::ARR_ITEM_TPL] ) )
			{
				foreach( $reward[RewardType::ARR_ITEM_TPL] as $tplId => $num )
				{
					$arrItem[] = array(
							'tplId' => $tplId,
							'num' => $num,
					);
				}
				unset($reward[RewardType::ARR_ITEM_TPL]);
			}
			if( !empty( $reward[RewardType::ARR_HERO_TPL] ) )
			{
				foreach( $reward[RewardType::ARR_HERO_TPL] as $herotplId => $heronum )
				{
					$arrHero[] = array(
							'tplId' => $herotplId,
							'num' => $heronum,
					);
				}
				unset($reward[RewardType::ARR_HERO_TPL]);
			}
			
			if( !empty( $reward[RewardType::ARR_TF_TPL] ) )
			{
				foreach( $reward[RewardType::ARR_TF_TPL] as $tfTplId => $tfnum )
				{
					$arrTf[] = array(
							'tplId' => $tfTplId,
							'num' => $tfnum,
					);
				}
				unset($reward[RewardType::ARR_TF_TPL]);
			}
			
				
			$reward['item'] = $arrItem;
			$reward['hero'] = $arrHero;
			$reward['treasfrag'] = $arrTf;
			$ret['va_reward'] = $reward;
			$rewardList[] = $ret;
		}
		
		return $rewardList;
	}
	
	public static function getInfoByArrRid( $uid, $arrRid)
	{
		//如果包含系统补偿，获取一下系统补偿的信息
		$arrPaybackId = array();
		$arrActPaybackId = array();
		foreach ( $arrRid as $key => $rid )
		{
			if ( $rid < ActPayBackDef::REWARD_ID_BASE )
			{
				$arrPaybackId[] = $rid;
				unset( $arrRid[ $key ] );
			}
			
			if ( $rid >= ActPayBackDef::REWARD_ID_BASE && $rid < RewardDef::RID_DIVISION )
			{
			    $arrActPaybackId[] = $rid;
			    unset( $arrRid[ $key ] );
			}
		}
		$arrPaybackInfo = array();
		if ( !empty( $arrPaybackId ) )
		{
			$arrPaybackInfo = PaybackLogic::getAvailableByArrId($uid, $arrPaybackId);			
		}
		$arrActPaybackInfo = array();
		if ( !empty( $arrActPaybackId ) )
		{
		    $arrActPaybackInfo = ActPayBackLogic::getAvailableByArrId($uid, $arrActPaybackId);
		}
		
		//获取一般的奖励信息
		$arrRewardInfo = array();
		if ( !empty( $arrRid ) )
		{
			$arrField = array(
					RewardDef::SQL_RID,
					RewardDef::SQL_SOURCE,
					RewardDef::SQL_RECV_TIME,
					RewardDef::SQL_DELETE_TIME,
					RewardDef::SQL_VA_REWARD
			);
			$arrRewardInfo = RewardDao::getByRidArr( $uid, $arrRid, $arrField );			
		}
		
		return array($arrPaybackInfo, $arrRewardInfo, $arrPaybackId, $arrRid, $arrActPaybackInfo, $arrActPaybackId);
	}
	
	public static function receiveByArrRid( $uid, $allId )
	{
		Logger::trace('receivePartReward. uid:%d, arrRid:%s', $uid, $allId);
		
		if ( empty( $allId ) )
		{
			throw new FakeException( 'ridArr should not be empty' );
		}
		if ( count( $allId ) > RewardDef::RECEIVE_NUM )
		{
			throw new FakeException( 'ridArr should not > %d', RewardDef::RECEIVE_NUM );
		}
		
		list($arrPaybackInfo, $arrRewardInfo, $arrPaybackId, $arrRid, $arrActPaybackInfo, $arrActPaybackId) = self::getInfoByArrRid($uid, $allId);
		if(count($arrPaybackInfo) != count($arrPaybackId))
		{
			throw new FakeException('maybe some payback out of date or already received. arrId:%s, arrInfo:%s', $arrPaybackId, $arrPaybackInfo);
		}
		if(count($arrRewardInfo) != count($arrRid))
		{
			throw new FakeException('some reward not found. arrId:%s, arrInfo:%s', $arrRid, $arrRewardInfo);
		}
		if (count($arrActPaybackInfo) != count($arrActPaybackId))
		{
		    Logger::warning('some actPayBack reward not found or over time. arrId:%s, arrInfo:%s', $arrActPaybackId, $arrActPaybackInfo);
		}
		
		
		$arrRewardInfo = array_merge( $arrPaybackInfo, $arrRewardInfo, $arrActPaybackInfo );
		
		//将所有奖励全部发给用户
		$arrRewardValue = self::mergeReward($arrRewardInfo);
		$ret = self::reward($uid, $arrRewardValue);
		if( $ret['ret'] != 'ok' )
		{
			return $ret['ret'];
		}		
		$allItemId = $ret['arrItemId'];
		$fragAdd = $ret['fragAdd'];
		$honorAdd = $ret['honorAdd'];
		$contriAdd = $ret['contriAdd'];
		$guildExpAdd = $ret['guildExpAdd'];
		$grainAdd = $ret['grainAdd'];
		$coinAdd = $ret['coinAdd'];
		$zgAdd = $ret['zgAdd'];
		$hellPointAdd = $ret['hellPointAdd'];
		$crossHonorAdd = $ret['crossHonorAdd'];
		$copointAdd = $ret['copointAdd'];
		
		$step = 0;
		try 
		{
			//先标记，奖励已经领了，然后在实际发东西
			$step = RewardDef::REWARD_STEP_SET_PAYBACK;	
			if ( !empty( $arrPaybackId ) )
			{
				$ret = PaybackLogic::insertPayBackUser($uid, $arrPaybackId);
				if(!$ret)
				{
					throw new InterException('set payback failed');
				}
			}
			if ( !empty( $arrActPaybackId ) )
			{
			    $actPayBackObj = ActPayBackObj::getInstance($uid);
			    $actPayBackObj->receiveRewards($arrActPaybackId);
			    $actPayBackObj->update();
			}
			
			$step = RewardDef::REWARD_STEP_SET_REWARD;
			if ( !empty( $arrRid ) )
			{
				$arrField = array(RewardDef::SQL_RECV_TIME => Util::getTime());
				$ret = RewardDao::updateByArrId($uid, $arrField, $arrRid);	
				if(!$ret)
				{
					throw new InterException('set reward failed');
				}			
			}
						
			$step = RewardDef::REWARD_STEP_USER;
			EnUser::getUserObj( $uid )->update();
			
			$step = RewardDef::REWARD_STEP_TREASFRAG;
			
			if ( $fragAdd )
			{
				$inst = FragseizeObj::getInstance($uid);
				$inst->updateFrags();
			}
			
			if ( $contriAdd || $grainAdd || $zgAdd )
			{
				$guildMemObj = GuildMemberObj::getInstance($uid);
				$guildMemObj->update();
			}
			if ( $crossHonorAdd )
			{
				$serverId= Util::getServerIdOfConnection();
				$pid =  WorldCompeteUtil::getPid($uid, true);
				WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid)->update();
			}
			
			if ( $copointAdd )
			{
				$serverId= Util::getServerIdOfConnection();
				$pid =  EnUser::getUserObj($uid)->getPid();
				CountryWarCrossUser::getInstance($serverId, $pid)->update();
			}
			
			if( $coinAdd )
			{
				PassObj::getInstance( $uid )->update();
			}
			
			if ( $honorAdd != 0 )
			{
				EnCompete::addHonor($uid, $honorAdd);
			}
				
			if( $guildExpAdd !=0 )
			{
				EnGuild::addGuildExp($uid, $guildExpAdd);
			}
			
			if ( $hellPointAdd != 0 ) 
			{
				EnWorldPass::addHellPoint($uid, $hellPointAdd);
			}
			
			if(!empty($allItemId))
			{
				$step = RewardDef::REWARD_STEP_ITEM;
				ItemManager::getInstance()->update();
				$step = RewardDef::REWARD_STEP_BAG;
				BagManager::getInstance()->getBag($uid)->update();
			}
		}
		catch ( Exception $e  )
		{
			//根据此日志补发奖励
			Logger::fatal( 'receiveByRidArr failed. uid:%d step:%d  arrRid:%s arrRewardValue:%s, allItemId:%s, err:%s', 
				$uid, $step, $allId, $arrRewardValue, $allItemId, $e->getMessage());
			throw $e;
		}

		
		return 'ok';
	}

	
	public static function mergeReward($arrRewardInfo)
	{
		//检查，合并奖励信息
		$arrGold = array();  //金币分类型处理
		$silver = 0;
		$wm = 0;
		$fame = 0;
		$hellPoint = 0;
		$crossHonor = 0;
		$copoint = 0;
		$soul = 0;
		$jewel = 0;
		$prestige = 0;
		$exe = 0;
		$stamina = 0;
		$honor = 0;
		$grain = 0;
		$coin = 0;
		$zg = 0;
		$tg = 0;
		$contri = 0;
		$guildExp = 0;
		$fsExp = 0;
		$jh = 0;
		$tallyPoint = 0;
		$book = 0;
		$exp = 0;
		$arrItemTpl = array();
		$arrItemId = array();
		$arrHeroTpl = array();
		$arrTfTpl = array();
		$hellTower = 0;
		
		foreach($arrRewardInfo as $rewardInfo)
		{
			$rid = $rewardInfo[RewardDef::SQL_RID];
			//逻辑上的合法性
			if ( $rewardInfo[ RewardDef::SQL_RECV_TIME ] > 0 )
			{
				throw new FakeException( 'rid: %d has beed received', $rid );
			}
			if ( $rewardInfo[ RewardDef::SQL_DELETE_TIME ] > 0  )
			{
				throw new FakeException( 'rid: %d has been deleted', $rid );
			}
			$source = $rewardInfo[RewardDef::SQL_SOURCE];
			$rewardValue = $rewardInfo[ RewardDef::SQL_VA_REWARD ];
			if ( empty( $rewardValue ) )
			{
				throw new InterException( 'no reward for rid: %d', $rid );
			}
			
			foreach($rewardValue as $type => $value)
			{
				switch($type)
				{
					case RewardType::GOLD:
						$statistType = RewardDef::$GOLD_STATISTICS_TYPE[$source];
						if(isset($arrGold[$statistType]))
						{
							$arrGold[$statistType] += $value;
						}
						else
						{
							$arrGold[$statistType] = $value;
						}
						break;
					case RewardType::SILVER:
						$silver += $value;
						break;
					case RewardType::WM:
						$wm += $value;
						break;
					case RewardType::FAME_NUM:
						$fame += $value;
						break;
					case RewardType::HELL_POINT:
						$hellPoint += $value;
						break;
					case RewardType::CROSS_HONOR:
						$crossHonor += $value;
						break;
					case RewardType::COPOINT:
						$copoint += $value;
						break;
					case RewardType::SOUL:
						$soul += $value;
						break;
					case RewardType::EXE:
						$exe += $value;
						break;
					case RewardType::STAMINA:
						$stamina += $value;
						break;
					case RewardType::JEWEL:
						$jewel += $value;
						break;
					case RewardType::PRESTIGE:
						$prestige += $value;
						break;
					case RewardType::HORNOR:
						$honor += $value;
						break;
					case RewardType::GRAIN:
						$grain += $value;
						break;
					case RewardType::COIN:
						$coin += $value;
						break;
					case RewardType::ZG:
						$zg += $value;
						break;
					case RewardType::TG:
						$tg += $value;
						break;
					case RewardType::TALLY_POINT:
						$tallyPoint += $value;
						break;
					case RewardType::BOOK_NUM:
						$book += $value;
						break;
					case RewardType::EXP_NUM:
						$exp += $value;
						break;
					case RewardType::GUILD_CONTRI:
						$contri += $value;
						break;
					case RewardType::GUILD_EXP:
						$guildExp += $value;
						break;
					case RewardType::FS_EXP:
						$fsExp += $value;
						break;
					case RewardType::JH:
						$jh += $value;
						break;
					case RewardType::HELL_TOWER:
					    $hellTower += $value;
					    break;
					case RewardType::ARR_ITEM_ID:
						$arrItemId = array_merge($arrItemId, $value);
						break;
					case RewardType::ARR_ITEM_TPL:
						foreach($value as $tplId => $num)
						{
							if(isset($arrItemTpl[$tplId]))
							{
								$arrItemTpl[$tplId] += $num;
							}
							else
							{
								$arrItemTpl[$tplId] = $num;
							}
						}
						break;
					case RewardType::ARR_HERO_TPL:
						foreach($value as $tplId => $num)
						{
							if(isset($arrHeroTpl[$tplId]))
							{
								$arrHeroTpl[$tplId] += $num;
							}
							else
							{
								$arrHeroTpl[$tplId] = $num;
							}
						}
						break;
					case RewardType::ARR_TF_TPL:
						foreach($value as $tplId => $num)
						{
							if(isset($arrTfTpl[$tplId]))
							{
								$arrTfTpl[$tplId] += $num;
							}
							else
							{
								$arrTfTpl[$tplId] = $num;
							}
						}
						break;
								
					case RewardDef::EXT_DATA:
					case PayBackDef::PAYBACK_TYPE:
					case PayBackDef::PAYBACK_MSG:
					case RewardDef::MSG:
					case RewardDef::TITLE:
						break;
					default:
						Logger::fatal('invalid reward type:%s, rewardInfo:%s', $type, $rewardInfo);
						break;
				}
			}			
		}

		return array(
				'arrGold' => $arrGold,
				'silver' => $silver,
				'wm' => $wm,
				'fame' => $fame,
				'hellPoint' => $hellPoint,
				'crossHonor' => $crossHonor,
				'copoint' => $copoint,
				'soul' => $soul,
				'execution' => $exe,
				'stamina' => $stamina,
				'jewel' => $jewel,
				'prestige' => $prestige,
				'honor' => $honor,
				'grain' => $grain,
				'coin' => $coin,
				'zg' => $zg,
				'tg' => $tg,
				'contri' => $contri,
				'guildExp' => $guildExp,
				'arrItemTpl' => $arrItemTpl,
				'arrItemId' => $arrItemId,
				'arrHeroTpl' => $arrHeroTpl,
				'arrTfTpl' => $arrTfTpl,
				'fsExp' => $fsExp,
				'jh' => $jh,
				'tally_point' => $tallyPoint,
				'book' => $book,
		        'tower_num' => $hellTower,
				'exp' => $exp,
				);		
	}
	
	public static function reward( $uid, $arrRewardValue )
	{
		$arrGold = $arrRewardValue['arrGold'];
		$silver = $arrRewardValue['silver'];
		$wm = $arrRewardValue['wm'];
		$fame = $arrRewardValue['fame'];
		$hellPoint = $arrRewardValue['hellPoint'];
		$crossHonor = $arrRewardValue['crossHonor'];
		$copoint = $arrRewardValue['copoint'];
		$soul = $arrRewardValue['soul'];
		$exe = $arrRewardValue['execution'];
		$stamina = $arrRewardValue['stamina'];
		$jewel = $arrRewardValue['jewel'];
		$prestige = $arrRewardValue['prestige'];
		$honor = $arrRewardValue['honor'];
		$grain = $arrRewardValue['grain'];
		$coin = $arrRewardValue['coin'];
		$zg = $arrRewardValue['zg'];
		$tg = $arrRewardValue['tg'];
		$contri = $arrRewardValue['contri'];
		$guildExp = $arrRewardValue['guildExp'];
		$arrItemTpl = $arrRewardValue['arrItemTpl'];
		$arrItemId = $arrRewardValue['arrItemId'];
		$arrHeroTpl = $arrRewardValue[ 'arrHeroTpl' ];
		$arrTfTpl = $arrRewardValue['arrTfTpl'];
		$fsExp = $arrRewardValue['fsExp'];
		$jh = $arrRewardValue['jh'];
		$tallyPoint = $arrRewardValue['tally_point'];
		$book = $arrRewardValue['book'];
		$hellTower = $arrRewardValue['tower_num'];
		$exp = $arrRewardValue['exp'];
		
		
		//物品奖励
		if( !empty($arrItemTpl) )
		{
			$arrItemId = array_merge($arrItemId,
					ItemManager::getInstance()->addItems($arrItemTpl) );
		}
		if(!empty($arrItemId))
		{
			$bag = BagManager::getInstance()->getBag();
			if ( $bag->addItems($arrItemId) == false )
			{
				Logger::warning('receive reward failed. add item');
				return array('ret' => 'bag_full');
			}
		}
		
		$userObj = EnUser::getUserObj($uid);
		$guildMemObj = GuildMemberObj::getInstance($uid);
		
		//英雄奖励
		$heroMgr = $userObj->getHeroManager();
		if(!empty($arrHeroTpl))
		{
			$numTotal = 0;
			foreach ( $arrHeroTpl as $htid => $num )
			{
				$numTotal += $num;
			}
			$arrHid = $heroMgr->addNewHeroes( $arrHeroTpl );
			if ( count( $arrHid ) != $numTotal )
			{
				throw new InterException('receive reward failed. add hero');				
			}
		}
		
		//数值奖励
		foreach ($arrGold as $type => $value)
		{
			if( $value > 0 &&  ! $userObj->addGold( $value, $type) )
			{
				throw new InterException('receive reward failed: add gold');
			}
		}

		if( $silver > 0 && ! $userObj->addSilver($silver) )
		{
			throw new InterException('receive reward failed: add silver');
		}
		if( $wm > 0 && ! $userObj->addWmNum($wm) )
		{
			throw new InterException('receive reward failed: add wm');
		}
		if( $jh > 0 && ! $userObj->addJH($jh))
		{
			throw new InterException('receive reward failed: add jh');
		}
		
		
		if( $fame >0 && ! $userObj->addFameNum( $fame ) )
		{
			throw new InterException('receive reward failed: add fame');
		}
		
		if( $soul > 0 &&  ! $userObj->addSoul($soul) )
		{
			throw new InterException('receive reward failed: add soul');
		}
		if( $exe > 0 &&  ! $userObj->addExecution( $exe ) )
		{
			throw new InterException('receive reward failed: add execution');
		}
		if( $stamina > 0 &&  ! $userObj->addStamina( $stamina ) )
		{
			throw new InterException('receive reward failed: add stamina');
		}
		
		if( $jewel > 0 &&  ! $userObj->addJewel($jewel) )
		{
			throw new InterException('receive reward failed: add jewel');
		}
		
		if ( $prestige > 0 && !$userObj->addPrestige( $prestige ) )
		{
			throw new InterException('receive reward failed: add prestige');
		}
		
		if ( $tg > 0 && !$userObj->addTgNum( $tg ) ) 
		{
			throw new InterException('receive reward failed: add tg');
		}
		if( $fsExp > 0 && !$userObj->addFsExp( $fsExp ))
		{
			throw new InterException('receive reward failed: add fsExp');
		}
		
		if ( $tallyPoint > 0 && !$userObj->addTallyPoint( $tallyPoint ) )
		{
			throw new InterException('receive reward failed: add tally point');
		}
		
		if ( $book > 0 && !$userObj->addBookNum( $book ) )
		{
			throw new InterException('receive reward failed: add book');
		}
		
		if ( $hellTower > 0 && !$userObj->addTowerNum( $hellTower ) )
		{
		    throw new InterException("receive reward failed: add tower");
		}
		
		if ( $exp > 0 && !$userObj->addExp($exp) )
		{
			throw new InterException("receive reward failed: add exp");
		}
		
		//加蛋疼的碎片
		$fragAdd = false;
		if (!empty($arrTfTpl))
		{
			$inst = FragseizeObj::getInstance($uid);
			$inst->addFrags( $arrTfTpl );
			$fragAdd = true;
		}
		
		$contriAdd = false;
		if ( $contri > 0 )
		{
			$guildMemObj->addContriPoint( $contri );
			$contriAdd = true;
		}
		
		$grainAdd = false;
		if( $grain > 0 )
		{
			$guildMemObj->addGrainNum( $grain );
			$grainAdd = true;
		}
		
		$coinAdd = false;
		if( $coin > 0 )
		{
			$passObj = EnPass::getPassObj( $uid );
			$passObj->addCoin( $coin );
			$coinAdd = true;
		}
		
		$zgAdd = false;
		if( $zg > 0 )
		{
			$guildMemObj->addZgNum( $zg );
			$zgAdd = true;
		}
		
		$crossHonorAdd = false;
		if($crossHonor > 0)
		{
			$serverId= Util::getServerIdOfConnection();
			$pid = WorldCompeteUtil::getPid($uid, true);
			$WorldCompeteInnerObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
			$WorldCompeteInnerObj->addCrossHonor($crossHonor);
			$crossHonorAdd = true;
		}
		$copointAdd = false;
		if( $copoint > 0)
		{
			$serverId= Util::getServerIdOfConnection();
			$pid = EnUser::getUserObj($uid)->getPid();
			$countryWarCrossUser = CountryWarCrossUser::getInstance($serverId, $pid);
			$countryWarCrossUser->addCopoint($copoint);
			$copointAdd = true;
		}
		
		$honorAdd = 0;
		if ( $honor > 0  )
		{
			$honorAdd = $honor;
		}
		
		$guildExpAdd = 0;
		if ( $guildExp > 0 )
		{
			$guildExpAdd = $guildExp;
		}
		
		$hellPointAdd = 0;
		if ( $hellPoint > 0 ) 
		{
			$hellPointAdd = $hellPoint;
		}
		
		//返回背包的信息让请求发起方更新,还有一些最好是最后加（更新）的东西
		return array(
				'ret' => 'ok',
				'arrItemId' => $arrItemId,
				'fragAdd' => $fragAdd,
				'contriAdd' =>$contriAdd,
				'grainAdd' => $grainAdd,
				'coinAdd' => $coinAdd,
				'zgAdd' => $zgAdd,
				'honorAdd' => $honorAdd,
				'guildExpAdd' => $guildExpAdd,
				'hellPointAdd' => $hellPointAdd,
				'crossHonorAdd' => $crossHonorAdd,
				'copointAdd' => $copointAdd,
				
				);
		
	}
	
	/**
	 * 获取奖励列表 （注：$offset+$limit不能超过100）
	 * @param unknown $uid
	 * @param unknown $offset
	 * @param unknown $limit
	 * @return Ambigous <multitype:, multitype:multitype:multitype:unknown   >
	 */
	public static function getReceivedList($uid, $offset, $limit)
	{
		//取出奖励的数组
		$arrField = array(
				RewardDef::SQL_RID,
				RewardDef::SQL_SOURCE,
				RewardDef::SQL_RECV_TIME,
				RewardDef::SQL_VA_REWARD
		);
		
		$arrReceivedFromCenter = RewardDao::getReceivedListByUidTime($uid, $arrField, 0, $limit+$offset);
		
		$arrReceivedFromPayBack = self::getReceivedFromPayBack($uid, 0, $limit+$offset);
		
		$arrReceivedFromActPayBack = self::getReceivedFromActPayBack($uid);
		
		$ret = array_merge($arrReceivedFromPayBack, $arrReceivedFromCenter, $arrReceivedFromActPayBack);
		
		if (empty($ret))
		{
			return array();
		}
		
		foreach ($ret as $key => $value)
		{
			$arrRecvTime[$key] = $value[RewardDef::SQL_RECV_TIME];
			$arrRid[$key] = $value[RewardDef::SQL_RID];
		}
		
		array_multisort($arrRecvTime, SORT_DESC, $arrRid, SORT_DESC, $ret);
		
		$ret = array_slice($ret, $offset, $limit);
		
		$ret = self::_standardRewardList( $ret );
		
		return $ret;
	}

	public static function getReceivedFromPayBack($uid, $offset, $limit)
	{
		$selectField = array(
				PayBackDef::PAYBACK_SQL_UID,
				PayBackDef::PAYBACK_SQL_PAYBACK_ID,
				PayBackDef::PAYBACK_SQL_TIME_EXECUTE
		);
		
		$wheres = array(
				array(PayBackDef::PAYBACK_SQL_UID, '=', $uid),
		);
		
		$arrReceivedFromPayBackUser = PayBackDAO::getLastReceivedFromPBUT($selectField, $wheres, $offset, $limit);
		
		if (empty($arrReceivedFromPayBackUser))
		{
			return array();
		}
		
		$arrPayBackId = Util::arrayExtract($arrReceivedFromPayBackUser, PayBackDef::PAYBACK_SQL_PAYBACK_ID);
		
		$selectField = array(
				PayBackDef::PAYBACK_SQL_PAYBACK_ID,
				PayBackDef::PAYBACK_SQL_ARRY_INFO
		);
		
		$wheres = array(
				array(PayBackDef::PAYBACK_SQL_PAYBACK_ID, 'IN', $arrPayBackId),
		);
		
		$arrPayBackInfo = PayBackDAO::getFromPayBackInfoTable($selectField, $wheres);
		
		$arrPayBackInfo = Util::arrayIndexCol($arrPayBackInfo, PayBackDef::PAYBACK_SQL_PAYBACK_ID, PayBackDef::PAYBACK_SQL_ARRY_INFO);
		
		$arrReceivedFromPayBack = array();
		foreach ($arrReceivedFromPayBackUser as $key => $value)
		{
			$arrReceivedFromPayBack[] = array(
					RewardDef::SQL_RID => $value[PayBackDef::PAYBACK_SQL_PAYBACK_ID],
					RewardDef::SQL_SOURCE => RewardSource::SYSTEM_COMPENSATION,
					RewardDef::SQL_RECV_TIME => $value[PayBackDef::PAYBACK_SQL_TIME_EXECUTE],
					RewardDef::SQL_VA_REWARD => $arrPayBackInfo[$value[PayBackDef::PAYBACK_SQL_PAYBACK_ID]],
			);
		}
		
		return $arrReceivedFromPayBack;
	}
	
	public static function getReceivedFromActPayBack($uid)
	{
	    $arrField = ActPayBackDef::$ALL_SQL_FIELD;
	    $arrReceivedInfo = ActPayBackDao::getInfo($uid, $arrField);
	    
	    if ( empty($arrReceivedInfo) )
	    {
	        return array();
	    }
	    
	    if ( TRUE == EnActivity::isOpen( ActivityName::ACTPAYBACK ) )
	    {
	        $conf = EnActivity::getConfByName(ActivityName::ACTPAYBACK);
	        
	        $startTime = $conf['start_time'];
	        $endTime = $conf['end_time'];
	        
	        if ( $arrReceivedInfo[ActPayBackDef::SQL_REFRESH_TIME] < $startTime )
	        {
	            return array();
	        }
	    }
	    
	    $arrReceivedId = array_keys($arrReceivedInfo['va_data']['rewarded']);
	    
	    $arrReceivedRewardInfo = ActPayBackLogic::getPayBackByArrRidAndTime($arrReceivedId, $arrReceivedInfo[ActPayBackDef::SQL_REFRESH_TIME]);
	    
	    $arrRet = array();
	    foreach ($arrReceivedRewardInfo as $rid => $value)
	    {
	        $arrRet[] = array(
	            RewardDef::SQL_RID => $rid,
	            RewardDef::SQL_SOURCE => RewardSource::ACT_PAY_BACK_REWARD,
	            RewardDef::SQL_RECV_TIME => $arrReceivedInfo['va_data']['rewarded'][$rid],
	            RewardDef::SQL_VA_REWARD => $value[RewardDef::SQL_VA_REWARD],
	        );
	    }
	    
	    return $arrRet;
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */