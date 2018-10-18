<?php
/**
 * Created by PhpStorm.
 * User: hanshijie
 * Date: 15/9/24
 * Time: 16:32
 */
class SplitGodWeapon extends BaseScript
{

    public function getItemInfo($uid)
    {

        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $bag = BagManager::getInstance()->getBag($uid);

        $itemType = ItemDef::ITEM_TYPE_GODWEAPON;

        $arrItemIdInBag = $bag->getItemIdsByItemType($itemType);

        $arrItemIdInFormation = array();

        $arrHid = EnFormation::getArrHidInFormation($uid);
        foreach ($arrHid as $hid)
        {
            $heroObj = $userObj->getHeroManager()->getHeroObj($hid);
            $arrItemIdInFormation = array_merge($arrItemIdInFormation, $heroObj->getAllEquipId() );
        }

        $arrItemByType = array();
        $allItemId = array_merge($arrItemIdInFormation, $arrItemIdInBag);
        ItemManager::getInstance()->getItems( $allItemId  );

        $msg = sprintf("uid:%d, uname:%s:\n", $uid, $uname);
        foreach( $allItemId as $itemId )
        {
            if($itemId == 0)
            {
                continue;
            }
            $itemObj = ItemManager::getInstance()->getItem($itemId);
            if( empty($itemObj) )
            {
                Logger::fatal('cant find itemId:%d', $itemId);
                return;
            }
            if( $itemObj->getItemType() == $itemType )
            {
                $arrItemByType[] = $itemId;

                if( in_array( $itemId, $arrItemIdInBag ) )
                {
                    $msg .= sprintf("\t[in  bag]");
                }
                else if( in_array( $itemId, $arrItemIdInFormation ) )
                {
                    $msg .= sprintf("\t[in hero]");
                }
                else
                {
                    Logger::fatal('cant be true');
                    return;
                }
                $msg .= sprintf("itemId:%d, tplId:%d",
                    $itemId, $itemObj->getItemTemplateID() );

                $msg .= sprintf(" totalExp:%d, reinForceLevel:%d, evolveNum:%d, num:%d \n",
                    $itemObj->getTotalExp(), $itemObj->getReinForcelevel(), $itemObj->getEvolveNum(), $itemObj->getItemNum() );
            }
        }
        printf("%s\n", $msg);
        Logger::info("%s", $msg);


    }

    protected function executeScript($arrOption)
    {
        if ( count($arrOption) < 1 )
        {
            printf("param: uid [itemId拆分物品id num拆分成的封魂神兵石的数量 ]\n");
            return;
        }

        $uid = intval( $arrOption[0] );

        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);

        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $bag = BagManager::getInstance()->getBag($uid);

        if ( count( $arrOption ) < 3 )
        {
            $this->getItemInfo($uid);
            return;
        }
        Util::kickOffUser($uid);

        $srcItemId = intval( $arrOption[1] );
        $num = intval( $arrOption[2] );

        if( $num < 2 || $num > 6)
        {
            printf("invalid num:%d\n", $num);
            return;
        }
        $newItemNum = $num - 1;

        $srcItemObj = ItemManager::getInstance()->getItem($srcItemId);

        //经验神兵石 itemTplId=600001
        $itemTplId = $srcItemObj->getItemTemplateID();
        if($itemTplId != GodWeaponDef::REBORN_RETURN_ITEM_ID)
        {
            printf("error itemTplId only 600001 can be split");
        }

        $totalExp = $srcItemObj->getTotalExp();
        $aimItemTplId = GodWeaponDef::REBORN_RETURN_ITEM_ID; //经验石头完整神兵石
        $aimItemConfExp = ItemAttr::getItemAttr($aimItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP);

        if ( $aimItemConfExp * $newItemNum > $totalExp )
        {
            printf("num to big. num:%d, totalExp:%d, aimItemConfExp:%d\n", $num, $totalExp, $aimItemConfExp);
            return;
        }
        $eachExp = intval( ( $totalExp - $aimItemConfExp * $newItemNum) / $num );

        $leftExp = intval($totalExp - $eachExp * $newItemNum);

        $msg = sprintf("set item:%d leftExp:%d, orgExp:%d, eachExp:%d, newItemNum:%d\n",
            $srcItemId, $leftExp, $totalExp, $eachExp, $newItemNum);
        $srcItemObj->addReinForceExp(-$eachExp * $newItemNum);

        printf("aim array aimItemTplId:%d newItemNum:%d \n", $aimItemTplId, $newItemNum);
        $arrNewItemId = ItemManager::getInstance()->addItems(array($aimItemTplId => $newItemNum));
        if(empty($arrNewItemId))
        {
            printf("add arrItem failed \n");
        }
        $bag->addItems($arrNewItemId, true);
        foreach($arrNewItemId as $id)
        {
            $eachItemObj = ItemManager::getInstance()->getItem($id);
            $eachItemObj->addReinForceExp($eachExp);
            $msg .= sprintf("newItemId:%d exp:%d\n", $id, $eachExp);
        }


        printf("%s\n", $msg);
        $ret = trim(fgets(STDIN));
        if( $ret != 'y' )
        {
            printf("ignore\n");
            return;
        }

        Logger::info('%s', $msg);

        $bag->update();

    }
}