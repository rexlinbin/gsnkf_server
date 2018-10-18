<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroManager.class.php 156345 2015-02-02 04:00:42Z TiantianZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroManager.class.php $
 * @author $Author: TiantianZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2015-02-02 04:00:42 +0000 (Mon, 02 Feb 2015) $
 * @version $Revision: 156345 $
 * @brief
 *
 **/



class HeroManager
{
	private $uid = 0;
	

	/**
	 * hero对象缓存
	 * @var array(hid => heroObj)
	 */
    private $arrHeroObj = array();
    
    /**
     * 新加的武将
     * @var array(hid)
     */
    private $arrNewHid = array();
    
    /**
     * 删除的武将
     * @param array(hid)
     */
    private $arrDelHid = array();
    
    /**
     * 删除的已经使用过的武将
     * @var unknown_type
     */
    private $arrDelUsedHid = array();
    
    
    private $arrNewStar = array();

    
	public function __construct ($uid)
	{
		$this->uid = $uid;		
				
		//初始化所有在“我的阵容”中的武将
		$arrHeroAttr =  $this->getSession();
		
		if( empty( $arrHeroAttr ) )
		{
			$arrHid = EnFormation::getArrHidInSquad($uid);		
			$arrHeroAttr = HeroLogic::getArrHero($arrHid, HeroDef::$HERO_FIELDS);
			Logger::trace('get squad data from db.arrHeroAttr:%s.',$arrHeroAttr);
			
			if ($uid == RPCContext::getInstance()->getUid() )
			{
				RPCContext::getInstance()->setSession(HeroDef::SESSION_KEY_SQUAD, $arrHeroAttr);
			}
		}
		else
		{
			Logger::trace('get squad data from session');
		}
		foreach($arrHeroAttr as $heroAttr)
		{
		    if(empty($heroAttr))
		    {
		        Logger::fatal('hero in formation has empty data.');
		        continue;
		    }
			$heroObj = $this->createHeroObj($heroAttr);
			$this->arrHeroObj[ $heroAttr['hid'] ]    =    $heroObj;
		}
	}
	
	
	/**
	 * 获取所有武将的基本信息， 这个函数应该只在登录的时候需要调用一次
	 */
	public function getAllHero()
	{
		$this->arrHeroObj	=	$this->getAllHeroObj();		
		$returnData = array();
		foreach( $this->arrHeroObj as $heroObj )
		{
			$returnData[ $heroObj->getHid() ] = $heroObj->getInfo(); 
		}
		
		return $returnData;		
	}
	
	public function getAllHeroObjInSquad()
	{
	    if(empty($this->arrHeroObj))
	    {
	        throw new FakeException('no heroinfo in HeroManager!!!!');
	    }
	    
	    $arrHid = EnFormation::getArrHidInSquad($this->uid);
	    
	    $arrObj = array();
	    foreach($arrHid as $hid)
	    {
	    	$arrObj[$hid] = $this->getHeroObj($hid);
	    }
	    
	    return $arrObj;
	}

	/**
	 * 
	 * @return array(OtherHeroObj)
	 */
	public function getAllHeroObj()
	{
		//所有在数据库中的武将
		$arrHeroAttr = HeroLogic::getAllUsedHeroByUid($this->uid);
		foreach($arrHeroAttr as $heroAttr)
		{
			if( !isset( $this->arrHeroObj[ $heroAttr['hid'] ]  ) )
			{
				$this->arrHeroObj[ $heroAttr['hid'] ] = $this->createHeroObj($heroAttr);
				$heroObj    =    $this->arrHeroObj[ $heroAttr['hid'] ];
				if($heroObj->isEquiped()
				        && (EnFormation::isHidInFormation($heroObj->getHid(), $this->uid)) == FALSE)
				{
				    Logger::fatal('hero %s is not in formation,but is equiped,unequip this hero.',$heroAttr['hid']);
				    HeroLogic::unEquipeHero($heroObj->getHid(),HeroDef::EQUIP_ALL);
				}
			}
		}
		//所有未使用的武将
		$userObj = EnUser::getUserObj($this->uid);
		$allUnusedHero = $userObj->getAllUnusedHero();
		foreach($allUnusedHero as $hid => $heroInfo)
		{
		    if(isset($arrHeroAttr[$hid]))
		    {
		        Logger::warning('hero data error!hid %d both is unused and used.delete it from unused',$hid);
                $userObj->delUnusedHero($hid);
		        continue;
		    }
			if( !isset( $this->arrHeroObj[ $hid ]  ) )
			{
				$this->arrHeroObj[ $hid ] = $this->createHeroObj( HeroLogic::getInitData($this->uid, $hid, $heroInfo['htid'],$heroInfo['level']) );
			}
		}
		
		return $this->arrHeroObj;
	}
	
	
	public function getArrHeroObj($arrHid)
	{
	    $arrHidInDb = array();
	    $arrHeroObj = array();
	    foreach($arrHid as $hid)
	    {
	        if( !isset( $this->arrHeroObj[ $hid ]  ) )
	        {
	            $arrHidInDb[] = $hid;
	        }
	        else
	        {
	            $arrHeroObj[$hid] = $this->arrHeroObj[ $hid ];
	        }
	    }
	    $arrHeroInfo = HeroLogic::getArrHero($arrHidInDb);
	    foreach($arrHeroInfo as $heroAttr)
	    {
	        $hid = $heroAttr['hid'];
	        if( !isset( $arrHeroObj[$hid]  ) )
	        {
	            $this->arrHeroObj[$hid] = $this->createHeroObj($heroAttr);
	            $arrHeroObj[$hid]  = $this->arrHeroObj[$hid];
	        }
	    }
	    $userObj = EnUser::getUserObj($this->uid);
	    $allUnusedHero = $userObj->getAllUnusedHero();
	    foreach($allUnusedHero as $hid => $heroInfo)
	    {
	        if( !isset( $arrHeroObj[ $hid ]  ) 
	                && (in_array($hid, $arrHidInDb)))
	        {
	            $this->arrHeroObj[ $hid ] = $this->createHeroObj( HeroLogic::getInitData($this->uid, $hid, $heroInfo['htid'],$heroInfo['level']) );
	            $arrHeroObj[$hid]  = $this->arrHeroObj[$hid];
	        }
	    }
	    if(count($arrHeroObj) != count($arrHid))
	    {
	        throw new FakeException('fatal error.arrhid %s arrhid of heroboj %s',$arrHid,array_keys($arrHeroObj));
	    }
	    return $arrHeroObj;
	}
	/**
	 * 
	 * @param int $hid
	 * @return HeroObj or OtherHeroObj
	 */
	public function getHeroObj($hid)
	{
		//如果之前已经删除此hero，但是数据库还没有修改。需要通过此判断，避免从数据库中获取一个其实要被删掉的武将数据
		if( in_array($hid, $this->arrDelHid) )
		{
			throw new InterException('hid:%d already deleted', $hid);
		}
		if ( isset( $this->arrHeroObj[$hid] ) )
		{
			return $this->arrHeroObj[$hid];
		}
		
		$userObj = EnUser::getUserObj($this->uid);
		$heroInfo = $userObj->getUnusedHero($hid);
		if( empty($heroInfo) )
		{
		    //先判断是否在session中  session中没有在去数据库取
			$heroAttr = HeroLogic::getHero($hid);
			if ( empty( $heroAttr ) )
			{
				throw new FakeException('not found hid:%d. uid:%d', $hid, $this->uid);				
			}
		}
		else
		{
			$heroAttr = HeroLogic::getInitData($this->uid, $hid, $heroInfo['htid'],$heroInfo['level']);
		}
	    Logger::trace('getHeroObj heroinfo %s.',$heroAttr);
		$this->arrHeroObj[ $hid ] = $this->createHeroObj($heroAttr);
		
		return $this->arrHeroObj[$hid];
	}
	
	
	public function getMasterHeroObj()
	{
		$userObj = EnUser::getUserObj( $this->uid );
		return $this->getHeroObj( $userObj->getMasterHid() );
	}
	
	public function addNewHeroWithLv($htid,$level)
	{
	    if (!HeroUtil::checkHtid($htid))
	    {
	        throw new FakeException("invalid htid %d for addHero. uid:%d", $htid, $this->uid);
	    }
	    if(HeroUtil::isMasterHtid($htid))
	    {
	        throw new FakeException('can not add master hero.htid %s is a master hero.',$htid);
	    }
	    
	    if($this->getHeroNum() >= HeroConf::MAX_HERO_NUM)
	    {
	        Logger::warning('addNewHero:heronum is to limit now.');
	    }
	    
	    $hid = IdGenerator::nextId('hid');
	    if ( empty($hid) )
	    {
	        throw new InterException('get hid failed');
	    }
	    $userObj = EnUser::getUserObj($this->uid);
	    $userObj->addUnusedHero($hid, $htid,$level);
	    
	    if ( isset( $this->arrHeroObj[$hid] ) )
	    {
	        throw new InterException('duplicated hid. uid:%d already has hid:%d', $this->uid, $hid);
	    }
	    $this->arrHeroObj[$hid] = $this->createHeroObj( HeroLogic::getInitData($this->uid, $hid, $htid, $level) );
	    
	    $starId    =    Creature::getCreatureConf($htid, CreatureAttr::STAR_ID);
	    $uid    =    RPCContext::getInstance()->getUid();
	    $star    =    array();
	    if(!empty($starId))
	    {
	        $star    =    EnStar::addNewStar($uid, $starId);

	    	foreach($star as $sid => $stid)
        	{
        		$this->arrNewStar[$sid] = $stid;
        	}
	    }
	    
	    $this->arrNewHid[] = $hid;
	    Logger::trace('addNewHero.htid %s.',$htid);
	    $ret    =    array('star'=>$star,'hero'=>array($hid=>$htid));
	    return $ret;
	}
	
	/**
	 * 注意！！！！！！addNewHero不会因为武将数目到达上限而抛出异常，所以所有有可能触发addNewHero的请求，必须先判断当前的武将数目是否超过了限制
	 * 添加一个武将，新添加到武将并没有在数据库中插入数据。 在initHero之后才会在数据中有一条数据
	 * 调用此接口后需要调用：userObj->update()
	 * @param int $htid
	 * @return int $hid
	 */
	public function addNewHero($htid)
	{
		$ret = $this->addNewHeroWithLv($htid, 1);
		$hids = array_keys($ret['hero']);
		return $hids[0];
	}
	
	/**
	 * 
	 * @param $arrHero $arrHero  array(htid=>num)
	 * @return array
	 */
	public function addNewHeroes( $arrHero )
	{
	    $arrHid = array();
	    foreach( $arrHero as $htid => $num )
	    {
	    	for( $i=0; $i < $num; $i++)
	    	{
	    		$arrHid[] = $this->addNewHero($htid);
	    	}
	    }
	    return $arrHid;
	}


	public function addNewHeroWithStar($htid)
	{
	    $ret    =    $this->addNewHeroWithLv($htid, 1);
	    return $ret;
	}
	
	/**
	 * 将一个未使用过的武将初始化
	 * @param int $hid
	 */
	public function initHero($hid, $setHeroAttr = array())
	{
		$userObj = EnUser::getUserObj($this->uid);
		
		$heroAttr = $userObj->initHero($hid, $setHeroAttr);
		
		if( !isset( $this->arrHeroObj[$hid] ) )
		{
			$this->arrHeroObj[$hid] = $this->createHeroObj($heroAttr);
		}
	}
	
	
	
	
	/**
	 * 删除一个武将
	 * 调用此函数后需要调用userObj->update
	 * @param int $hid
	 */
	public function delHeroByHid($hid)
	{

		$heroObj = $this->getHeroObj($hid);
		if(! $heroObj->canBeDel() )
		{
			throw new FakeException('cant del hero. uid:%d, hid:%d', $this->uid, $hid);
		}
		
		$userObj = EnUser::getUserObj($this->uid);
		if (  $userObj->getUnusedHeroHtid($hid) )
		{
			$userObj->delUnusedHero($hid);
			//Logger::info('delUnusedHeroByHid hid:%d', $hid);		
		}
		else
		{
			$this->arrDelUsedHid[] = $hid;
			/* 不能在此就直接改数据库，需要在update中修改数据库，而且可以批量修改
			$arrField = array(
					'delete_time' => Util::getTime(),
					);
			$ret = HeroDao::update($hid, $arrField);
			if( $ret['affected_rows'] != 1)
			{
				Logger::fatal('del hero err. affected_rows=%d', $ret['affected_rows']);
			}	
			*/
		}
		
		unset( $this->arrHeroObj[$hid] );
		
		$this->arrDelHid[] = $hid;
		
		return true;
	}
	
	

	private function createHeroObj($heroAttr)
	{
		if( $this->uid != $heroAttr['uid'] )
		{
			throw new InterException('try to get hero of other. uid:%d, hid:%d', $this->uid, $heroAttr['hid']);
		}
		
		$guid = RPCContext::getInstance()->getUid();
		if ($guid == $heroAttr['uid'])
		{
			return new HeroObj($heroAttr);
		}
		else
		{
			return new OtherHeroObj($heroAttr);
		}
	}
	
	
	public function rollback()
	{
		Logger::debug('rollback. delHid:%s, delUsedHid:%s, newHid:%s, newStar:%s',
				$this->arrDelHid, $this->arrDelUsedHid, $this->arrNewHid, $this->arrNewStar);
		
		//对于删除的武将不需要特殊处理，但是对于新加的武将需要把它从$this->arrHeroObj中删除掉
		foreach( $this->arrNewHid as $hid )
		{
			if( isset( $this->arrHeroObj[$hid] )  )
			{
				unset( $this->arrHeroObj[$hid] );
			}
		}
		
		foreach ( $this->arrHeroObj as $heroObj )
		{
			$heroObj->rollback();
		}
		
		
		$this->arrDelHid = array();
		$this->arrDelUsedHid = array();
		$this->arrNewHid = array();
		$this->arrNewStar = array();
	}

	public function update()
	{		
		$arrNewHero = array();
		foreach ($this->arrHeroObj as $hid => $heroObj)
		{		    
			$heroObj->update();
			if( in_array($heroObj->getHid(), $this->arrNewHid ) )
			{
				$arrNewHero[$hid] = $heroObj->getInfo();
			}
		}
		if( count($arrNewHero) != count($this->arrNewHid) )
		{
			Logger::fatal('not all new hero be updated. arrNewHid:%s, arrHero:%s', $this->arrNewHid, $this->arrHeroObj);
		}
		
		//更新一下session。因为在session中记录了武将个数，所以需要计算一下武将个数变化
		$deltHeroNum = count($arrNewHero) - count($this->arrDelHid);				
		$this->updateSession($deltHeroNum);
		
		//处理删除的使用过武将	
		if( !empty($this->arrDelUsedHid) )	
		{
			$arrField = array(
					'delete_time' => Util::getTime(),
			);
			foreach($this->arrDelUsedHid as $hid)
			{
			    Logger::info('delUsedHeroByHid hid:%s',$hid);
				$ret = HeroDao::update($hid, $arrField);
				if( $ret['affected_rows'] != 1)
				{
					Logger::fatal('del hid:%d err. affected_rows=%d', $hid, $ret['affected_rows']);
				}
			}
		}
		$this->arrDelHid = array();
		$this->arrDelUsedHid = array();
		
		$arrNewHtid = array();
		if(!empty($arrNewHero))
		{
			$this->arrNewHid = array();
			RPCContext::getInstance()->sendMsg(array($this->uid),
			PushInterfaceDef::HERO_ADD_NEW_HERO, $arrNewHero);
		    $maxQuality = 0;
			foreach($arrNewHero as $hid => $heroInfo)
			{
			    $htid = $heroInfo['htid'];
			    $arrNewHtid[] = $htid;
			    $quality = Creature::getHeroConf($htid, CreatureAttr::QUALITY);
			    if($quality > $maxQuality)
			    {
			        $maxQuality = $quality;
			    }
			}
			EnAchieve::updateHeroColor($this->uid, $maxQuality);
			//武将图鉴
			HeroLogic::updateHeroBook($this->uid, $arrNewHtid);
		}
		
		//新加武将可能会触发新加名将，这里需要处理新加的名将
		if(!empty($this->arrNewStar))
		{
		    $myStar = MyStar::getInstance($this->uid);
		    $myStar->update();
		    $sendInfo = array();
		    foreach($this->arrNewStar as $sid => $stid)
		    {
		        $starInfo = $myStar->getStarInfo($sid);
		        $sendInfo[] = $starInfo;
		    }
		    $this->arrNewStar = array();
		    if(!empty($sendInfo))
		    {
		        RPCContext::getInstance()->sendMsg(array($this->uid),
		                PushInterfaceDef::STAR_ADD_NEW_NOTICE, $sendInfo);
		    }
		}
	}
	
	
	public function getSession()
	{
		if( $this->uid == RPCContext::getInstance()->getUid() )
		{
			return RPCContext::getInstance()->getSession(HeroDef::SESSION_KEY_SQUAD);
		}
		return NULL;
	}
	
	/**
	 * 在 1.更改阵型数据时或者 2.更改阵型上的武将时     需要updateSession
	 * @param int $deltHeroNum
	 */
	public function updateSession($deltHeroNum = 0)
	{
		if( $this->uid != RPCContext::getInstance()->getUid() )
		{
			return;
		}
		
		//在session中记录一下武将总数
		$allNum = RPCContext::getInstance()->getSession(HeroDef::SESSION_KEY_ALL_NUM);
		if(!empty($allNum) && $deltHeroNum != 0)
		{
			RPCContext::getInstance()->setSession(HeroDef::SESSION_KEY_ALL_NUM, $allNum + $deltHeroNum);
		}
		
		//把阵容中的武将都放到session中
		$arrHid = EnFormation::getArrHidInSquad($this->uid);
		$arrHeroAttr = array();
		foreach($arrHid as $hid)
		{
			$arrHeroAttr[$hid] = $this->getHeroObj($hid)->getAllAttr();
		}
		RPCContext::getInstance()->setSession(HeroDef::SESSION_KEY_SQUAD, $arrHeroAttr);
	}
	
	public function getHeroNum()
	{
		if($this->uid == RPCContext::getInstance()->getUid() )
		{
			$allNum = RPCContext::getInstance()->getSession(HeroDef::SESSION_KEY_ALL_NUM);
			if(!empty($allNum))
			{
				Logger::debug('hero num:%d.', $allNum);
				return $allNum;
			}
		}
		$userObj = EnUser::getUserObj($this->uid);
		$unusedNum = $userObj->getUnusedHeroNum();
		$usedNum = HeroDao::getHeroNumByUid($this->uid);
		$allNum = $unusedNum + $usedNum;
		
		Logger::debug('hero num. unused:%d, used:%d', $unusedNum, $usedNum);
				
		if( $this->uid == RPCContext::getInstance()->getUid() )
		{
			RPCContext::getInstance()->setSession(HeroDef::SESSION_KEY_ALL_NUM, $allNum);
		}
		return $allNum;
	}
	
	public function hasTooManyHeroes()
	{
	    $userObj    =    EnUser::getUserObj($this->uid);
		return $this->getHeroNum() >= $userObj->getHeroLimit();
	}
	/**
	 * 只有script/BattleTest.php使用
	 * @param unknown_type $htid
	 */
	public function getHeroNumByHtid($htid)
	{
	    $num    =    0;
	    $allHeroObj    =    $this->getAllHeroObj();
	    foreach($allHeroObj as $hid    =>    $heroObj)
	    {
	        if($heroObj->getHtid() == $htid)
	        {
	            $num++;
	        }
	    }
	    return $num;
	}

	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */