<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnDressRoom.class.php 247248 2016-06-20 14:08:57Z BaoguoMeng $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dressroom/EnDressRoom.class.php $$
 * @author $$Author: BaoguoMeng $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-06-20 14:08:57 +0000 (Mon, 20 Jun 2016) $$
 * @version $$Revision: 247248 $$
 * @brief 
 *  
 **/
class EnDressRoom
{
    /**
     * 获得新时装是调用，更新时装屋中时装的属性为 已获得过
     * @param $itemTmpId 时装的模板id
     */
    public static function getNewDress($itemTmpId)
    {
        if (DressRoomUtil::isItemTmpIdAviableDress($itemTmpId) == false)
        {
            Logger::debug('the dress:%d is not available @cehua', $itemTmpId);
            return;
        }
        $dressRoomManager = DressRoomManager::getInstance();
        $dressRoomManager->updGetStatusOfDress($itemTmpId);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        $dressRoomManager->update();
    }

    /**
     * 时装屋的额外属性加成
     * @param $uid
     * @return array
     */
    public static function getAddAttrByDress($uid)
    {
        $dressRoomManager = DressRoomManager::getInstance($uid);
        //已激活的属性
        $arrActiveYesDress = $dressRoomManager->getArrActiveYesDress();
        $arrAddAttr = array();

        foreach ($arrActiveYesDress as $itemTmpId)
        {
            $extraAttrs = DressItem::getExtraAttrs($itemTmpId);
            foreach ($extraAttrs as $k => $v)
            {
                if (isset($arrAddAttr[$k]))
                {
                    $arrAddAttr[$k] += $v;
                }
                else
                {
                    $arrAddAttr[$k] = $v;
                }
            }
        }

        /**
         * 套装属性加成
         */
        $suitDressConf = btstore_get()->SUIT_DRESS->toArray();
        foreach($suitDressConf as $id => $idConf)
        {
            $arrDress = $dressRoomManager->getArrDress();
            $flag = true; //套装是否集齐
            foreach($idConf[SuitDressCsvDef::SUIT_ITEMS] as $item)
            {
                if(!in_array($item, array_keys($arrDress)))
                {
                    $flag = false;
                    break;
                }
            }
            if($flag == true)
            {
                $arrSuitAttr = $idConf[SuitDressCsvDef::SUIT_ATTR];
                foreach($arrSuitAttr as $eachAttr)
                {
                    if (isset($arrAddAttr[$eachAttr[0]]))
                    {
                        $arrAddAttr[$eachAttr[0]] += $eachAttr[1];
                    }
                    else
                    {
                        $arrAddAttr[$eachAttr[0]] = $eachAttr[1];
                    }
                }

            }
        }
        Logger::debug('before dress lv attr:%s',$arrAddAttr);
        //时装强化到一定等级加属性
        $masterHeroObj = EnUser::getUserObj( $uid )->getHeroManager()->getMasterHeroObj();
        $dressIdArr=$masterHeroObj->getEquipByType(HeroDef::EQUIP_DRESS);
        foreach ($dressIdArr as $pos=>$dressId)
        {
        	if ($dressId==0)
        	{
        		break;
        	}
        	$dressItem=ItemManager::getInstance()->getItem($dressId);
        	$dressLv=$dressItem->getLevel();
        	$dressItemTmpId=$dressItem->getItemTemplateID();
        	$dressExtraAttr=btstore_get()->ITEMS[$dressItemTmpId][ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA_ATTR ]->toArray();
        	Logger::trace('cur dress tpl id:%d, lv:%d, dress extra attr:%s', $dressItemTmpId, $dressLv, $dressExtraAttr);
        	foreach ($dressExtraAttr as $lvExtraAttr)
        	{
        		if ($dressLv>=$lvExtraAttr[0])
        		{
        			if (isset($arrAddAttr[$lvExtraAttr[1]]))
        			{
        				$arrAddAttr[$lvExtraAttr[1]] +=$lvExtraAttr[2] ;
        			}
        			else
        			{
        				$arrAddAttr[$lvExtraAttr[1]] = $lvExtraAttr[2] ;
        			}
        		}
        		else
        		{
        			break;
        		}
        	}
        }
        Logger::debug('after dress lv attr:%s',$arrAddAttr);

        $arrRet = HeroUtil::adaptAttr($arrAddAttr);
        Logger::trace('getAddAttrByDress. uid:%d, arr:%s', $uid, $arrRet);
        return $arrRet;
    }

    public static function setCurDress($uid, $itemTplId)
    {
        $dressRoomManager = DressRoomManager::getInstance($uid);
        $dressRoomManager->updCurDress($itemTplId);
        $dressRoomManager->update();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */