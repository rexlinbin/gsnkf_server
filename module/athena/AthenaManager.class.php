<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: AthenaManager.class.php 237716 2016-04-12 07:02:50Z DuoLi $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/AthenaManager.class.php $$
 * @author $$Author: DuoLi $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-04-12 07:02:50 +0000 (Tue, 12 Apr 2016) $$
 * @version $$Revision: 237716 $$
 * @brief 
 *  
 **/

/**
 * Class AthenaManager
 * va_data => array[
 *  detail => array[ index 页 =>
 *      {
 *          attrId属性id => level等级, ...
 *      }
 *  ]
 *  special => array[
 *      normal => array{attrId特殊属性id, ...},
 *      rage => array{attrId特殊属性id, ...}
 *  ]
 *  treeNum => num(开启页数)
 *  buyNum => array[
 *      itemTplId => num, ...
 *  ]
 * ]
 */
class AthenaManager
{
    private $uid;
    private $athena;
    private $athenaModify;
    private static $arrInstance;

    private function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $this->uid = $uid;
        self::loadData();
        self::rfrBuyNum();
    }

    private function loadData()
    {
        $data = AthenaDao::loadData($this->uid);
        if(empty($data))
        {
            $data = self::init();
            if($this->uid == RPCContext::getInstance()->getUid())
            {
                AthenaDao::update($data);
            }
        }
        $this->athena = $this->athenaModify = $data;
    }

    private function init()
    {
        return array(
            AthenaSql::UID => $this->uid,
            AthenaSql::VA_DATA => array(
                AthenaSql::DETAIL => array(),
                AthenaSql::SPECIAL => array(),
                AthenaSql::TREE_NUM => AthenaDef::INIT_TREE_INDEX,
                AthenaSql::BUY_NUM => array(),
                AthenaSql::BUY_TIME => 0,
            ),
        );
    }

    private function rfrBuyNum()
    {
        $buyTime = self::getBuyTime();
        if(Util::isSameDay($buyTime))
        {
            return;
        }
        $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_NUM] = array();
    }

    public static function getInstance($uid)
    {
        if(empty(self::$arrInstance[$uid]))
        {
            $Instance = new self($uid);
            self::$arrInstance[$uid] = $Instance;
            return $Instance;
        }
        return self::$arrInstance[$uid];
    }

    public function update()
    {
        if($this->uid != RPCContext::getInstance()->getUid())
        {
            throw new InterException('do not support update athena from other user:%d thread:%d',
                $this->uid, RPCContext::getInstance()->getUid());
        }
        if($this->athena != $this->athenaModify)
        {
            AthenaDao::update($this->athena);
        }
        $this->athenaModify = $this->athena;
    }

    public function attrLvUp($index, $attrId)
    {
        $level = $this->getAttrLv($index, $attrId);
        $level += 1;
        $this->setAttrLv($index, $attrId, $level);
    }

    public function setAttrLv($index, $attrId, $level)
    {
        $this->athena[AthenaSql::VA_DATA][AthenaSql::DETAIL][$index][$attrId] = $level;
    }

    public function getAttrLv($index, $attrId)
    {
        if(empty($this->athena[AthenaSql::VA_DATA][AthenaSql::DETAIL][$index][$attrId]))
        {
            return 0;
        }
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::DETAIL][$index][$attrId];
    }

    public function getDetail()
    {
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::DETAIL];
    }

    public function addSpecialAttr($type, $attrId)
    {
        $arrSpecialAttr = $this->getArrSpecialAttr();
        if($type == AthenaDef::TYPE_NORMAL)
        {
            $arrSpecialAttr[AthenaSql::NORMAL][] = $attrId;
        }
        else if($type == AthenaDef::TYPE_RAGE)
        {
            $arrSpecialAttr[AthenaSql::RAGE][] = $attrId;
        }
        else
        {
            throw new InterException("inter");
        }

        $this->setArrSpecialAttr($arrSpecialAttr);
    }

    public function isSpecialAttrExist($type, $specialId)
    {
        $arrSpecialAttr = $this->getArrSpecialAttr();
        if($type == AthenaDef::TYPE_NORMAL)
        {
            if(empty($arrSpecialAttr[AthenaSql::NORMAL]))
            {
                return false;
            }
            $tmpArr = $arrSpecialAttr[AthenaSql::NORMAL];
        }
        else if($type == AthenaDef::TYPE_RAGE)
        {
            if(empty($arrSpecialAttr[AthenaSql::RAGE]))
            {
                return false;
            }
            $tmpArr = $arrSpecialAttr[AthenaSql::RAGE];
        }
        else
        {
            throw new InterException("inter");
        }
        if(in_array($specialId, $tmpArr))
        {
            return true;
        }
        return false;
    }

    public function setArrSpecialAttr($arrSpecial)
    {
        $this->athena[AthenaSql::VA_DATA][AthenaSql::SPECIAL] = $arrSpecial;
    }

    public function getArrSpecialAttr()
    {
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::SPECIAL];
    }

    public function addTreeNum()
    {
        $curTreeNum = $this->getTreeNum();
        $curTreeNum += 1;
        $this->setTreeNum($curTreeNum);
    }

    public function setTreeNum($num)
    {
        $this->athena[AthenaSql::VA_DATA][AthenaSql::TREE_NUM] = $num;
    }

    public function getTreeNum()
    {
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::TREE_NUM];
    }

    public function addBuyNum($itemTplId, $num)
    {
        $curNum = self::getBuyNum($itemTplId);
        $curNum += $num;
        self::setBuyNum($itemTplId, $curNum);
    }

    public function setBuyNum($itemTplId, $num)
    {
        $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_NUM][$itemTplId] = $num;
    }

    public function getBuyNum($itemTplId)
    {
        if(empty($this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_NUM][$itemTplId]))
        {
            return 0;
        }
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_NUM][$itemTplId];
    }

    public function getWholeBuyNum()
    {
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_NUM];
    }

    public function rfrBuyTime()
    {
        if(Util::isSameDay($this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_TIME]))
        {
            return;
        }
        $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_TIME] = Util::getTime();
    }

    public function getBuyTime()
    {
    	if (empty($this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_TIME])) 
    	{
    		$this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_TIME] = 0;
    	}
        return $this->athena[AthenaSql::VA_DATA][AthenaSql::BUY_TIME];
    }

    public function addTalent($talentId)
    {
        $this->athena[AthenaSql::VA_DATA][AthenaSql::ARR_TALENT][] = $talentId;
    }

    /**
     * 觉醒能力
     * @return array
     */
    public function getArrTalentInfo()
    {
    	// 这里再加上默认的觉醒
        $DefaultTalent = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_MASTER_HERO_AWAKE_ABILITY]->toArray();
    	
        if(empty($this->athena[AthenaSql::VA_DATA][AthenaSql::ARR_TALENT]))
        {
            return $DefaultTalent;
        }
        return array_merge($DefaultTalent, $this->athena[AthenaSql::VA_DATA][AthenaSql::ARR_TALENT]);
    }

    public function ifTalentExist($talentId)
    {
        $arrTalent = $this->getArrTalentInfo();
        if(empty($arrTalent))
        {
            return false;
        }

        return in_array($talentId, $arrTalent);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */