<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PetManager.class.php 248248 2016-06-27 03:45:32Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/PetManager.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-06-27 03:45:32 +0000 (Mon, 27 Jun 2016) $
 * @version $Revision: 248248 $
 * @brief 
 *  
 **/

/**
 * Class PetManager
 * $allPet
 * [
 * 	petId => array
 * 	[
 * 		petId,
 * 		uid,
 * 		level,
 * 		va...
 * 	]
 * ]
 */
class PetManager
{
	private $allPet = array();
	private $allPetBack = array();
	private $uid = 0;
	private static $s_instance = NULL;
	
	/**
	 * 获取本类唯一实例
	 * @return PetManager
	 */
	public static function getInstance( $uid = 0 )
	{
		if ( !isset( self::$s_instance[$uid] ))
		{
			self::$s_instance[$uid] = new self( $uid );
		}
		return self::$s_instance[$uid];
	}
	
	/**
	 * 测试的时候会用到
	 */
	public static function release()
	{
		if (self::$s_instance != null)
		{
			self::$s_instance = null;
		}
	}
	
	
	public function __construct( $uid )
	{
		$guid = RPCContext::getInstance()->getUid();
		if ( $uid == 0 || $uid == $guid )
		{
			$this->uid = $guid;
			$this->allPet = RPCContext::getInstance()->getSession( PetDef::PET_SESSION );
			if ( empty( $this->allPet ) )
			{
				$this->allPet = PetDAO::selectAllPet( $this->uid );
				if ( !empty( $this->allPet ) )
				{
					$this->allPet = Util::arrayIndex( $this->allPet , PetDef::PETID);
				}
				RPCContext::getInstance()->setSession( PetDef::PET_SESSION , $this->allPet);
			}
		}
		else 
		{
			$this->uid = $uid;
			$this->allPet = PetDAO::selectAllPet( $this->uid );
			if ( !empty( $this->allPet ) )
			{
				$this->allPet = Util::arrayIndex( $this->allPet , PetDef::PETID);
			}
		}
		
		$this->allPetBack = $this->allPet;//差点写错，必须在前面
		
		//专为造假
		if ( empty( $this->allPet ) )
		{
			$allPetCount = PetDAO::getAllPetCount($uid);
			if( $allPetCount <= 0 )
			{
				$this->addNewPet( 1,false );//TODO那些个假数据需要策划自己保证（初始技能点数，初始栏位数，升级需要的经验）
				$bag = BagManager::getInstance()->getBag($this->uid);
				$bag->addItemByTemplateID( 50305 , 1, true);//需要策划给出数值，以保证新手引导TODO
				$this->update();
				$bag->update();
				RPCContext::getInstance()->setSession( 'pet.fake' , 1);
			}
		}
		//专为造假
		
		Logger::debug('allpet are in construct: %s', $this->allPet);
		//用以实现驯养经验的正确性和及时更新问题
		
		$this->fixTalentSkill();//多个天赋添加
		$this->adaptPetExp();
		$this->checkPetSkillNormal();
		$this->fixEvolveAddLevel();
		Logger::debug('down in construct');
	}
	
	public function fixTalentSkill()
	{
		//多个天赋添加	=====================
		foreach ( $this->allPet as $index => $petInfo )
		{
			$pettmpl = $petInfo[PetDef::PETTMPL];
			$petconf = btstore_get()->PET[$pettmpl]->toArray();
			$skillTalent = $petconf['talentSkillsWeightArr'];
			Logger::debug('mid fix talent skill is: %s', $skillTalent);
			$realSkillTalent = array();
			if ( empty( $skillTalent ) )
			{
				//兼容以前的
				$realSkillTalent = array( 'id' => 0, 'level' => 0 );
			}
			else
			{
				foreach ( $skillTalent as $randIndex => $randSkillInfo )
				{
					$realSkillTalent[] = array(
							'id' => $randSkillInfo['id'],
							'level' => $randSkillInfo['lv'],
					);
				}
			}
			$realSkillTalent['petuseless'] = 0;
			unset($realSkillTalent['petuseless']);
			if ( $petInfo[PetDef::VAPET]['skillTalent'] != $realSkillTalent )
			{
				Logger::warning('petid: %d, skill before: %s ,skill after: %s',$petInfo[PetDef::PET_ID], $petInfo[PetDef::VAPET]['skillTalent'],$realSkillTalent);
				$this->allPet[$index][PetDef::VAPET]['skillTalent'] = $realSkillTalent;
			}
			
		}
		
		//多个天赋添加	=====================
	}
	
	public function adaptPetExp()
	{	
		//在这里拉取了有哪些宠物现在是在阵上
		$keeperInst = KeeperObj::getInstance($this->uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		$setPet = $vaKeeper['setpet'];
		$petCost = btstore_get()->PET_COST[1];
		$needTime = PetDef::PET_TRAIN_TIME;
		Logger::debug('now adaptPetExp: %s, %s', $vaKeeper, $petCost);
		foreach($setPet as $pos => $posInfo)
		{
			$petidInPos = $posInfo[ 'petid'  ];
			//对每一开启的阵容位置如果有宠物并且在阵上的时间已经>结算需要的时间那么就把宠物的经验更新一下
			if (!empty($petidInPos) )
			{
				$trainTime = $this->allPet[$petidInPos][PetDef::TRAINTIME];
				if ( $trainTime+$needTime<= Util::getTime() )
				{
					//驯养了多久
					$timeLast = Util::getTime()-$trainTime;
					//该加多少经验
					$expTimes = intval( $timeLast/$needTime );
					$addExp = $expTimes*$petCost['squandExp'];
					//设置新的驯养时间并增加经验
					$newTime = Util::getTime()- $timeLast%$needTime;
					$this->setTrainTime($petidInPos,$newTime);
					$this->addExp( $petidInPos, $addExp );
					Logger::debug('now2 adaptPetExp: %d, %d', $addExp,$timeLast);
				}
			}
		}
	}
	
	public function setTrainTime( $petid, $time )
	{
		$this->allPet[$petid][PetDef::TRAINTIME] = $time;
	}
	public function addExp( $petid,$exp )
	{
        $petInfo = $this->allPet[$petid];
        $levelBeforeFeed = $petInfo[PetDef::LEVEL];
        $petTmpl = $petInfo[PetDef::PETTMPL];
        $petConf = btstore_get()->PET[$petTmpl];
		//如果能够成功，加完之后的总经验
        $petExpTotal = $petInfo[ PetDef::EXP ] + $exp;

        $expTblId = $petConf[ 'expTbl'  ];
        $expTbl = btstore_get()->EXP_TBL[ $expTblId ];

        //此时两个值都是初始的（也就是只是一个暂存，不要被名字误导）
        $expAfterFeed = $petExpTotal;
        $lvAfterFeed = $petInfo[ 'level' ];
        
        $level = EnUser::getUserObj( $this->uid )->getLevel();
		//真正的循环判定宠物到底可以到多少经验和级别 
        foreach ( $expTbl as $lv => $needExp )
        {
            if ( $petExpTotal >= $needExp )
            {
                if( $lv >= $level )
                {
                    $lvAfterFeed = $level;
                    $expAfterFeed = $expTbl[ $level ];
                    break;//走到这里之后，经验表后边的等级都>宠物可增长到的等级了
                }
                else
                {
                    $lvAfterFeed = $lv;
                }
            }
            else
            {
            	//这是级别还没达到上限，但是经验不够了
                break;
            }
        }

		 //判定： 防止修改之后级别变低
        if ( $expAfterFeed < $petInfo[ 'exp' ] || $lvAfterFeed < $petInfo[ 'level' ] )
        {
            Logger::fatal(
                    'feedpet, data serious fault, petid: %d , addExp: %d, exp and lv before: %d, %d'
                    ,$petid , $exp ,$petInfo['exp'],$petInfo[ 'level' ] );
        }

        //修改宠物经验和级别
        $this->setExpAndLv( $petid, $expAfterFeed, $lvAfterFeed);

        //升级会增加技能点
        $addPoints = 0;
        for ( $levelTmp = $levelBeforeFeed + 1; $levelTmp <= $lvAfterFeed; $levelTmp++ )
        {
            if ( $levelTmp%$petConf['skillPointLvInterval'] == 0 )
            {
                $addPoints += $petConf['skillPointInc'];
            }
        }
		$this->addSkillPoint($petid, $addPoints);
		
		return $addPoints;
	}
	public function getAllPet()
	{
		if ( empty( $this->allPet ) )
		{
			return array();
		}
		
		return $this->allPet;
	}
	
	public function getOnePetInfo( $petid )
	{
		if ( !isset( $this->allPet[ $petid ] ) )
		{
			return array();
		}
		else 
		{
			return $this->allPet[ $petid ];
		}
	}
	
	public function addNewPet( $petTmpl, $realAdd = true )
	{
		$petid = IdGenerator::nextId( PetDef::PET_ID );
		$petconf = btstore_get()->PET[$petTmpl]->toArray();
		//随一个天赋技能和一个特殊技能，技能id可能为空，就是空技能
		$randSkillTalent = $petconf['talentSkillsWeightArr'];
		$randSkillProduct = $petconf['productSkillsWeightArr'];
		
		
		//多个天赋添加	=====================	
		$initSkillTalent = array();
		if ( empty( $randSkillTalent ) )
		{
			//兼容以前的
			$initSkillTalent = array( 'id' => 0, 'level' => 0 );
		} 
		else
		{
			foreach ( $randSkillTalent as $randIndex => $randSkillInfo )
			{
				$initSkillTalent[] = array( 
						'id' => $randSkillInfo['id'],
						'level' => $randSkillInfo['lv'],
				);
			}
		} 
		//多个天赋添加 ========================
		
		
		//多个天赋删除$retSkillTalent = Util::noBackSample($randSkillTalent, 1);
		$retSkillProduct = Util::noBackSample($randSkillProduct, 1);
		
		//多个天赋删除$initSkillTalent = $randSkillTalent[$retSkillTalent[0]];
		$initSkillProduct = $randSkillProduct[$retSkillProduct[0]];
		$initSkillSlot = $petconf['initSkillSlot']; 
		//初始的普通技能栏位信息
		$initSkillPos = array();
		for( $i =0;$i < $initSkillSlot; $i++  )
		{
			$initSkillPos[] = array(
					'id' => 0,'level' =>0,'status' => PetDef::SKILL_UNLOCK,
			);
		}
		
		$petIniVal = array(
				PetDef::PETID => $petid,
				'uid' => $this->uid,
				PetDef::PETTMPL => $petTmpl,
				PetDef::EXP => 0,
				PetDef::LEVEL => 1,
				PetDef::SKILLPOINT => $petconf['initSkillPoint'],
				PetDef::SWALLOW => 0,
				PetDef::TRAINTIME => 0,
				PetDef::DELETE_TIME => PetDef::OK,
				PetDef::VAPET => array(
						'skillNormal' => $initSkillPos,
						'skillTalent' => $initSkillTalent,
						/* 多个天赋删除 array(
							array(
									'id' => $initSkillTalent['id'],
									'level' => $initSkillTalent['lv']),
						), */
						'skillProduct' => array(
							array( 
									'id' => $initSkillProduct['id'],
									'level' => $initSkillProduct['lv'] ),
						),
		),
		);	 

		$this->allPet[ $petid ] = $petIniVal;
		if( $realAdd )
		{
			//这里成就系统会为了初始化数据而拉取宠物，宠物的构造函数又会加新的宠物，加新的宠物会update成就系统
			//安全起见，初始化时添加的宠物还是不要通知成就系统了
			EnAchieve::updatePetTypes($this->uid, $petTmpl);
		}
		
		return $petIniVal;
	}
	
	public function setExpAndLv( $petid , $exp , $lv )
	{
		$this->allPet[ $petid ][ 'exp' ] = $exp;
		$this->allPet[ $petid ][ 'level' ] = $lv;
	}
	
	public function subSkillPoint( $petid, $num = 1 )
	{
		if( $num < 0 )
		{
			throw new InterException( 'sub minus: %d', $num );
		}
		$this->allPet[ $petid ][PetDef::SKILLPOINT] -= $num;
		if ( $this->allPet[ $petid ][PetDef::SKILLPOINT] < 0 )
		{
			throw new InterException( 'result: %s in minus < 0', $this->allPet[ $petid ][PetDef::SKILLPOINT] );
		}
	}
	
	public function openSkillSlot( $petid )
	{
		$this->allPet[ $petid ][ PetDef::VAPET]['skillNormal'][] 
		= array( 'id' => 0, 'level' => 0 ,'status' => 0);
	}
	
	public function skillLvUp( $petid, $pos )
	{
		$this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos]['level'] ++;
	}
	
	public function deletePet( $petid )
	{
		unset($this->allPet[$petid]);//[PetDef::DELETE_TIME] = Util::getTime();
	}
	
	public function addSkillPoint($petid,$point)
	{
		$this->allPet[$petid][PetDef::SKILLPOINT] += $point;
	}
	
	public function addSwallowNum( $petid, $swallowNum = 1 )
	{
		$this->allPet[$petid][PetDef::SWALLOW] += $swallowNum;
	}
	
	public function addNewNormalSkill( $petid, $pos, $skillId )
	{
		Logger::debug('add new normal skill: %d, %d, %d', $petid, $pos, $skillId);
		if( !empty( $this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos]['id'] ) )
		{
			throw new FakeException( 'petid: %d in pos: %d has skillid: %d', $petid, $pos, $skillId );
		}
		$this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos] 
		= array('id' =>$skillId, 'level' => 1, 'status' => 0 );
	}
	
	public function resetNormalSkill($petid)
	{
		$petTmpl = $this->allPet[$petid][PetDef::PETTMPL];
		$petConf = btstore_get()->PET[$petTmpl]->toArray();
		$initSkillSlot = $petConf['initSkillSlot'];
		
		foreach ( $this->allPet[$petid][PetDef::VAPET]['skillNormal'] as $pos => $skillInfo )
		{
			if ( ( $skillInfo['status'] == 0) )
			{
				unset( $this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos]);
			}
		}
		
		$this->allPet[$petid][PetDef::VAPET]['skillNormal']
		= array_merge( $this->allPet[$petid][PetDef::VAPET]['skillNormal'] );
		
		$needNum = $initSkillSlot - count( $this->allPet[$petid][PetDef::VAPET]['skillNormal'] );
		if ( $needNum > 0 )
		{
			for ( $m = 0; $m<$needNum; $m++ )
			{
				$this->allPet[$petid][PetDef::VAPET]['skillNormal'][] 
				= array('id' => 0, 'level' => 0, 'status' => 0 );
			}
		}
		
		return $this->allPet[$petid][PetDef::VAPET]['skillNormal'];
	}
	
	public function lockSkillSlot($petid, $pos)
	{
		$this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos]['status'] = 1;
	}
	
	public function unlockSkillSlot( $petid, $pos )
	{
		$this->allPet[$petid][PetDef::VAPET]['skillNormal'][$pos]['status'] = 0;
	}

	public function getEvolveLevel($petId)
	{
		if(empty($this->allPet[$petId][PetDef::VAPET]['evolveLevel']))
		{
			return 0;
		}
		return $this->allPet[$petId][PetDef::VAPET]['evolveLevel'];
	}

	public function addEvolveLevel($petId)
	{
		if(empty($this->allPet[$petId][PetDef::VAPET]['evolveLevel']))
		{
			$this->allPet[$petId][PetDef::VAPET]['evolveLevel'] = 0;
		}
		++$this->allPet[$petId][PetDef::VAPET]['evolveLevel'];


		/*进阶解锁技能等级提升 宠物进阶增加的技能等级, 配置不能改, 跟策划确认过了
		$petTplId = $this->allPet[$petId][PetDef::PETTMPL];
		$evolveSkillConf = btstore_get()->PET[$petTplId]['evolveSkill'];
		if(empty($evolveSkillConf[$this->allPet[$petId][PetDef::VAPET]['evolveLevel']]))
		{
			return;
		}

		$skillNormal = $this->allPet[$petId][PetDef::VAPET]['skillNormal'];
		foreach($skillNormal as $index => $eachSkill)
		{
			$this->allPet[$petId][PetDef::VAPET]['skillNormal'][$index]['level'] +=
				$evolveSkillConf[$this->allPet[$petId][PetDef::VAPET]['evolveLevel']];
			
			if ($eachSkill['id'] == 0)
			{
			    continue;
			}
			if (isset($this->allPet[$petId][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel']))
			{
			    $this->allPet[$petId][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel'] +=
			        $evolveSkillConf[$this->allPet[$petId][PetDef::VAPET]['evolveLevel']];
			}
			else{
			    $this->allPet[$petId][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel'] =
			        $evolveSkillConf[$this->allPet[$petId][PetDef::VAPET]['evolveLevel']];
			}
		}*/
	}

	public function setToConfirm($petId, $toConfirm)
	{
		if(empty($toConfirm))
		{
			return;
		}
		$this->allPet[$petId][PetDef::VAPET]['toConfirm'] = $toConfirm;
	}

	public function confirm($petId)
	{
        foreach($this->allPet[$petId][PetDef::VAPET]['toConfirm'] as $attrId => $attrValue)
        {
            $this->allPet[$petId][PetDef::VAPET]['confirmed'][$attrId] = $attrValue;
        }

		$this->unSetToConfirm($petId);
	}

	public function unSetToConfirm($petId)
	{
		unset($this->allPet[$petId][PetDef::VAPET]['toConfirm']);
	}

    /**
     * 获取某个属性的当前洗练价值
     */
    public function getCurConfirmAttrValueOfAttrId($petId, $attrId)
    {
        if(!isset($this->allPet[$petId][PetDef::VAPET]['confirmed'][$attrId]))
        {
            return 0;
        }
        return $this->allPet[$petId][PetDef::VAPET]['confirmed'][$attrId];
    }

	public function exchange($petId1, $petId2)
	{
	    /*先把所有技能加的进阶等级减去
	    $skillNormal = $this->allPet[$petId1][PetDef::VAPET]['skillNormal'];
	    foreach($skillNormal as $index => $eachSkill)
		{
			if (isset($this->allPet[$petId1][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel']))
			{
			    $this->allPet[$petId1][PetDef::VAPET]['skillNormal'][$index]['level'] -= 
			        $this->allPet[$petId1][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel'];
			    if ($this->allPet[$petId1][PetDef::VAPET]['skillNormal'][$index]['level'] < 0)
			    {
			        Logger::fatal("waring!!exchange err, pet[%d], skill[%d], change level err!", $petId1, $eachSkill['id']);
			        $this->allPet[$petId1][PetDef::VAPET]['skillNormal'][$index]['level'] = 0;
			    }
			}
		}
		unset($this->allPet[$petId1][PetDef::VAPET]['skillNormalEvolve']);
		
		$skillNormal = $this->allPet[$petId2][PetDef::VAPET]['skillNormal'];
		foreach($skillNormal as $index => $eachSkill)
		{
		    if (isset($this->allPet[$petId2][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel']))
		    {
		        $this->allPet[$petId2][PetDef::VAPET]['skillNormal'][$index]['level'] -=
		            $this->allPet[$petId2][PetDef::VAPET]['skillNormalEvolve'][$eachSkill['id']]['evolveLevel'];
		        if ($this->allPet[$petId2][PetDef::VAPET]['skillNormal'][$index]['level'] < 0)
			    {
			        Logger::fatal("waring!!exchange err, pet[%d], skill[%d], change level err!", $petId2, $eachSkill['id']);
			        $this->allPet[$petId2][PetDef::VAPET]['skillNormal'][$index]['level'] = 0;
			    }
		    }
		}
		unset($this->allPet[$petId2][PetDef::VAPET]['skillNormalEvolve']);
	    */
	    
		$this->allPet[$petId1][PetDef::VAPET]['evolveLevel'] = 0;
		$this->allPet[$petId2][PetDef::VAPET]['evolveLevel'] = 0;

		$confirmOfPet1 = empty($this->allPet[$petId1][PetDef::VAPET]['confirmed'])
            ? array() : $this->allPet[$petId1][PetDef::VAPET]['confirmed'];
		$this->allPet[$petId1][PetDef::VAPET]['confirmed'] = empty($this->allPet[$petId2][PetDef::VAPET]['confirmed'])
            ? array() : $this->allPet[$petId2][PetDef::VAPET]['confirmed'];
		$this->allPet[$petId2][PetDef::VAPET]['confirmed'] = $confirmOfPet1;
	}

    /**
     * 技能学习，连续失败的次数
     * @param $petId
     * @return int
     */
    public function getFailNum($petId)
	{
        if(!isset($this->allPet[$petId][PetDef::VAPET]['failNum']))
        {
            return 0;
        }
        return $this->allPet[$petId][PetDef::VAPET]['failNum'];
	}

    public function setFailNum($petId, $failNum)
    {
        $this->allPet[$petId][PetDef::VAPET]['failNum'] = $failNum;
    }

    public function getUpdateTime($petId)
    {
        if (isset($this->allPet[$petId][PetDef::VAPET]['updateTime']))
        {
            return $this->allPet[$petId][PetDef::VAPET]['updateTime'];
        }
        return 0;
    }
    
    public function setUpdateTime($petId, $updateTime = 0)
    {
        if (empty($updateTime))
        {
            $updateTime = Util::getTime();
        }
        $this->allPet[$petId][PetDef::VAPET]['updateTime'] = $updateTime;
    }
    
	public function update()
	{
		if ( $this->allPet == $this->allPetBack )
		{
			Logger::trace( 'nothing changed for pet');
			return ;
		}
		
		$clearBattle = false;
		$allBookPet = PetLogic::getPetHandbookInfo($this->uid);
		
		foreach ( $this->allPet as $petid => $val )
		{
			if ( !isset( $this->allPetBack[ $petid ] ) )
			{
				//新加宠物
				$this->setUpdateTime($petid);
				$val[PetDef::VAPET]['updateTime'] = Util::getTime();
				PetDAO::addNewPet( $val );
				if( !in_array( $val[PetDef::PETTMPL] , $allBookPet) )
				{
					$clearBattle = true;
				}
				
			}
			else if ( $this->allPetBack[ $petid ]!= $val )
			{
				//有宠物的数据更新
			    $this->setUpdateTime($petid);
			    $val[PetDef::VAPET]['updateTime'] = Util::getTime();
				PetDAO::updatePet( $petid , $val );
				unset( $this->allPetBack[ $petid ] );
			}
			else 
			{
				unset( $this->allPetBack[ $petid ] );
			}
		}
		
		if ( !empty( $this->allPetBack ) )
		{
			$petidArrBeSwallow = Util::arrayExtract($this->allPetBack, PetDef::PETID);
			
			foreach ( $petidArrBeSwallow as $bePetid )
			{
			    //$this->setUpdateTime($bePetid);
				PetDAO::deletePet($this->uid, $bePetid);
				$clearBattle = true;//不做啥优化了，删就清了吧
			}
			
		}
		
		$guid = RPCContext::getInstance()->getUid();
		if ( $guid == $this->uid )
		{
			RPCContext::getInstance()->setSession( PetDef::PET_SESSION, $this->allPet );
		}
		
		$this->allPetBack = $this->allPet;
		if( $clearBattle )
		{
			Logger::debug('refresh battle data because of and or delete');
			EnUser::getUserObj($this->uid)->modifyBattleData();
		}
	}

	private function checkPetSkillNormal()
	{
	    $allPetInfo = $this->allPet;
	    foreach($allPetInfo as $petId => $petInfo)
	    {
	        $fix = false;
	        $skillNormal = isset($petInfo[PetDef::VAPET]['skillNormal'])?$petInfo[PetDef::VAPET]['skillNormal']:array();
	        $evolveLevel = isset($petInfo[PetDef::VAPET]['evolveLevel'])?$petInfo[PetDef::VAPET]['evolveLevel']:0;
	        $petConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]->toArray();
	        
	        $addLevel = 0;
	        $evolveSkillConf = $petConf['evolveSkill'];
	        
	        foreach($skillNormal as $index => $eachSkill)
	        {
	            $level = $eachSkill['level'];
	            if ($level > $petConf['lrnSkillLvLimit'])
	            {
	                $newlevel = $petConf['lrnSkillLvLimit'];
	                $this->allPet[$petId][PetDef::VAPET]['skillNormal'][$index]['level'] = $newlevel;
	                $fix = true;
	            }
	        }
	        
	        if ($fix)
	        {
	            Logger::fatal("fix success!!!pet[%d], new info is [%s], old is [%s].",$petId,
	                $this->allPet[$petId][PetDef::VAPET]['skillNormal'],
	                $petInfo[PetDef::VAPET]['skillNormal']);
	        }
	    }
	    
	    $this->update();
	}
	
	private function fixEvolveAddLevel()
	{
	    if (!defined('PlatformConfig::PET_DELETE_SKILL_LEVEL_TIME'))
	    {
	        return ;
	    }
	    $compareTime = strtotime(PlatformConfig::PET_DELETE_SKILL_LEVEL_TIME);
	    $allPetInfo = $this->allPet;
	    foreach($allPetInfo as $petId => $petInfo)
	    {
	        $updateTime = $this->getUpdateTime($petId);
	        if ($updateTime >= $compareTime)
	        {
	            continue;
	        }
	        
	        $skillNormal = isset($petInfo[PetDef::VAPET]['skillNormal'])?$petInfo[PetDef::VAPET]['skillNormal']:array();
	        $evolveLevel = isset($petInfo[PetDef::VAPET]['evolveLevel'])?$petInfo[PetDef::VAPET]['evolveLevel']:0;
	        if($evolveLevel == 0)
	        {
	            continue;
	        }
	        
	        $petConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]->toArray();
	         
	        $addLevel = 0;
	        $evolveSkillConf = $petConf['evolveSkill'];
	        foreach ($evolveSkillConf as $l => $addNum)
	        {
	            if ($evolveLevel >= $l)
	            {
	                $addLevel += $addNum;
	            }
	        }

	        if (empty($addLevel))
	        {
	            continue;
	        }
	        
	        foreach($skillNormal as $index => $eachSkill)
	        {
	            $level = $eachSkill['level'];
	            $newlevel = $level - $addLevel;
	            $this->allPet[$petId][PetDef::VAPET]['skillNormal'][$index]['level'] = ($newlevel>1)?$newlevel:1;
	        }
	        
	        Logger::info("fix pet [%d] ok!petTmpl [%d],evolve level is [%d],old skillnormal is [%s],new is [%s]!!!",$petId,$petInfo[PetDef::PETTMPL],$evolveLevel,$skillNormal,$this->allPet[$petId][PetDef::VAPET]['skillNormal']);
	    }
	    
	    $this->update();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
