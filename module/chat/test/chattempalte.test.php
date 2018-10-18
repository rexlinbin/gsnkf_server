<?php
if (! defined ( 'ROOT' ))
{
    define ( 'ROOT', dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) );
    define ( 'LIB_ROOT', ROOT . '/lib' );
    define ( 'EXLIB_ROOT', ROOT . '/exlib' );
    define ( 'DEF_ROOT', ROOT . '/def' );
    define ( 'CONF_ROOT', ROOT . '/conf' );
    define ( 'LOG_ROOT', ROOT . '/log' );
    define ( 'MOD_ROOT', ROOT . '/module' );
    define ( 'HOOK_ROOT', ROOT . '/hook' );
}


require_once (DEF_ROOT . '/Define.def.php');

if (file_exists ( DEF_ROOT . '/Classes.def.php' ))
{
    require_once (DEF_ROOT . '/Classes.def.php');

    function __autoload($className)
    {

        $className = strtolower ( $className );
        if (isset ( ClassDef::$ARR_CLASS [$className] ))
        {
            require_once (ROOT . '/' . ClassDef::$ARR_CLASS [$className]);
        }
        else
        {
            trigger_error ( "class $className not found", E_USER_ERROR );
        }
    }
}

$uid = 10001;
$user = array (
    'uid' => 10001,
    'uname' => 'qwerty',
    'utid' => 1,
);

$guildId = 1;
$guildInfo = array(
	'guild_id'=>1,
	'guild_name'=>'111',
	);

$guildPost = 'lalalallaalaa';
$bossId = 15;
$groupId = 1;
$itemId = 29456;
$htid = 11001;
$type = 0;
$taskId = 1;
$reward = array('belly'=>1, 'prestige'=>1, 'experience' => 1, 'gold'=>1, 'items'=>array(array('item_template_id' => 10001, 'item_id'=>29456, 'item_num'=>1)));


//chatTemplate::sendArenaEnd();
//chatTemplate::sendArenaAward($user, $user, $user);
//chatTemplate::sendArenaLuckyAward();
//chatTemplate::sendArenaStart();
//chatTemplate::sendArenaTopChange($user, $user);
//chatTemplate::sendArenaConsecutiveEnd($user, $user, 10);
//chatTemplate::sendArenaConsecutive($user, $type);
//chatTemplate::sendArenaLevelup($user, $type);
//chatTemplate::sendTaskEnd($user, $taskId);
//chatTemplate::sendAchievementEnd($user, 100100);
//chatTemplate::sendTitleGet($user, 101);
//chatTemplate::sendTreasureMap($uid, $user, 1000);
//chatTemplate::sendCopyEnd($user, 1);
//chatTemplate::sendGuildApply($user, $guildId);
//chatTemplate::sendGuildApplyAccept($user, $guildId);
//chatTemplate::sendGuildApplyAcceptMe($uid, $guildInfo);
//chatTemplate::sendGuildApplyRejectMe($uid, $guildInfo);
//chatTemplate::sendGuildExit($user, $guildId);
//chatTemplate::sendGuildPresidentTransfer($user, $user, $guildId);
//chatTemplate::sendGuildPresidentTransferMe($user, $guildInfo, $uid);
//chatTemplate::sendGuildBanquetBeingStart(10, $guildId);
//chatTemplate::sendGuildBanquetStart($guildId);
//chatTemplate::sendGuildBanquetBeingEnd(10, $guildId);
//chatTemplate::sendGuildBanquetEnd($guildId);
//chatTemplate::sendGuildMeFirstLogin($uid, $guildPost);
//chatTemplate::sendGuildKickout($user, $guildId);
//chatTemplate::sendGuildKickoutMe($user, $user);
//chatTemplate::sendGuildMeToVicePresident($uid, $guildInfo);
//chatTemplate::sendGuildVicePresident($user, $guildInfo);
//chatTemplate::sendBossBeingStart($bossId);
//chatTemplate::sendBossStart($bossId);
//chatTemplate::sendBossKill($user, $bossId, $reward);
//chatTemplate::sendBossAttackHPFirst($user, $bossId);
//chatTemplate::sendBossAttackHPSecond($user, $bossId);
//chatTemplate::sendBossAttackHPThird($user, $bossId);
//ChatTemplate::sendBossAttackHP(15, $user, "10.12%", $reward, $user, "9.12%", $reward, $user, "1.12%", $reward);
//chatTemplate::sendItemRedQuality($user, $groupId, $itemId);
//chatTemplate::sendItemPurpleQuality($user, $itemId);
//chatTemplate::sendTalkHero($user, $htid);


//$luckers = array(
//			0 => array (
//				'uid' => 74302,
//				'utid' => 2,
//				'uname' => '54m55Lym5pav6LWb5ouJ',
//				),
//			1 => array ( 
//				'uid' => 74313,
//				'utid' => 2,
//				'uname' => '5Y2h5bqT6Zyy5aic',
//		   		)
//			);
//chatTemplate::sendChanlledgeLuckyPrize($luckers);
//$superLuckers = array (
//				'uid' => 74302,
//				'utid' => 2,
//				'uname' => '54m55Lym5pav6LWb5ouJ',
//				);
//chatTemplate::sendChanlledgeSuperLuckyPrize($superLuckers);

$vipInfo = array (
				'uid' => 49806,
				'utid' => 2,
				'uname' => '54m55Lym5pav6LWb5ouJ',
				);
$vipLevel = 10;
chatTemplate::sendSysVipLevelUp1($vipInfo, $vipLevel);
chatTemplate::sendBroadcastVipLevelUp2($vipInfo, $vipLevel);
chatTemplate::sendWorldCharity1($vipInfo);
chatTemplate::sendWorldCharity2($vipInfo);
chatTemplate::sendWorldCharity3($vipInfo);
chatTemplate::sendWorldCharity4($vipInfo);
chatTemplate::sendWorldCharity5($vipInfo);
chatTemplate::sendWorldCharity6($vipInfo);
chatTemplate::sendWorldCharity7($vipInfo);
chatTemplate::sendWorldCharity8($vipInfo);
echo "end";
