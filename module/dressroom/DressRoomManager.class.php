<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DressRoomManager.class.php 143256 2014-11-29 10:05:37Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/DressRoomManager.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-11-29 10:05:37 +0000 (Sat, 29 Nov 2014) $$
 * @version $$Revision: 143256 $$
 * @brief 
 *  
 **/

/**
 * $data
 * [
 *   只要获得过的时装，都可以激活
 *  'arr_dress' => [
 *      $itemTmpId => ['as' => 0(激活状态)]
 *  ]
 *  'cur_dress' => $itemTmpId 0表示当前没有时装
 * ]
 */
class DressRoomManager
{
    private static $arrInstance = array();
    private $uid;
    private $data;
    private $dataModify;

    private function __construct($uid)
    {
        $this->uid = $uid;
        $this->init();
    }

    private function init()
    {
        $data = DressRoomDao::loadAll($this->uid);
        if (empty($data))
        {
            $data = $this->compare();
            DressRoomDao::insertData($this->uid, $data);
        }
        $this->data = $this->dataModify = $data;
    }

    /**
     * 刷新玩家数据，防止新获得时装时候出错，造成玩家时装没活的的情况
     */
    public function refresh()
    {
        //只在自己的线程， 才compare
        if(RPCContext::getInstance()->getUid() != $this->uid)
        {
            return;
        }

        $data = $this->compare();
        if (empty($data))
        {
            return;
        }
        foreach ($data[TblDressRoomDef::ARRDRESS] as $itemTmpId => $info)
        {
            if (!isset($this->data[TblDressRoomDef::ARRDRESS][$itemTmpId]))
            {
                $this->data[TblDressRoomDef::ARRDRESS][$itemTmpId] = array(
                    TblDressRoomDef::ACTIVESTATUS => DressRoomDef::ACTIVESTATUSNO,
                );
            }
        }
    }

    //比较背包数据+武将数据和时装屋数据
    private function compare()
    {
        //读配置 可获得的时装屋时装
        $arrAviableDress = DressRoomUtil::getAviableDressFromConf();
        
        $arrItemTmpIdFromBag = array();
        if ( RPCContext::getInstance()->getUid() == $this->uid )
        {
        	$bag = BagManager::getInstance()->getBag($this->uid);
        	$arrItemTmpIdFromBag = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_DRESS);
        }

        $userObj = EnUser::getUserObj($this->uid);
        $masterHid = $userObj->getMasterHid();
        //装备在session里有缓存， 已优化, 只穿masterHid
        $arrItemTmpIdOnHero = HeroLogic::getAllEquipTmplIdOnHero($this->uid, HeroDef::EQUIP_DRESS, $masterHid);

        $arrItemTmpIdOfUser = array_unique(array_merge($arrItemTmpIdFromBag, $arrItemTmpIdOnHero));
        //玩家已收集的时装屋时装
        $arrDress = array();
        foreach ($arrItemTmpIdOfUser as $itemTmpId)
        {
            if (in_array($itemTmpId, $arrAviableDress))
            {
                $arrDress[$itemTmpId] = array(
                    TblDressRoomDef::ACTIVESTATUS => DressRoomDef::ACTIVESTATUSNO,
                );
            }
        }

        //外部刷新用
        if (isset($this->data[TblDressRoomDef::ARRDRESS])
            && $arrDress == $this->data[TblDressRoomDef::ARRDRESS])
        {
            Logger::trace('arrDress not change');
            return array();
        }

        Logger::info('uid:%d arrDress of User:%s, dress From Bag:%s, dress From Hero:%s, arrItemTmpIdOfUser:%s',
            $this->uid, $arrDress, $arrItemTmpIdFromBag, $arrItemTmpIdOnHero, $arrItemTmpIdOfUser);
        $curDress = isset($arrItemTmpIdOnHero[0]) ? $arrItemTmpIdOnHero[0] : 0; //0表示当前没有时装
        $data = array(
            TblDressRoomDef::ARRDRESS => $arrDress,
            TblDressRoomDef::CURDRESS => $curDress,
        );

        return $data;
    }

    public static function getInstance($uid=NULL)
    {
        if (empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        if (empty(self::$arrInstance[$uid]))
        {
            $Instance = new self($uid);
            self::$arrInstance[$uid] = $Instance;
            return $Instance;
        }
        return self::$arrInstance[$uid];
    }

    public static function release($uid)
    {
        self::$arrInstance[$uid] = NULL;
    }

    public function update()
    {
        if ($this->uid != RPCContext::getInstance()->getUid())
        {
            throw new InterException('do not support update dress room of other player .');
        }
        if ($this->data != $this->dataModify)
        {
            DressRoomDao::updateData($this->uid, $this->data);
        }
        $this->dataModify = $this->data;
    }

    public function updCurDress($itemTmpId)
    {
        $arrDress = $this->getArrDress();

        if (!isset($arrDress[$itemTmpId]))
        {
            throw new FakeException('you have not get the dress:%d', $itemTmpId);
        }

        $this->data[TblDressRoomDef::CURDRESS] = $itemTmpId;
    }

    public function getCurDress()
    {
        return $this->data[TblDressRoomDef::CURDRESS];
    }

    public function updActiveStatusOfDress($itemTmpId, $status=DressRoomDef::ACTIVESTATUSYES)
    {
        $arrDress = $this->getArrDress();

        //不曾得到 不能激活
        if (!isset($arrDress[$itemTmpId]))
        {
            throw new FakeException('you have not get the dress:%d', $itemTmpId);
        }
        if (isset($arrDress[$itemTmpId][TblDressRoomDef::ACTIVESTATUS])
            && $arrDress[$itemTmpId][TblDressRoomDef::ACTIVESTATUS] == DressRoomDef::ACTIVESTATUSYES)
        {
            throw new FakeException('the dress:%d has been active already', $itemTmpId);
        }

        $this->data[TblDressRoomDef::ARRDRESS][$itemTmpId][TblDressRoomDef::ACTIVESTATUS] = $status;
    }

    public function getArrDress()
    {
        return $this->data[TblDressRoomDef::ARRDRESS];
    }

    public function updGetStatusOfDress($itemTmpId)
    {
        $arrDress = $this->getArrDress();

        if (isset($arrDress[$itemTmpId]))
        {
            Logger::info('dress:%d has already been got', $itemTmpId);
            return;
        }

        $this->data[TblDressRoomDef::ARRDRESS][$itemTmpId][TblDressRoomDef::ACTIVESTATUS] = DressRoomDef::ACTIVESTATUSNO;
    }

    public function getArrActiveYesDress()
    {
        $arrDress = $this->getArrDress();

        //激活状态的时装
        $arrActiveYesDress = array();
        foreach($arrDress as $itemTmpId => $arrStatus)
        {
            if (isset($arrDress[$itemTmpId][TblDressRoomDef::ACTIVESTATUS])
                && $arrDress[$itemTmpId][TblDressRoomDef::ACTIVESTATUS] == DressRoomDef::ACTIVESTATUSYES)
            {
                $arrActiveYesDress[] = $itemTmpId;
            }
        }
        return $arrActiveYesDress;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */