<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Creature.class.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/creature/Creature.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief
 *
 **/

class Creature
{
	
    /**
     * MST的id，hero的HTID
     * @var int
     */
	private $id;
	
	/**
	 * creature的所有属性，刚刚创建时只有hid，level等基本属性。 其他比如maxHp、三维、技能等属性在getBattleInfo中计算得到
	 * @var array
	 */
	protected $creatureInfo;
	
	/**
	 * 属性加成数组
	 * @var array
	 */
	protected $arrAddAttr = array();
	
	/**
	 * 觉醒能力id数组
	 * @var array
	 */
	protected $arrAwakeAbility;

	public function __construct($id)
	{
		$this->id = $id;
		if (!isset(btstore_get()->HEROES[$this->id])  && 
				!isset(btstore_get()->MONSTERS[$this->id]))
		{
			Logger::fatal('invalid creature:%d', $id);
			throw new Exception('inter');
		}		
		$this->init();
	}
	
	
	public function getEvolveLv()
	{
	    return $this->getConf(CreatureAttr::EVOLVE_LEVEL);
	}
	
	public function getLevel()
	{
		if( isset( $this->creatureInfo[PropertyKey::LEVEL] )   )
		{
			return $this->creatureInfo[PropertyKey::LEVEL];
		}
	    return $this->getConf(CreatureAttr::LEVEL);
	}
	
	public function getHtid()
	{
	    return $this->creatureInfo[PropertyKey::HTID];
	}
	
	public function getBaseHtid()
	{
	    return $this->creatureInfo[PropertyKey::BASE_HTID];
	}
	
	public function getHid()
	{
	    return $this->id;
	}
	
	
	public function isHero()
	{
	    return false;
	}
	
	public function setLevel($level)
	{
	    $this->creatureInfo[ PropertyKey::LEVEL ] = $level;
	}
	/**
	 * 只有新手剧情战斗用
	 * @param unknown_type $attr
	 * @param unknown_type $value
	 */
	public function setAttr($attr,$value)
	{
	    $this->creatureInfo[$attr] = $value;
	}
	
	public function getConf($field)
	{
	    return self::getCreatureConf($this->id, $field);
	}
	
	
	public static function getHeroConf($id, $field)
	{
	    if( !isset(btstore_get()->HEROES[$id]) )
	    {
	        Logger::fatal('no htid:%d in heros', $id);
	        throw new Exception('inter');
	    }
	    $creatureConf = btstore_get()->HEROES[$id];
	
	    if ( isset($creatureConf[$field])  )
	    {
	        return $creatureConf[$field];
	    }
	    else
	    {
	        return 0;
	    }
	}
	
	public static function getMonsterConf($id, $field)
	{
	    if( !isset(btstore_get()->MONSTERS[$id]) )
	    {
	        Logger::fatal('no htid:%d in monsters', $id);
	        throw new Exception('inter');
	    }
	    $creatureConf = btstore_get()->MONSTERS[$id];
	
	    if ( isset($creatureConf[$field])  )
	    {
	        return $creatureConf[$field];
	    }
	    else
	    {
	        return 0;
	    }
	}
	
	public static function getCreatureConf($id, $field)
	{
	    if(HeroUtil::isHeroByHtid( $id ))
	    {
	        return self::getHeroConf($id, $field);
	    }
	    else
	    {
	        return self::getMonsterConf($id, $field);
	    }
	}
	

	/**
	 * 注意！！
	 * init函数调用的时候，$this->creatureInfo还没有初始化，
	 * 所以在init函数中不能调用getHtid这个函数
	 * 或者使用$this->creatureInfo[PropertyKey::HTID]这个数据
	 */
	public function init()
	{
		//刚开始只需要一些很基本的值， 和直接能从配置中获得的属性
		$this->creatureInfo = array(
				PropertyKey::HID => $this->getHid(),
				PropertyKey::HTID => $this->id,
		        PropertyKey::GENDER=>$this->getConf(CreatureAttr::GENDER),
				PropertyKey::BASE_HTID => $this->getConf(CreatureAttr::BASE_HTID),
				PropertyKey::LEVEL => $this->getLevel(),
		        PropertyKey::EVOLVE_LEVEL => $this->getEvolveLv(),
				PropertyKey::COUNTRY => $this->getConf(CreatureAttr::COUNTRY),
	
				PropertyKey::CHAOS_SKILL => $this->getConf( CreatureAttr::CHAOS_SKILL ),
				PropertyKey::CHARM_SKILL => $this->getConf( CreatureAttr::CHARM_SKILL ),
				PropertyKey::LAUGH_SKILL => $this->getConf( CreatureAttr::LAUGH_SKILL ),
				PropertyKey::ATTACK_SKILL => $this->getConf( CreatureAttr::ATTACK_SKILL ),
				PropertyKey::RAGE_SKILL => $this->getConf( CreatureAttr::RAGE_SKILL ),
				PropertyKey::PARRY_SKILL => $this->getConf( CreatureAttr::PARRY_SKILL  ),
				PropertyKey::DEATH_SKILL => $this->getConf( CreatureAttr::DEATH_SKILL  ),
				PropertyKey::ROUND_BEGIN_SKILL => $this->getConf( CreatureAttr::ROUND_BEGIN_SKILL  ),
				PropertyKey::BIG_ROUND_BEGIN_SKILL => $this->getConf( CreatureAttr::BIG_ROUND_BEGIN_SKILL  ),
				PropertyKey::ROUND_END_SKILL => $this->getConf( CreatureAttr::ROUND_END_SKILL  ),				
				PropertyKey::ARR_SKILL => $this->getConf( CreatureAttr::RANDOM_ATTACK_SKILL )->toArray(),
				PropertyKey::ARR_IMMUNED_BUFF => $this->getConf( CreatureAttr::ARR_IMMUNED_BUFF )->toArray(),
				PropertyKey::ARR_IMMUNED_SKILL_TYPE => $this->getConf( CreatureAttr::ARR_IMMUNED_SKILL_TYPE )->toArray(),
				PropertyKey::ARR_IMMUNED_TARGET_TYPE => $this->getConf( CreatureAttr::ARR_IMMUNED_TARGET_TYPE )->toArray(),
				PropertyKey::ARR_IMMUNED_TRIGGER_CONDITION => array(),
		        
				PropertyKey::ARR_ATTACK_SKILL => array(),
				PropertyKey::ARR_RAGE_SKILL => array(),
				PropertyKey::ARR_DEATH_SKILL => array(),
				PropertyKey::ARR_PARRY_SKILL => array(),
				PropertyKey::ARR_DODGE_SKILL => array(),
				PropertyKey::ARR_ROUND_BEGIN_SKILL=>array(),
				PropertyKey::ARR_ROUND_END_SKILL => array(),
				
				PropertyKey::ARR_ATTACK_BUFF => array(),
				PropertyKey::ARR_RAGE_BUFF => array(),
				PropertyKey::ARR_DEATH_BUFF => array(),
				PropertyKey::ARR_PARRY_BUFF => array(),
				PropertyKey::ARR_DODGE_BUFF => array(),
				PropertyKey::ARR_ROUND_BEGIN_BUFF => array(),
				PropertyKey::ARR_ROUND_END_BUFF => array(),
		        
		        //攻击时怒气增长相关的值
		        PropertyKey::RAGE_GET_BASE => $this->getConf( CreatureAttr::RAGE_GET_BASE ),
		        PropertyKey::RAGE_GET_AMEND => $this->getConf( CreatureAttr::RAGE_GET_AMEND ),
		        PropertyKey::RAGE_GET_RATIO => $this->getConf( CreatureAttr::RAGE_GET_RATIO ),
		        PropertyKey::FATAL_RATIO    => $this->getConf(CreatureAttr::FATAL_RATIO),
		
		        //以下为有属性加成的属性
		        //三维
		        PropertyKey::STG_BASE => $this->getConf(CreatureAttr::STRENGTH_INIT),
		        PropertyKey::REIGN_BASE => $this->getConf(CreatureAttr::REIGN_INIT),
		        PropertyKey::ITG_BASE => $this->getConf(CreatureAttr::INTELLIGENCE_INIT),
		        //血量相关的属性
		        PropertyKey::HP_BASE=> $this->getByEvolveLv(CreatureAttr::HP, CreatureAttr::HP_INC),
		        PropertyKey::HP_RATIO=>0,
		        PropertyKey::HP_FINAL=>0,
                //基础怒气值		       
		        PropertyKey::CURR_RAGE => $this->getConf(CreatureAttr::RAGE),		        
		        //卡牌基础最终伤害、免伤
		        PropertyKey::ABSOLUTE_ATTACK => $this->getConf( CreatureAttr::ABSOLUTE_ATTACK ),
		        PropertyKey::ABSOLUTE_DEFEND => $this->getConf( CreatureAttr::ABSOLUTE_DEFEND ),
		        //物、魔   绝对 攻击 和防御
		        PropertyKey::ABSOLUTE_MAGIC_ATTACK => 0,
		        PropertyKey::ABSOLUTE_MAGIC_DEFEND => 0,
		        PropertyKey::ABSOLUTE_PHYSICAL_ATTACK => 0,
		        PropertyKey::ABSOLUTE_PHYSICAL_DEFEND => 0,
		        PropertyKey::MODIFY_PHYSIC_ATK => 0,
		        PropertyKey::MODIFY_PHYSIC_DEF => 0,
		        PropertyKey::MODIFY_RAGE_ATK => 0,
		        PropertyKey::MODIFY_RAGE_DEF => 0,
		        PropertyKey::MODIFY_CURE_RATIO => 0,
		        PropertyKey::MODIFY_BECURE_RATIO => 0,
		        PropertyKey::ABSOLUTE_ATK_RATIO => 0,
		        PropertyKey::ABSOLUTE_DFS_RATIO => 0,
		        // 物、必、魔  攻击力、防御力 附加比例
		        PropertyKey::PHYSICAL_ATTACK_ADDITION => 0,
		        PropertyKey::MAGIC_ATTACK_ADDITION => 0,
		        PropertyKey::PHYSICAL_DEFEND_ADDITION => 0,
		        PropertyKey::MAGIC_DEFEND_ADDITION => 0,
		        //力量、统帅、智力百分比
		        PropertyKey::STG_RATIO    => 0,
		        PropertyKey::REIGN_RATIO   => 0,
		        PropertyKey::ITG_RATIO    => 0,

		        //国家克制/反制
		        PropertyKey::COUNTRY_RESTRAIN_WEI 		  => 0,
		        PropertyKey::COUNTRY_RESTRAIN_SHU 		  => 0,
		        PropertyKey::COUNTRY_RESTRAIN_WU 		  => 0,
		        PropertyKey::COUNTRY_RESTRAIN_QUN 		  => 0,
		        PropertyKey::COUNTRY_COUNTER_WEI 		  => 0,
		        PropertyKey::COUNTRY_COUNTER_SHU 		  => 0,
		        PropertyKey::COUNTRY_COUNTER_WU 		  => 0,
		        PropertyKey::COUNTRY_COUNTER_QUN 		  => 0,		     

		        PropertyKey::PHYSICAL_PENETRATE           => 0,
		        PropertyKey::PHYSICAL_RESISTANCE          => 0,
		        PropertyKey::MAGIC_PENETRATE              => 0,
		        PropertyKey::MAGIC_RESISTANCE             => 0,
		        PropertyKey::PENETRATE_ADDITION           => 0,
		        PropertyKey::MORALE           			  => 0,
		        PropertyKey::MODIFY_CURE            	  => 0,
		        PropertyKey::MODIFY_BECURE            	  => 0,
		        
		        PropertyKey::BURN_DAMAGE            	  => 0,
		        PropertyKey::POISON_DAMAGE            	  => 0,
		        PropertyKey::BURN_RESISTANCE          	  => 0,
		        PropertyKey::POISON_RESISTANCE            => 0,
		        
		        PropertyKey::PVP_DAMAGE_ADDITION		  => 0,
		        PropertyKey::PVP_DAMAGE_RESIS_ADDITION	  => 0,
		        
		        PropertyKey::BURN_DAMAGE_ADDITION         => 0,
		        PropertyKey::POISON_DAMAGE_ADDITION       => 0,
		        PropertyKey::BURN_RESIS_ADDITION          => 0,
		        PropertyKey::POISON_RESIS_ADDITION        => 0,
		        
		        PropertyKey::MAX_SUFFER_DAMAGE_REVERSE		=> 0,
				PropertyKey::DODGE=>$this->getConf(CreatureAttr::DODGE_INIT),
		        PropertyKey::FATAL => $this->getConf(CreatureAttr::FATAL_INIT),
		        PropertyKey::PARRY => $this->getConf(CreatureAttr::PARRY_INIT),
		        PropertyKey::HIT => $this->getConf(CreatureAttr::HIT_INIT),
		        // 物、必、魔  攻击倍率（百分比）基础值
		        PropertyKey::PHYSICAL_ATTACK_BASE => 
		            $this->getByEvolveLv(CreatureAttr::PHYSIC_ATK_INIT, CreatureAttr::PHYSIC_ATK_INC),
		        PropertyKey::MAGIC_ATTACK_BASE => 
		            $this->getByEvolveLv(CreatureAttr::MAGIC_ATK_INIT, CreatureAttr::MAGIC_ATK_INC),
		        // 物、必、魔  防御倍率（百分比）基础值
		        PropertyKey::PHYSICAL_DEFEND_BASE => 
		            $this->getByEvolveLv(CreatureAttr::PHYSIC_DEF_INIT, CreatureAttr::PHYSIC_DEF_INC),
		        PropertyKey::MAGIC_DEFEND_BASE => 
		            $this->getByEvolveLv(CreatureAttr::MAGIC_DEF_INIT, CreatureAttr::MAGIC_DEF_INC),
		        
		        // 物、必、魔  攻击倍率（百分比）
		        PropertyKey::PHYSICAL_ATTACK_RATIO => $this->getConf(CreatureAttr::PHYSICAL_ATK_RATIO_INIT),
		        PropertyKey::MAGIC_ATTACK_RATIO => $this->getConf(CreatureAttr::MAGIC_ATK_RATIO_INIT),
		        // 物、必、魔  防御 倍率（百分比）
		        PropertyKey::PHYSICAL_DAMAGE_IGNORE_RATIO => $this->getConf(CreatureAttr::PHYSICAL_IGNORE_RATIO_INIT),
		        PropertyKey::MAGIC_DAMAGE_IGNORE_RATIO => $this->getConf(CreatureAttr::MAGIC_IGNORE_RATIO_INIT),
		        //
		        //通用伤害基础值		        
		        PropertyKey::GENERAL_ATTACK_BASE =>
		                    $this->getByEvolveLv(CreatureAttr::GENERAL_ATTACK_INIT, CreatureAttr::GENERAL_ATTACK_INC),
		                    
		        //通用伤害addition
		        PropertyKey::GENERAL_ATTACK_ADDITION=>0,
		        
		        PropertyKey::PARRY_RESIST => 0,//破挡概率
		        PropertyKey::FATAL_RESIST => 0,//抗暴概率
		        
		        PropertyKey::ABSOLUTE_GENERAL_ATTACK => 0,
				);
	}
	
	public function setAddAttrByBaseLv($baseLv)
	{
	    if($baseLv!=BaseLevel::NORMAL && ($baseLv!=BaseLevel::HARD))
	    {
	        return;
	    }
	    $baseLvName    =    CopyConf::$BASE_LEVEL_INDEX[$baseLv];
	    $conf    =    $this->getConf($baseLvName);
	    if(empty($conf))
	    {
	        return;
	    }
	    $addAttr    =    array();
	    foreach($conf as $key=>$value)
	    {
	        if(!isset($addAttr[$key]))
	        {
	            $addAttr[$key] = 0;
	        }
	        $addAttr[$key]+=$value;
	    }
	    $this->setAddAttr(HeroDef::ADD_ATTR_BY_BASELV, $addAttr);
	}
	
	
	/**
	 * 1.读取配置表中的配置属性(没有等级、转生的属性)
     * 2.根据等级、转生次数重新计算属性值（血量基础值、通用攻击基础值、物攻物防基础值，法攻法防基础值）
     * 3.属性加成
     * 4.计算三维、maxHp
     * 5.计算战斗力
	 * @return array:
	 */
	public function getBattleInfo()
	{
	    //在getBattleInfo中再次调用init的原因
	    //1.在EnFormation中getMonsterFormationInfo会重新设置monster的等级   影响getByEvolveLv函数的计算值
	    //2.如果不加init函数，连续调用getBattleInfo多次会造成属性加成的多次叠加，造成数据错误
	    $this->init();
	    $this->replaceInitAttr();
	    //getAddAttr会用到等级level，必须在重设level的代码之后
		$addAttr = $this->getAddAttr();
		$this->addAttr($addAttr);
		$this->getSanwei();
		$this->getMaxHp();
		$this->creatureInfo['equipInfo']    =    $this->getEquipInfo();
		//进行技能替换、附加       先附加觉醒能力的技能或者buff，再附加技能书的技能或者buff
		$this->addAwakeAbilitySkillBuff();
// 		$this->addBookSkillBuff();
		$this->creatureInfo[PropertyKey::FIGHT_FORCE] = $this->getFightForce();
		return $this->creatureInfo;
	}
	
	public function addAttr($addAttr)
	{
	    if($this->isHero())
	    {
	        Logger::trace('addAttr htid %s.addattr %s.',$this->id,$addAttr);
	    }
	    foreach ( $addAttr as $attr)
	    {
	        if(empty($attr))
	        {
	            continue;
	        }
	        foreach( $attr as $key => $value )
	        {
	            if( isset( $this->creatureInfo[$key]  ) )
	            {
	                $this->creatureInfo[$key] += $value;
	            }
	            else
	            {
	                $this->creatureInfo[$key] = $value;
	                Logger::trace('att:%s not in battle info', $key);
	            }
	        }
	    }
	}

	public function setAddAttr($key, $value)
	{
	    Logger::trace('setAddAttr key %s value %s hid %d',$key,$value,$this->getHid());
		$this->arrAddAttr[$key] = $value;
	}
	public function getAddAttr()
	{
		//觉醒的属性加成
		if(empty($this->arrAddAttr[HeroDef::ADD_ATTR_BY_AWAIK]))
		{
			$this->arrAddAttr[HeroDef::ADD_ATTR_BY_AWAIK] = $this->getAddAttrByAwakeAbility();
		}		
	 
	    return $this->arrAddAttr;
	}

	public function getSanwei()
	{
	    $this->creatureInfo[PropertyKey::STRENGTH] = $this->creatureInfo[PropertyKey::STG_BASE] * 
	            ( 1 + $this->creatureInfo[PropertyKey::STG_RATIO]/UNIT_BASE);
	    $this->creatureInfo[PropertyKey::REIGN] = $this->creatureInfo[PropertyKey::REIGN_BASE] * 
	            ( 1 + $this->creatureInfo[PropertyKey::REIGN_RATIO]/UNIT_BASE);
	    $this->creatureInfo[PropertyKey::INTELLIGENCE] = $this->creatureInfo[PropertyKey::ITG_BASE] * 
	            ( 1 + $this->creatureInfo[PropertyKey::ITG_RATIO]/UNIT_BASE);
	    
		return array(
				PropertyKey::STRENGTH => $this->creatureInfo[PropertyKey::STRENGTH],
				PropertyKey::REIGN    => $this->creatureInfo[PropertyKey::REIGN],
				PropertyKey::INTELLIGENCE => $this->creatureInfo[PropertyKey::INTELLIGENCE],
				);
	}
	/**
	 * 武将生命=(生命基础值总值*（1+生命百分比总值/10000）+最终值)*(1+(武将.统帅-5000)/10000)
	 */
	public function getMaxHp()
	{
	    $hpBase = $this->creatureInfo[PropertyKey::HP_BASE];
	    $hpRatio = $this->creatureInfo[PropertyKey::HP_RATIO];
	    $hpFinal = $this->creatureInfo[PropertyKey::HP_FINAL];
	    $hp = intval(( $hpBase * (1 + $hpRatio / UNIT_BASE)  + $hpFinal )*
	            (1+ ($this->creatureInfo[PropertyKey::REIGN]-5000)/UNIT_BASE));
	    $this->creatureInfo[PropertyKey::MAX_HP] = $hp;
	    return $hp;
	}
	

	protected function getByEvolveLv($field,$incField)
	{
		$ev = 0;	//进化等级
		$inc = $this->getConf($incField);	//等级成长
		$evBaseRatio = $this->getConf(CreatureAttr::EVOLVE_BASE_RATIO); //进阶基础值系数  基础值随进化的成长百分比
		$ev0 = $this->getConf(CreatureAttr::EVOLVE_INIT_LEVEL); //进阶初始等级（第一次进阶时的等级）
		$ev1 = $this->getConf(CreatureAttr::EVOLVE_GAP_LEVEL); //进阶间隔等级（第一次进阶后，每隔多少等级进阶一次）
		//初始值
		$valInit = $this->getConf( $field);
		//等级增长            在进化等级上的等级增长值
		$valLevel = ( $this->getLevel()-1 ) * ($inc/HeroConf::INC_RATIO);
		//如ev0是40 ev1是10   进化路线：40（进化0次） 50（进化1次） 60（进化2次） 
		//在进化第一次时等级变成1，要将0阶的等级增长加上即39*inc
		//在进化第二次事等级变成1，要将0阶，1阶的等级增长加上即40*inc+49*inc
		//......
		//进阶产生的数据。 每次进阶后等级变成0， 进阶等级+1，下面的公式是为了保证进阶后，整体数值不变
		$valEv = $ev * ( $ev0 * 2 +  $ev1 * ($ev-1) - 2) * $inc / 2 / HeroConf::INC_RATIO;
		$val = $valInit * (1 + $evBaseRatio/UNIT_BASE*$ev)  + $valLevel +  $valEv;
	    return intval($val);
	}
	
	
	/**
	 * 战斗力计算方式（通用攻击+物理防御+法术防御+物理攻击+法术攻击+生命/5）+（三围总和-15000）/100*50的总和
	 * 如果战斗力小于5  赋值为5
	 * @return int
	 */
	public function getFightForce()
	{
	    $fightForce=0;
	    $arrAttr = array(
	            PropertyKey::GENERAL_ATTACK_BASE =>  PropertyKey::GENERAL_ATTACK_ADDITION,
	            PropertyKey::PHYSICAL_DEFEND_BASE => PropertyKey::PHYSICAL_DEFEND_ADDITION,
	            PropertyKey::MAGIC_DEFEND_BASE => PropertyKey::MAGIC_DEFEND_ADDITION,
	            PropertyKey::PHYSICAL_ATTACK_BASE => PropertyKey::PHYSICAL_ATTACK_ADDITION,
	            PropertyKey::MAGIC_ATTACK_BASE => PropertyKey::MAGIC_ATTACK_ADDITION,
	    );
	    foreach($arrAttr as $attr => $addition)
	    {
	        $fightForce    +=    intval($this->creatureInfo[$attr] 
	                * (1 + ($this->creatureInfo[$addition]/UNIT_BASE)));
	    }
	    $fightForce += $this->creatureInfo[PropertyKey::ABSOLUTE_GENERAL_ATTACK];
	    $fightForce += $this->creatureInfo[PropertyKey::ABSOLUTE_PHYSICAL_ATTACK];
	    $fightForce += $this->creatureInfo[PropertyKey::ABSOLUTE_MAGIC_ATTACK];
	    $fightForce += $this->creatureInfo[PropertyKey::ABSOLUTE_PHYSICAL_DEFEND];
	    $fightForce += $this->creatureInfo[PropertyKey::ABSOLUTE_MAGIC_DEFEND];
	    $fightForce += intval($this->creatureInfo[PropertyKey::MAX_HP]/5);
	    $sanWei = $this->creatureInfo[PropertyKey::STRENGTH]+
	                    $this->creatureInfo[PropertyKey::REIGN]+
	                    $this->creatureInfo[PropertyKey::INTELLIGENCE];
	    $fightForce += ($sanWei-HeroConf::FIGHT_FORCE_PLUS_SW)/100*10;
	    
	    // 战力新增影响因素：int(武将等级*PvP属性系数*（PvP伤害增益+PvP免伤增益）/60000)
	    $fightForce += intval($this->getLevel() * HeroConf::PVP_REFER_COEF * ($this->creatureInfo[PropertyKey::PVP_DAMAGE_ADDITION] + $this->creatureInfo[PropertyKey::PVP_DAMAGE_RESIS_ADDITION]) / (6 * UNIT_BASE));
	    
	    // 战力新增影响因素：穿透属性，新武将战斗力=原武将战斗力+int(武将等级*穿透属性/4)
	    $fightForce += intval($this->getLevel() * $this->creatureInfo[PropertyKey::PENETRATE_ADDITION] / 4);
	    
	    // 战力新增影响因素：新武将战斗力=原武将战斗力+int(([86]+[87]+[88]+[89])*0.5)；向下取整
	    $fightForce += intval(($this->creatureInfo[PropertyKey::BURN_DAMAGE] + $this->creatureInfo[PropertyKey::POISON_DAMAGE] + $this->creatureInfo[PropertyKey::BURN_RESISTANCE] + $this->creatureInfo[PropertyKey::POISON_RESISTANCE]) * 0.5);
	    
	    // 战力新增影响因素：新武将战斗力=原武将战斗力+int(武将等级*500*([96]+[97]+[98]+[99])/10000)
	    $fightForce += intval($this->getLevel() * 500 * ($this->creatureInfo[PropertyKey::BURN_DAMAGE_ADDITION] + $this->creatureInfo[PropertyKey::POISON_DAMAGE_ADDITION] + $this->creatureInfo[PropertyKey::BURN_RESIS_ADDITION] + $this->creatureInfo[PropertyKey::POISON_RESIS_ADDITION]) / UNIT_BASE);
	    
	    // 战力新增影响因素：新武将战斗力=原武将战斗力+士气*武将等级*3
	    $fightForce += intval($this->creatureInfo[PropertyKey::MORALE] * $this->getLevel() * 3);
	    
	    // 战力新增影响因素：新武将战斗力=原武将战斗力+int(（最终伤害+最终免伤）/2)
	    $fightForce += intval(($this->creatureInfo[PropertyKey::ABSOLUTE_ATTACK] + $this->creatureInfo[PropertyKey::ABSOLUTE_DEFEND]) / 2);
	    
	    if($fightForce < HeroConf::MIN_FIGHT_FORCE)
	    {
	        $fightForce = HeroConf::MIN_FIGHT_FORCE;
	    }
		return $fightForce;
	}
	
	
	
    protected function initAwakeAbility()
	{
	    $lv = $this->getLevel();
	    $evolveLv = $this->getEvolveLv();
	    $talentInitConf   = $this->getConf(CreatureAttr::AWAKE_ABILITY_INIT )->toArray();
	    $talentGrowConf = $this->getConf(CreatureAttr::AWAKE_ABILITY_GROW );
	    $talentSkills = array ();
	    foreach ( $talentGrowConf as $conf )
	    {
	        //郑琛更改了觉醒能力成长的配置
	        if (count($conf) < 3)
	        {
	            Logger::warning( 'error config %s.',$conf);
	            continue ;
	        }
	        switch ($conf[0])
	        {
	            case AWAKEABILITY_GROW_TYPE:: GROW_BY_LEVEL:
	                if ( $lv >= $conf[1] )
	                {
	                    $talentSkills[] =$conf[2];
	                }
	                break ;
	            case AWAKEABILITY_GROW_TYPE:: GROW_BY_EVOLVELV:
	                if ( $evolveLv >= $conf[1] )
	                {
	                    $talentSkills[] =$conf[2];
	                }
	                break ;
	        }
	    }
	    Logger:: trace( 'awake ability.htid %s level:%d, init:%s, grow:%s',$this->id, $lv, $talentInitConf, $talentSkills);
	    //觉醒能力的附加优先级:成长觉醒能力>初始觉醒能力
	    //初始觉醒能力ID组和成长觉醒能力ID组按照配置的优先顺序进行附加
	    $this-> arrAwakeAbility = array_merge($talentInitConf , $talentSkills);
	}
	
	/**
	 * 为阵上武将加成的觉醒能力配置
	 * 
	 * 每一条配置是类似下面的内容：
	 * 位置|国家|性别|属性id|属性数值，位置|国家|性别|属性id|属性数值，
	 * 位置：0,1,2(0:都生效，1：在阵上生效，2：在助战军生效)
     * 国家：0,1,2,3,4(0:为阵上所有国家提供加成，1-4是只为阵上对应国家)
     * 性别：0,1,2(0:为阵上所有性别提供加上，1-2为男性女性)
	 * 
	 * @return array
	 */
	public function getAddAttrConfByAwakeAbilityForFmt()
	{
		$this->initAwakeAbility();
		
		$arrAddAttrConf = array();
		foreach ($this->arrAwakeAbility as $id)
		{
			if (empty($id))
			{
				continue;
			}
			
			$conf = HeroUtil::getAwakeAbilityConf($id);
			$arrAddAttrConf = array_merge($arrAddAttrConf, $conf['arrAddAttrForFmt']);
		}
		return $arrAddAttrConf;
	}
	
	public function getAddAttrByAwakeAbility()
	{
	    $this->initAwakeAbility();
	    $arrAddAttr = array();
	    foreach( $this->arrAwakeAbility  as $id )
	    {
	        if(empty($id))
	        {
	            continue;
	        }
	        $conf = HeroUtil::getAwakeAbilityConf($id);
	
	        foreach( $conf['arrAttrId'] as $index => $attrId)
	        {
	            if(!isset($conf['arrAttrValue'][$index]))
	            {
	                throw new ConfigException('config error.awake ability id %d',$id);
	            }
	            if( isset( $arrAddAttr[$attrId] ) )
	            {
	                $arrAddAttr[$attrId] += $conf['arrAttrValue'][$index];
	            }
	            else
	            {
	                $arrAddAttr[$attrId] = $conf['arrAttrValue'][$index];
	            }
	        }
	    }
	    $arrAddAttr = HeroUtil::adaptAttr($arrAddAttr);
	    return $arrAddAttr;
	}
	
	public function getAddAttrByTalent()
	{
	    return array();
	}
	
	/**
	 * 添加觉醒技能到武将身上
	 */
	public function addAwakeAbilitySkillBuff()
	{
	    //觉醒能力增加技能
	    foreach ($this->arrAwakeAbility as $id)
	    {
	        if(empty($id))
	        {
	            continue;
	        }
	        $skillBuffs = HeroUtil::getAwakeAbilitySkillBuff($id);
	        Logger::trace('addAwakeAbilitySkillBuff %d addskillbuff %s ',$id,$skillBuffs);
	        $this->addSkillBuff($skillBuffs);
	    }
	}
	/**
	 * 添加技能书技能到武将身上
	 */
	protected function addBookSkillBuff()
	{
	    //技能书增加技能
	    $bookSkill	=	$this->getSkillBook();
	    foreach($bookSkill as $pos => $sbId)
	    {
	        if($sbId == ItemDef::ITEM_ID_NO_ITEM)
	        {
	            continue;
	        }
	        $skillBuffs	=	ItemManager::getInstance()->getItem($sbId)->getSkillBuff();
	        $this->addSkillBuff($skillBuffs);
	    }
	}
	public function addSkillBuff($skillBuffs)
	{
	    if(count($skillBuffs) > 3)
	    {
	        throw new InterException('skillbuff type is more than 3.the types contain replace,add,attach.');
	    }
	    $replace	=	array();
	    $attach		=	array();
	    $add		=	array();
	    Logger::trace('addSkillBuff %s.',$skillBuffs);
	    if(isset($skillBuffs['replace']))
	    {
	        $replace	=	$skillBuffs['replace'];
	    }
	    if(isset($skillBuffs['attach']))
	    {
	        $attach	=	$skillBuffs['attach'];
	    }
	    if(isset($skillBuffs['add']))
	    {
	        $add	=	$skillBuffs['add'];
	    }
	    foreach($replace as $skill => $skillId)
	    {
	        if(empty($skillId))
	        {
	            continue;
	        }
	        $this->creatureInfo[$skill]	=	$skillId;
	    }
	    foreach($attach as $skill => $skillAttr)
	    {
	        if(!isset($this->creatureInfo[$skill]))
	        {
	            $this->creatureInfo[$skill] = array();
	        }
	        $this->creatureInfo[$skill]	=	array_merge($this->creatureInfo[$skill],$skillAttr);
	    }
	    foreach($add as $buffName => $buffs)
	    {
	        foreach($buffs as $buff)
	        {
	            if(in_array($buff, $this->creatureInfo[$buffName]) == FALSE)
	            {
	                $this->creatureInfo[$buffName][] = $buff;
	            }
	        }
	    }
	}
	
	/**
	 * getBattleInfo中会调用，如果需要在生成战斗数据中替换一些属性（在计算血量，战斗力，三围之前）
	 * 子类可以重写这个函数，一般是用在"造假"的武将战斗数据中
	 */
	public function replaceInitAttr()
	{
		return ;
	}
	
	/**
	 * 计算连携属性加成时，需要调用
	 * @return multitype:
	 */
	public function getAllEquipId()
	{
	    return array();
	}
	public function getAllArmingId()
	{
	    return array();
	}
	protected function getEquipInfo()
	{
	    return array();
	}
	protected function getSkillBook()
	{
	    return array();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */