<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardUtil.class.php 259766 2016-08-31 10:00:19Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/RewardUtil.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-31 10:00:19 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259766 $
 * @brief 本类最大坑：不要让策划配类型为6和10的奖励（有替代类型）
 *  
 **/
class RewardUtil
{
	/**
	 * 根据id发奖励，用户和背包均没有更新
	 * @param int $uid			用户uid
	 * @param int $rewardId		奖励id
	 * @param int $source		奖励来源，添加金币时使用
	 * 
	 * @return 
	 * array(
	 *			'userModify' => bool, 	用户信息是否改变
	 *			'bagMofify' => bool,   	背包信息是否改变
	 *			'rewardInfo' => array,	返回奖励的信息（背包的信息靠推）
	 *	) 
	 */
	public static function rewardById($uid, $rewardId, $source, $tmpBag = false, $updateNow = true )
	{
		if ( $rewardId < 0 )
		{
			throw new InterException( 'rewardId should > 0' );
		}
		
		$cfg = btstore_get()->ONLINE_GIFT;
		if ( !isset( $cfg[ $rewardId ][ 'rewardArr' ] ) )
		{
			throw new ConfigException( 'no rewardInfo in such rewardId: %d', $rewardId );
		}
		$rewardArr = $cfg[ $rewardId ][ 'rewardArr' ];
		
		return self::reward($uid, $rewardArr, $source, $tmpBag, $updateNow);
	}
	
	/**
	 * 发奖：并未进行用户和背包的update，称为： type、value直接发
	 * @param int $uid				要发给的uid
	 * @param array $rewardArr		奖励数组
	 * {
	 * 		  array(
	 * 				'type' => RewardConfType::SILVER,
	 * 				'val'  => int,	
	 * 				),
	 * 		...
	 * 		...
	 * 		  array(
	 * 				'type' => RewardConfType::ITEM,//此类暂时只支持一个id，默认数量为1
	 * 				'val'  => int,	
	 * 				),
	 * 		  array(
	 * 				'type' => RewardConfType::ITEM_MULTI,
	 * 				'val'  => array(
	 * 									array( $itemTid, $itemNum ),
	 * 									array( $itemTid, $itemNum ),
	 * 									...				
	 * 								),	
	 * 				),
	 * }
	 * @param int $source			奖励来源，加金币时使用
	 * @param bool $tmpBag			是否往临时背包里塞
	 * @param bool $updateNow		是否要立即更新（如果选择不要立即更新的话还要再调用updateReward方法）
	 * 
	 * @return 
	 * array(
	 *			'rewardInfo' => array,	返回奖励的信息（背包的信息靠推）
	 *			注意！： 提醒策划陪奖励不要再用6和10两个类型
	 * 			注意! ：该方法支持先加后update，除了比武荣誉,军团贡献和建设度
	 *			最好是一切做完了之后再调用该方法
	 *	) 
	 */
	public static function reward( $uid , $rewardArr, $source, $tmpBag = false, $updateNow = true )
	{
		$res = array();
		$bagModify = false;
		$userModify = false;
		$tFragModify = false;
		$grainModify = false;
		$coinModify = false;
		$zgModify = false;
		$crossHonorModify = false;
		$copointModify = false;
		
		if ( empty( $rewardArr ) )
		{
			return $ret = array( 
					'userModify' => $userModify,
					'bagModify' => $bagModify, 
					'rewardInfo' => $res 
				);	
		}
		Logger::debug( 'rewardArr is: %s', $rewardArr );
		
		$user = EnUser::getUserObj( $uid );
		$level = $user->getLevel();
		
		$bag = null;
		$arrItem = array();
		$arrHero = array();
		$arrTreasureFrag = array();
		$honor = 0;
		$contri = 0;
		$guildExp = 0;
		$grain = 0;
		$coin = 0;
		$zg = 0;
		$hellPoint = 0;
		$crossHonor = 0;
		$copoint = 0;
		
		foreach ( $rewardArr as $rewardVal )
		{
			switch ( $rewardVal[ 'type' ] )
			{
				case RewardConfType::SILVER:
					$user->addSilver( $rewardVal[ 'val' ] );
					self::addKeyValue($res, 'silver', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::SOUL:
					$user->addSoul( $rewardVal[ 'val' ] );
					self::addKeyValue($res, 'soul', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::JEWEL:
					$user->addJewel($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'jewel', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::PRESTIGE:
					$user->addPrestige($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'prestige', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::TG:
					$user->addTgNum($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'tg', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::TALLY_POINT:
					$user->addTallyPoint($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'tally_point', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::BOOK:
					$user->addBookNum($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'book', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::EXP:
					$user->addExp($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'exp', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::JH:
					$user->addJH($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'jh', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::WM:
					$user->addWmNum($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'wm', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::FAME:
					$user->addFameNum($rewardVal[ 'val' ]);
					self::addKeyValue($res, 'fame', $rewardVal[ 'val' ]);
					$userModify = true;
					break;					
				case RewardConfType::HELL_POINT:
					$hellPoint += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'hellPoint', $hellPoint );
					break;
				case RewardConfType::CROSS_HONOR:
					$crossHonor += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'crossHonor', $crossHonor );
					$crossHonorModify = true;
					break;
				case RewardConfType::COPOINT:
					$copoint += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'copoint', $copoint );
					$copointModify = true;
					break;
				case RewardConfType::GOLD:
					$user->addGold( $rewardVal[ 'val' ], $source );
					self::addKeyValue($res, 'gold', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::EXECUTION:
					$user->addExecution( $rewardVal[ 'val' ] );
					self::addKeyValue($res, 'execution', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::STAMINA :
					$user->addStamina( $rewardVal[ 'val' ] );
					self::addKeyValue($res, 'stamina', $rewardVal[ 'val' ]);
					$userModify = true;
					break;
				case RewardConfType::SILVER_MUL_LEVEL:
					$silver = $rewardVal[ 'val' ] * $level;
					$user->addSilver( $silver );
					self::addKeyValue($res, 'silver', $silver );
					$userModify = true;
					break;
				case RewardConfType::EXP_MUL_LEVEL:
					$exp = $rewardVal[ 'val' ] * $level;
					$user->addExp( $exp );
					self::addKeyValue($res, 'exp', $exp );
					$userModify = true;
					break;
				case RewardConfType::SOUL_MUL_LEVEL:
					$soul = $rewardVal[ 'val' ] * $level;
					$user->addSoul( $soul );
					self::addKeyValue($res, 'soul', $soul);
					$userModify = true;
					break;
				case RewardConfType::ITEM:
					if ( isset( $arrItem[ $rewardVal[ 'val' ] ] ) )
					{
						$arrItem[ $rewardVal[ 'val' ] ] += 1 ;
					}
					else 
					{
						$arrItem[ $rewardVal[ 'val' ]] = 1 ;
					}
					break;
				case RewardConfType::ITEM_MULTI:

					foreach ( $rewardVal[ 'val' ] as $rewardItem )
					{
						if ( isset( $arrItem[ $rewardItem[ 0 ] ] ) )
						{
							$arrItem[ $rewardItem[ 0 ] ] += intval( $rewardItem[ 1 ] );
						}
						else 
						{
							$arrItem[ $rewardItem[ 0 ] ] = intval( $rewardItem[ 1 ] );
						}
					}
					break;
				case RewardConfType::HERO:
					if( isset( $arrHero[$rewardVal[ 'val' ]] ) )
					{
						$arrHero[ $rewardVal[ 'val' ] ] += 1;						
					}
					else
					{
						$arrHero[ $rewardVal[ 'val' ] ] = 1;
					}					
					$userModify = true;
					break;
					
				case RewardConfType::HERO_MULTI:
					foreach ( $rewardVal[ 'val' ] as $rewardHero )
					{
						if ( isset( $arrHero[ $rewardHero[ 0 ] ] ) )
						{
							$arrHero[ $rewardHero[ 0 ] ] += intval( $rewardHero[ 1 ] );
						}
						else 
						{
							$arrHero[ $rewardHero[ 0 ] ] = intval( $rewardHero[ 1 ] );
						}
					}
					$userModify = true;
					break;
					
				case RewardConfType::TREASURE_FRAG_MULTI:

					foreach ( $rewardVal[ 'val' ] as $rewardTreasureFrag )
					{
						if ( isset( $arrTreasureFrag[ $rewardTreasureFrag[ 0 ] ] ) )
						{
							$arrTreasureFrag[ $rewardTreasureFrag[ 0 ] ] += intval( $rewardTreasureFrag[ 1 ] );
						}
						else
						{
							$arrTreasureFrag[ $rewardTreasureFrag[ 0 ] ] = intval( $rewardTreasureFrag[ 1 ] );
						}
					}
					$tFragModify = true;
					break;
				case RewardConfType::GUILD_CONTRI :
					$contri += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'contri', $contri );
					break;
				case RewardConfType::GUILD_EXP :
					$guildExp += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'guildExp', $guildExp );
					break;
				case RewardConfType::HORNOR :
					$honor += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'honor', $honor );
					break;
				case RewardConfType::GRAIN :
					$grain += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'grain', $grain );
					$grainModify = true;
					break;
				case RewardConfType::COIN :
					$coin += $rewardVal[ 'val' ];
					self::addKeyValue($res, 'coin', $coin );
					$coinModify = true;
					break;
				case RewardConfType::ZG :
					$zg += $rewardVal['val'];
					self::addKeyValue($res, 'zg', $zg );
					$zgModify = true;
					break;
				case RewardConfType::HELL_TOWER:
				    $user->addTowerNum($rewardVal['val']);
				    self::addKeyValue($res, 'hell_tower', $rewardVal['val']);
				    $userModify = true;
				    break;
				default:
					Logger::fatal( ' nothing for this sick babe? ' );
					break;
			}
			Logger::debug('rewardval in rewardutil is: %s', $rewardVal );
		}
	
		if ( !empty( $arrItem ) )
		{
			self::addKeyValueItem($res, 'item', $arrItem );
			
			$bag = BagManager::getInstance()->getBag( $uid );
			if ( !$bag->addItemsByTemplateID( $arrItem, $tmpBag ) )
			{
				throw new FakeException( 'bag is full: %s', $arrItem);
			}
			Logger::trace('reward. add item:%s', $arrItem);
		}
		
		if( !empty( $arrHero ) )
		{
			self::addKeyValueHero( $res , 'hero', $arrHero);
			
			if($user->getHeroManager()->hasTooManyHeroes())
			{
				throw new FakeException('too many hero. %s', $arrHero);
			}
			Logger::trace('reward. add hero:%s', $arrHero);
			$user->getHeroManager()->addNewHeroes( $arrHero );
		}
		
		if ( !empty( $arrTreasureFrag ) )
		{
			self::addKeyValueItem($res, 'treasureFrag', $arrItem );
			$fragInst = FragseizeObj::getInstance( $uid );
			$fragInst->addFrags( $arrTreasureFrag );
			if( $updateNow )
			{
				$fragInst->updateFrags();
			}
			Logger::trace('reward. add Tfrag:%s', $arrTreasureFrag);
		}

		if( $grain != 0 || $zg != 0 )
		{
			$guildMemObj = GuildMemberObj::getInstance($uid);
			$guildMemObj->addGrainNum( $grain );
			$guildMemObj->addZgNum( $zg );
			if( $updateNow )
			{
				$guildMemObj->update();
			}
		}
		
		if( $coin != 0 )
		{
			$passObj = EnPass::getPassObj($uid);
			$passObj->addCoin( $coin );
			if( $updateNow )
			{
				$passObj->update();
			}
		}
		
		if( $crossHonor != 0 )
		{
			$serverId= Util::getServerIdOfConnection();
			$pid =  WorldCompeteUtil::getPid($uid, true);
			$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
			$worldCompeteInnerUserObj->addCrossHonor($crossHonor);
			
			if( $updateNow )
			{
				$worldCompeteInnerUserObj->update();
			}
		}
		
		if( $copoint != 0 )
		{
			$serverId= Util::getServerIdOfConnection();
			$pid =  EnUser::getUserObj($uid)->getPid();
			$crossCountryUser = CountryWarCrossUser::getInstance($serverId, $pid);
			$crossCountryUser->addCopoint($copoint);
			if( $updateNow )
			{
				$crossCountryUser->update();
			}
		}
		
		if ( $honor != 0 )
		{
			EnCompete::addHonor($uid, $honor);
		}
		if ( $contri != 0 )
		{
			EnGuild::addMemberPoint($uid, $contri);
		}
		if( $guildExp != 0 )
		{
			EnGuild::addGuildExp($uid, $guildExp);
		}
		if ( $hellPoint != 0 ) 
		{
			EnWorldPass::addHellPoint($uid, $hellPoint);
		}
		
		if ( $bag != null )
		{
			$bagModify = true;
		}
		
		$ret = array(
				UpdateKeys::BAG => $bagModify,
				UpdateKeys::USER => $userModify,
				UpdateKeys::TFRAG => $tFragModify,
				UpdateKeys::GRAIN => $grainModify,
				UpdateKeys::COIN => $coinModify,
				UpdateKeys::ZG => $zgModify,
				UpdateKeys::CROSSHONOR => $crossHonorModify,
				UpdateKeys::COPOINT => $copointModify,
				UpdateKeys::REWARDINFO => $res,
		);

		Logger::trace( 'finish reward by rewardUtil, reward: %s', $res );
		return $ret;
	}
	
	/**
	 * 
	 * @param int $uid
	 * @param int $updateKeys self::reward函数的返回值
	 */
	public static function updateReward( $uid, $updateKeys )
	{
		foreach ( $updateKeys as $key => $need )
		{
			if( $need )
			{
				switch ( $key )
				{
					case UpdateKeys::BAG: 
						BagManager::getInstance()->getBag($uid)->update();
						break;
					case UpdateKeys::USER:
						EnUser::getUserObj($uid)->update();
						break;
					case UpdateKeys::TFRAG:
						FragseizeObj::getInstance($uid)->updateFrags();
						break;
					case UpdateKeys::GRAIN:
					case UpdateKeys::ZG:
						GuildMemberObj::getInstance($uid)->update();
						break;
					case UpdateKeys::COIN:
						$passObj = EnPass::getPassObj($uid)->update();
						break;
					case UpdateKeys::CROSSHONOR:
						$serverId= Util::getServerIdOfConnection();
						$pid =  WorldCompeteUtil::getPid($uid, true);
						WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid)->update();
						break;
					case UpdateKeys::COPOINT:
						$serverId= Util::getServerIdOfConnection();
						$pid =  EnUser::getUserObj($uid)->getPid();
						CountryWarCrossUser::getInstance($serverId, $pid)->update();
						break;
				}
			}
		}
	}
	
	private static function addKeyValue(&$arr, $key, $value)
	{
		if (!isset($arr[$key]))
		{
			$arr[$key] = $value;
		}
		else
		{
			$arr[$key] += $value;
		}
	}

	private static function addKeyValueItem( &$arr, $key, $value )
	{
		foreach ( $value as $id => $num )
		{
			if ( !isset( $arr[ $key ][ $id ] ) )
			{
				$arr[ $key ][ $id ] = $num;
			}
			else 
			{
				$arr[ $key ][ $id ] += $num;
			}
		}
	}
	
	private static function addKeyValueHero( &$arr, $key, $value )
	{
		foreach ( $value as $id => $num )
		{
			if ( !isset( $arr[ $key ][ $id ] ) )
			{
				$arr[ $key ][ $id ] = $num;
			}
			else
			{
				$arr[ $key ][ $id ] += $num;
			}
		}
	}
	
	/**
	 * 直接发奖的另一个方法（称之为：3元组直接发）
	 * @param int $uid
	 * @param array $rewardArr
	 * array
	 * (
	 * 		array(1,0,200)//银币200
	 * 		array(7,60007,20)//物品60007 20个
	 * 		.
	 * 		.
	 * 		支持宝物碎片
	 * 注意！： 提醒策划陪奖励不要再用6和10两个类型
	 * 注意! ：该方法没有update user 和bag
	 * ！！！！！！！注意，该函数支持加碎片且是直接update的，而user和bag没有
	 * )
	 * @param int $source 金币的类型
	 * @param int $tmpBag 是否要放到临时背包
	 * @return array 参见：self::reward
	 * @throws FakeException
	 */
	public static function reward3DArr($uid,  $rewardArr, $source, $tmpBag = false, $updateNow = true )
	{
		$rewardForUtil = self::getTypeValueFormatFrom3D($rewardArr);
		Logger::debug('rewardinfo in reward3DArr: %s',$rewardForUtil);
		
		return RewardUtil::reward($uid, $rewardForUtil, $source,$tmpBag, $updateNow );
	}
	
	/**
	 * 3d格式转化为tp格式，是给本模块自己用的，一般不要调用
	 * @param unknown $rewardArr
	 * @throws FakeException
	 * @return multitype:multitype:unknown multitype:multitype:unknown    multitype:unknown
	 */
	public static function getTypeValueFormatFrom3D($rewardArr)
	{
		Logger::debug('rewardinfo in getTypeValueFormatFrom3D before: %s', $rewardArr);
		$rewardForUtil = array();
		foreach ( $rewardArr as $val )
		{
			switch ( $val[0] )
			{
				case RewardConfType::EXECUTION:
				case RewardConfType::GOLD:
				case RewardConfType::JEWEL:
				case RewardConfType::PRESTIGE:
				case RewardConfType::SILVER:
				case RewardConfType::SOUL:
				case RewardConfType::SOUL:
				case RewardConfType::STAMINA:
				case RewardConfType::HORNOR:
				case RewardConfType::GUILD_CONTRI:
				case RewardConfType::GUILD_EXP:
				case RewardConfType::SOUL_MUL_LEVEL:
				case RewardConfType::SILVER_MUL_LEVEL:
				case RewardConfType::EXP_MUL_LEVEL:
				case RewardConfType::GRAIN:
				case RewardConfType::COIN:
				case RewardConfType::ZG:
				case RewardConfType::TG:
				case RewardConfType::WM:
				case RewardConfType::FAME:
				case RewardConfType::HELL_POINT:
				case RewardConfType::CROSS_HONOR:
				case RewardConfType::COPOINT:
				case RewardConfType::JH:
				case RewardConfType::TALLY_POINT:
				case RewardConfType::BOOK:
				case RewardConfType::HELL_TOWER:
				case RewardConfType::EXP:
					$rewardForUtil[] = array(
					'type' => $val[0],
					'val' => $val[2],
					);
					break;
				case RewardConfType::HERO_MULTI:
				case RewardConfType::ITEM_MULTI:
				case RewardConfType::TREASURE_FRAG_MULTI:
					$rewardForUtil[] = array(
					'type' => $val[0],
					'val' => array(
					array( $val[1], $val[2] ),
					),
					);
					break;
				default:
					throw new FakeException( 'invalid type %d',$val[0] );
			}
		}
		Logger::debug('rewardinfo in getTypeValueFormatFrom3D after: %s', $rewardForUtil);
		return $rewardForUtil;
	}
	

	
	/**
	 * 以type、value的形式可以直接发到奖励中心的方法, 称之为 type，value奖励中心
	 * @param int $uid
	 * @param array $rewardArrGroup  详见本类reward方法，再外面又包了一层，支持多条type，value一起作为奖励中心的一条发
	 * @param int $rewardCenterType
	 * @param array $extraArr
	 * @param string $db 如果要给其他服发奖励，需要传入对应服的db名字
	 */
	public static function rewardUtil2Center($uid, $rewardArrGroup, $rewardCenterType, $extraArr = array(), $db = '' )
	{
		Logger::debug('rewardinfo in rewardUtil2Center before: %s', $rewardArrGroup);
		$res = array();
		
		$level = -1;
		
		foreach ( $rewardArrGroup as $key => $rewardArr )
		{
			
			foreach ( $rewardArr as $rewardVal )
			{
				$type = $rewardVal[ 'type' ] ;
				switch ( $type )
				{
					case RewardConfType::SILVER:
					case RewardConfType::SOUL:
					case RewardConfType::JEWEL:
					case RewardConfType::PRESTIGE:
					case RewardConfType::GOLD:
					case RewardConfType::EXECUTION:
					case RewardConfType::STAMINA :
					case RewardConfType::HORNOR:
					case RewardConfType::GUILD_CONTRI :
					case RewardConfType::GUILD_EXP :
					case RewardConfType::GRAIN:
					case RewardConfType::COIN:
					case RewardConfType::ZG:
					case RewardConfType::TG:
					case RewardConfType::WM:
					case RewardConfType::FAME:
					case RewardConfType::HELL_POINT:
					case RewardConfType::CROSS_HONOR:
					case RewardConfType::COPOINT:
					case RewardConfType::JH:
					case RewardConfType::TALLY_POINT:
					case RewardConfType::BOOK:
					case RewardConfType::HELL_TOWER:
					case RewardConfType::EXP:
						$val = $rewardVal['val'];
						self::addKeyValue($res, RewardConfType::$rewardUtil2Center[$type], $val );
						break;
					case RewardConfType::SILVER_MUL_LEVEL:
					case RewardConfType::SOUL_MUL_LEVEL:
					case RewardConfType::EXP_MUL_LEVEL:
						
						if( $level < 0 )
						{
							$level = EnUser::getUserLevel($uid, $db);
						}
						$val = $rewardVal['val'] * $level;
						self::addKeyValue($res, RewardConfType::$rewardUtil2Center[$type], $val );
						break;
					case RewardConfType::ITEM_MULTI:
					case RewardConfType::HERO_MULTI:
					case RewardConfType::TREASURE_FRAG_MULTI:
						$arrThings = array();
						foreach ( $rewardVal[ 'val' ] as $rewardThing )
						{
							if ( isset( $arrThings[ $rewardThing[ 0 ] ] ) )
							{
								$arrThings[ $rewardThing[ 0 ] ] += intval( $rewardThing[ 1 ] );
							}
							else
							{
								$arrThings[ $rewardThing[ 0 ] ] = intval( $rewardThing[ 1 ] );
							}
						}
						self::addKeyValueItem($res, RewardConfType::$rewardUtil2Center[$type], $arrThings );
						break;
			
					default:
						Logger::fatal( ' nothing for this sick babe? ' );
						break;
				}
					
				Logger::debug('rewardval in rewardutil is: %s', $rewardVal );
					
			}
		}
		if ( !empty( $extraArr ) )
		{
			$res[RewardDef::EXT_DATA] = $extraArr;
		}
		Logger::debug('rewardinfo in rewardUtil2Center after: %s', $res);
		EnReward::sendReward($uid, $rewardCenterType, $res, $db);
	}
	
	/**
	 * 3d格式直接发到奖励中心
	 * @param int $uid
	 * @param array $rewardArrGroup
	 * array(
	 *		array
	 * 		(
	 * 				array(1,0,200)//银币200
	 * 				array(7,60007,20)//物品60007 20个
	 * 				.
	 * 				.
	 * 				支持宝物碎片
	 * 		注意！： 提醒策划陪奖励不要再用6和10两个类型
	 * 		)
	 * )
	 * 
	 * @param int $rewardCenterType 奖励中心的类型
	 * @param array $extraArr
	 * @param string $db 如果要给其他服发奖励，需要传入对应服的db名字
	 */
	public static function reward3DtoCenter($uid, $rewardArrGroup, $rewardCenterType, $extraArr = array(), $db = '' )
	{
		Logger::debug('rewardinfo in reward3DtoCenter before: %s', $rewardArrGroup);
		$res = array();
		foreach ( $rewardArrGroup as $key => $rewardArr )
		{
			$res[$key] = self::getTypeValueFormatFrom3D($rewardArr);
		}
		
		Logger::debug('rewardinfo in reward3DtoCenter after: %s', $res);
		self::rewardUtil2Center($uid, $res, $rewardCenterType, $extraArr, $db);
	}
	
	/**
	 * 将策划配置的3元组格式奖励配置
	 * @param array $arr3D
	 */
	public static function format3DtoCenter($arr3D, $uid = 0)
	{
		$arrReward = array();
		$level = -1;
		if( $uid > 0 )
		{
			$level = EnUser::getUserObj($uid)->getLevel();
		}
		foreach( $arr3D as $v3d )
		{
			$type = $v3d[0];
			switch ( $type )
			{
				case RewardConfType::SILVER:
				case RewardConfType::SOUL:
				case RewardConfType::JEWEL:
				case RewardConfType::PRESTIGE:
				case RewardConfType::GOLD:
				case RewardConfType::EXECUTION:
				case RewardConfType::STAMINA :
				case RewardConfType::HORNOR:
				case RewardConfType::GUILD_CONTRI :
				case RewardConfType::GUILD_EXP :
				case RewardConfType::GRAIN :
				case RewardConfType::COIN :
				case RewardConfType::ZG :
				case RewardConfType::TG :
				case RewardConfType::WM :
				case RewardConfType::FAME:
				case RewardConfType::HELL_POINT:
				case RewardConfType::CROSS_HONOR:
				case RewardConfType::COPOINT:
				case RewardConfType::JH:
				case RewardConfType::TALLY_POINT:
				case RewardConfType::BOOK:
				case RewardConfType::HELL_TOWER:
				case RewardConfType::EXP:
					$val = intval($v3d[2]);
					self::addKeyValue($arrReward, RewardConfType::$rewardUtil2Center[$type], $val );
					break;
				case RewardConfType::SILVER_MUL_LEVEL:
				case RewardConfType::SOUL_MUL_LEVEL:
				case RewardConfType::EXP_MUL_LEVEL:
					if( $level < 0 )
					{
						throw new InterException('need level for reward');
					}
					$val = intval($v3d[2])* $level;
					self::addKeyValue($arrReward, RewardConfType::$rewardUtil2Center[$type], $val );
					break;
				case RewardConfType::ITEM_MULTI:
				case RewardConfType::HERO_MULTI:
				case RewardConfType::TREASURE_FRAG_MULTI:
					$val = array( intval($v3d[1]) => intval($v3d[2]) );
					self::addKeyValueItem($arrReward, RewardConfType::$rewardUtil2Center[$type], $val );
					break;
						
				default:
					Logger::fatal( ' nothing for this sick babe? ' );
					break;
			}
		}
		
		Logger::debug('format3DtoCenter. before:%s, after:%s', $arr3D, $arrReward);
		return $arrReward;
	}

    /**
     * 删除物品
     * 目前支持的类型有：1,2,3,4,5,7,11,12,17
     * 荣誉是直接更新的
     * 
     * @param $uid
     * @param array $material 三元组物品
     *  [
     *      0 => array(1,0,200), 1 => array(7,60007,20)...
     *  ]
     * @param $statistics string 金币的统计类型,如果不消耗金币,可以不传
     * @param int $amount 三元组的数量,默认1
     * @param array $arrItemId 要删掉的itemId数组,默认空,就直接按照模板Id删除
     * @param int $update 是否update,默认更新user和bag,
     * @throws FakeException
     */
    public static function delMaterial($uid, $material, $statistics=null, $amount=1, $arrItemId=array(), $update=true)
    {
    	if (empty($material)) 
    	{
    		return ;
    	}
    	
    	$bagModify = false;
    	$userModify = false;
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);

        $honor = 0;
        $arrMaterial = ItemManager::getInstance()->getItems($arrItemId);
        foreach($material as $oneM)
        {
            list($type, $mid, $num) = $oneM;
            $toDelNum = $num * $amount;
            switch($type)
            {
                case RewardConfType::SILVER:
                    if($userObj->subSilver($toDelNum) == false)
                    {
                        throw new FakeException("subSilver %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::SOUL:
                    if($userObj->subSoul($toDelNum) == false)
                    {
                        throw new FakeException("subSoul %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::GOLD:
                    if(empty($statistics))
                    {
                    	throw new FakeException("subGold must have Statistics def");
                    }
                    $goldType = $statistics;
                    if( $statistics == -1 )
                    {
                    	Logger::warning('subGold no Statistics');
                    	$goldType = 0;
                    }
                    if($userObj->subGold($toDelNum, $goldType) == false)
                    {
                    	throw new FakeException("subGold %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::EXECUTION:
                    if($userObj->subExecution($toDelNum) == false)
                    {
                    	throw new FakeException("subExecution %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::STAMINA:
                    if($userObj->subStamina($toDelNum) == false)
                    {
                    	throw new FakeException("subStamina %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::JEWEL:
                    if($userObj->subJewel($toDelNum) == false)
                    {
                        throw new FakeException("subJewel %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::PRESTIGE:
                    if($userObj->subPrestige($toDelNum) == false)
                    {
                        throw new FakeException("subPrestige %d failed", $toDelNum);
                    }
                    $userModify = true;
                    break;
                case RewardConfType::ITEM_MULTI:
                    if(empty($arrMaterial))
                    {
                        if($bag->deleteItembyTemplateID($mid, $toDelNum) == false)
                        {
                            throw new FakeException("delete item failed itemId:%d num:%d", $mid, $toDelNum);
                        }
                    }
                    else
                    {
                        foreach($arrMaterial as $materialId => $materialObj)
                        {
                        	if (empty($materialObj)) 
                        	{
                        		continue;
                        	}
                            $materialTplId = $materialObj->getItemTemplateID();
                            if($materialTplId == $mid)
                            {
                                $materialNum = $materialObj->getItemNum();
                                $delNum = min($toDelNum, $materialNum);
                                $bag->decreaseItem($materialId, $delNum);
                                $toDelNum -= $delNum;
                            }
                        }
                        if($toDelNum > 0)
                        {
                            throw new FakeException("Material to del do not match");
                        }
                    }
                    $bagModify = true;
                    break;
                case RewardConfType::HORNOR:
                	$honor += $toDelNum;
                	break;
                default:
                    throw new FakeException("invalid type:%d", $type);
            }
        }
        
        if (!empty($honor) && EnCompete::addHonor($uid, -$honor) == 'failed') 
        {
        	throw new FakeException('subHonor %d failed', $honor);
        }

        if ($update) 
        {
        	$bag->update();
        	$userObj->update();
        }
        
        return array(
        		UpdateKeys::BAG => $bagModify,
        		UpdateKeys::USER => $userModify,
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */