<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendOutVipGift.php 200370 2015-09-24 08:32:35Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendOutVipGift.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-09-24 08:32:35 +0000 (Thu, 24 Sep 2015) $
 * @version $Revision: 200370 $
 * @brief 
 *  
 **/
class SendOutVipGift extends BaseScript
{
    private static $REWARDTYPE = RewardSource::SYSTEM_GENERAL;
    private static $REWARDTIME = "20150925 00:00:00";
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $arrRewardForVip = array(
                //体力丹 10032 进化石 60019 神兵洗练石60025 五星武将选择包20022 高级丹药宝盒30107
                //體力丹*5、
                1 => array(RewardType::ARR_ITEM_TPL => array(10032=>5)),
                //體力丹*10、進化石*50
                2 => array(RewardType::ARR_ITEM_TPL => array(10032=>10,60019=>50)),
                //體力丹*10、進化石*50、神兵洗煉石*10
                3 => array(RewardType::ARR_ITEM_TPL => array(10032=>10,60019=>50,60025=>10)),
                //體力丹*10、進化石*100、神兵洗煉石*20、五星武將選擇包*1（id20022）
                4 => array(RewardType::ARR_ITEM_TPL => array(10032=>10,60019=>100,60025=>20,20022=>1)),
                //體力丹*20、進化石*100、神兵洗煉石*30、五星武將選擇包*1（id20022）、高級丹藥寶盒*1
                5 => array(RewardType::ARR_ITEM_TPL => array(10032=>20,60019=>100,60025=>30,20022=>1,30107=>1)),
                //體力丹*20、進化石*100、神兵洗煉石*30、五星武將選擇包*1（id20022）、高級丹藥寶盒*2
                6 => array(RewardType::ARR_ITEM_TPL => array(10032=>20,60019=>100,60025=>30,20022=>1,30107=>2)),
                );
        $vipToReward = array(
                0=>1,
                1=>2,
                2=>2,
                3=>2,
                4=>2,
                5=>2,
                6=>3,
                7=>3,
                8=>3,
                9=>3,
                10=>4,
                11=>4,
                12=>4,
                13=>5,
                14=>6
                );
        $this->sendVipGift($arrRewardForVip, $vipToReward, $arrOption);
    }
    
    
    public function sendVipGift($arrRewardForVip,$vipToReward,$arrOption)
    {
        $offset = 0;
        $limit = CData::MAX_FETCH_SIZE;
        $data = new CData();
        while(TRUE)
        {
            $ret = $data->select(array('uid','vip'))
                        ->from('t_user')
                        ->where(array('uid','>',0))
                        ->orderBy('uid', TRUE)
                        ->limit($offset, $limit)
                        ->query();
            $arrUser = Util::arrayIndex($ret, 'uid');
            $arrUid = array_keys($arrUser);
            if(empty($arrUid))
            {
                break;
            }
            $arrUserReward = self::getArrUserReward($arrUid);
            foreach($ret as $userInfo)
            {
                $uid = intval($userInfo['uid']);
                $vip = intval($userInfo['vip']);
                if(isset($arrUserReward[$uid]))
                {
                    Logger::info('uid %d has receive reward',$uid);
                    continue;
                }
                if(!isset($vipToReward[$vip]))
                {
                    Logger::fatal('uid %d vip %d no corresponding rewardid.',$uid,$vip);
                    continue;
                }
                $rewardId = $vipToReward[$vip];
                $rewardInfo = $arrRewardForVip[$rewardId];
                Logger::trace('uid %d vip %d rewardinfo %s',$uid,$vip,serialize($rewardInfo));
                if(isset($arrOption[0]) && ($arrOption[0] == 'send'))
                {
                    Logger::info('uid %d vip %d sendreward %s',$uid,$vip,$rewardInfo);
                    EnReward::sendReward($uid, self::$REWARDTYPE, $rewardInfo);
                }
            }
            if(count($ret) < $limit)
            {
                break;
            }
            $offset += $limit;
            Logger::info('process offset %d',$offset);
        }
    }

    public function getArrUserReward($arrUid)
    {
        $data = new CData();
        $ret = $data->select ( array(RewardDef::SQL_RID,RewardDef::SQL_UID) )->from ( RewardDef::SQL_TABLE )
                ->where( RewardDef::SQL_UID , 'IN', $arrUid)
                ->where( RewardDef::SQL_SEND_TIME, '>', strtotime(self::$REWARDTIME))
                ->where( RewardDef::SQL_SOURCE , '=', self::$REWARDTYPE )
                ->query();
        $arrUserReward = Util::arrayIndex($ret, RewardDef::SQL_UID);
        return $arrUserReward;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */