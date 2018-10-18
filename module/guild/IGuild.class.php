<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuild.class.php 230593 2016-03-02 10:21:16Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/IGuild.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:21:16 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230593 $
 * @brief 
 *  
 **/

interface IGuild
{
	/**
	 * 创建
	 * 
	 * @param string $name 		名称
	 * @param boolean $isGold	是否金币创建, 0银币, 1金币, 默认0银币
	 * @param string $slogan 	宣言
	 * @param string $post		公告
	 * @param string $passwd 	密码
	 * @return array 			结果
	 * <code>
	 * {
	 * 	'ret':string
	 * 		'ok'				成功
	 * 		'used'				名称已经使用
	 * 		'blank'				名称存在空格
	 * 		'exceed'			超过创建次数
	 * 		'harmony'			名称存在敏感词
	 * 	'info':array			成功后返回军团信息,否则为空
	 * 		{
	 * 			see getGuildInfo
	 * 		}
	 * }
	 * </code>
	 */
	function createGuild($name, $isGold = 0, $slogan = "", $post = "", $passwd = "");
	
	/**
	 * 申请
	 * 
	 * @param int $guildId 		军团id
	 * @return string $ret		申请结果
	 * 'ok' 					成功
	 */
	function applyGuild($guildId);
	
	/**
	 * 取消申请
	 * 
	 * @param int $guildId		军团id
	 * @return string $ret		处理结果
	 * 'ok' 					成功
	 */
	function cancelApply($guildId);
	
	/**
	 * 同意申请
	 * 
	 * @param int $uid			用户id
	 * @return string $ret		处理结果
	 * 'ok' 					成功	
	 * 'failed'					失败
	 * 'exceed'					人数超上限
	 * 'limited'				限制加入
	 * 'forbidden_citywar'		报名城池战
	 * 'forbidden_guildrob'		抢粮战期间
	 */
	function agreeApply($uid);
	
	/**
	 * 拒绝申请
	 * 
	 * @param int $uid			用户id
	 * @return string $ret		处理结果
	 * 'ok' 					成功
	 * 'failed' 				失败
	 */
	function refuseApply($uid);
	
	/**
	 * 拒绝所有申请
	 *
	 * @return string $ret		处理结果
	 * 'ok' 					成功
	 * 'failed' 				失败
	 */
	function refuseAllApply();
	
	/**
	 * 退出军团
	 * 
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 * 'forbidden_citywar'		报名城池战
	 * 'forbidden_guildrob'		抢粮战期间
	 * 'forbidden_guildwar'		报名跨服赛
	 */
	function quitGuild();
	
	/**
	 * 踢出成员
	 * 
	 * @param int $uid			用户id
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 * 'forbidden_citywar'		报名城池战
	 * 'forbidden_guildrob'		抢粮战期间
	 * 'forbidden_guildwar'		报名跨服赛
	 */
	function kickMember($uid);
	
	/**
	 * 修改军团徽章
	 * 
	 * @param int $iconId
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 */
	function modifyIcon($icon);
	
	/**
	 * 修改宣言
     * 
	 * @param string $slogan	宣言
	 * @return array 			更新结果
	 * <code>
	 * {
	 * 	'ret':string
	 * 		'ok'				表示成功
	 * 	'slogan':string			更新后的slogan
	 * }
	 * </code>
	 */
	function modifySlogan($slogan);
	
	/**
	 * 修改公告
	 *
	 * @param string $post		公告
	 * @return array 			更新结果
	 * <code>
	 * {
	 * 	'ret':string
	 * 		'ok'				表示成功
	 * 	'post':string			更新后的post
	 * }
	 * </code>
	 */
	function modifyPost($post);
	
	/**
	 * 修改密码
	 *
	 * @param string $oldPasswd	旧密码
	 * @param string $newPasswd	新密码
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'err_passwd'				密码错误
	 */
	function modifyPasswd($oldPasswd, $newPasswd);
	
	/**
	 * 修改军团名
	 *
	 * @param string $name		军团名
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'used'					名称已经使用
	 * 'blank'					名称存在空格
	 * 'harmony'				名称存在敏感词
	 */
	function modifyName($name);
	
	/**
	 * 任命副团长
	 * 
	 * @param int $uid			用户id
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 */
	function setVicePresident($uid);

	/**
	 * 取消副团长
	 * 
	 * @param int $uid			用户id
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 * 'forbidden_guildwar'		报名跨服赛
	 */
	function unsetVicePresident($uid); 
	
	/**
	 * 转让团长
	 * 
	 * @param int $uid			用户id
	 * @param string $passwd	密码
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 * 'err_passwd'				密码错误
	 * 'forbidden_guildwar'		报名跨服赛
	 */
	function transPresident($uid, $passwd);
	
	/**
	 * 解散军团
	 * 
	 * @param string $passwd	密码
	 * @return string $ret		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 * 'err_passwd'				密码错误
	 * 'forbidden_citywar'		报名城池战
	 * 'forbidden_guildrob'		抢粮战期间
	 * 'forbidden_guildwar'		报名跨服赛
	 */
	function dismiss($passwd);
	
	/**
	 * 弹劾团长
	 * 
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 */
	function impeach();
	
	/**
	 * 贡献
	 * 
	 * @param int $type			类型,(1,2,3,4,5),目前只有4种，最多支持5种	
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 * 'failed'					失败
	 */
	function contribute($type);
	
	/**
	 * 升级军团
	 *
	 * @param int $type			建筑类型，目前支持：1忠义堂2关公殿3商城4副本5任务6粮仓
	 * @return string $ret		升级结果
	 * 'ok'						成功
	 */
	function upgradeGuild($type);
	
	/**
	 * 领奖
	 * 
	 * @param int $type			领取类型，0免费1金币，默认为0
	 * @return array 			处理结果
	 * <code>
	 * {
	 * 	'ret':string
	 * 		'ok'				成功
	 * 		'failed'			失败
	 * 		'exceed'			人数超上限
	 * 	'level':int				领取时关公殿等级
	 * }
	 * </code>
	 */
	function reward($type = 0);
	
	/**
	 * 留言
	 *
	 * @param string $msg		留言
	 * @return string $msg		过滤后的留言
	 */
	function leaveMessage($msg);
	
	/**
	 * 摇奖
	 * 
	 * @return array 掉落东西
	 * <code>
	 * {
	 *     'item'
	 *     {
	 *         $itemTmplId => $num
	 *     }
	 *     'hero'
	 *     {
	 *         $htid => $num
	 *     }
	 *     'treasFrag'
	 *     {
	 *         $treasFragTmplId => $num
	 *     }
	 *     'silver' => $num
	 *     'soul' => $num
	 * }
	 * </code>
	 */
	function lottery();
	
	/**
	 * 采集
	 * 
	 * @param int $fieldId 		粮田id(1,2,3,4,5)	
	 * @param int $num			次数
	 * @return array 
	 * <code>
	 * {
	 * 		0 => $grainNum	军团当前粮草
	 * 		1 => $meritNum	成员当前功勋
	 * 		2 => $addGrain  军团增加粮草
	 * 		3 => 额外获得物品
	 * 		{
	 * 			'item'
	 *     		{
	 *         		$itemTmplId => $num
	 *     		}
	 * 		}
	 * }
	 * </code>
	 */
	function harvest($fieldId, $num = 1);
	
	/**
	 * 一键采集
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		0 => $grainNum	军团当前粮草
	 * 		1 => $meritNum	成员当前功勋
	 * 		2 => $sumGrain  军团增加粮草
	 * 		3 => $sum		采集次数
	 * 		4 => array
	 * 		{
	 * 			1 => array
	 * 			{
	 * 				0 => $addExp
	 * 				1 => $curLevel
	 * 			}
	 * 			2 => array
	 * 			{
	 * 				0 => $addExp
	 * 				1 => $curLevel
	 * 			}
	 * 		}
	 * 		5 => array      额外获得物品
	 * 		{
	 * 			'item'
	 *     		{
	 *         		$itemTmplId => $num
	 *     		}
	 * 		}
	 * }
	 * </code>
	 */
	function quickHarvest();
	
	/**
	 * 刷新自己粮田
	 * 
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 */
	function refreshOwn();
	
	/**
	 * 刷新全体粮田
	 * @param $type 			1是用金币 2是用建设度
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 */
	function refreshAll($type);
	
	/**
	 * 分配粮草
	 * 
	 * @return array
	 * {
	 * 		0 => $num 个人分得粮草数量
	 * 		1 => $num 军团剩余粮草数量
	 * }
	 * or
	 * 'forbidden_guildrob'		抢粮战期间
	 */
	function share();
	
	/**
	 * 购买战书
	 *
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 */
	function buyFightBook();
	
	/**
	 * 玩家互相切磋
	 *
	 * @param $atkedUid 被切磋玩家uid
	 * @return array
	 * <code>
	 * {
	 *      'errcode' 0 成功 1 进攻方的切磋次数限制 2 防守方的被切磋次数限制
	 *      'battleRes' => 战报
	 *      'uPlayWithNum' => 切磋方（进攻方）当天切磋次数
	 *      'atkedUBePlayWithNum' => 被切磋方（防守方）当天切磋次数
	 * }
	 * </code>
	 */
	function fightEachOther($atkedUid);
	
	/**
	 * 提升技能
	 *
	 * @param $id 技能id
	 * @param $type 类型，1学习2提升
	 * @return string $ret 		处理结果
	 * 'ok'						成功
	 */
	function promote($id, $type);
	
	/**
	 * 获取公会的申请列表
	 * 
	 * @param int $offset 		分页位置
	 * @param int $limit 		每页大小
	 * @return array 			申请记录
	 * <code>
	 * {
	 * 		'count':			总数
	 * 		'offset':			回传给前端
	 * 		'data':
	 * 		{
	 * 			$uid =>
	 * 			{
	 * 				'uid':			用户uid
	 * 				'utid':			用户模板id
	 * 				'uname':		用户uname
	 * 				'htid':			主角武将模板id
	 * 				'dress':		时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'level':		等级
	 * 				'vip':			vip等级
	 * 				'position':		竞技场排名
	 * 				'fight_force':	战力
	 * 				'apply_time':	申请时间
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getGuildApplyList($offset, $limit);
	
	/**
	 * 获取用户的申请记录
	 * 
	 * @return array 			个人申请记录
	 * <code>
	 * {
	 * 		guild_id:int		军团id
	 * }
	 * </code>
	 */
	function getUserApplyList();
	
	/**
	 * 获取军团列表
	 * 包含用户申请的军团
	 * 当appnum非0时,前appnum个为申请的军团
	 * 
	 * @param int $offset		分页位置
	 * @param int $limit		每页大小
	 * @return array 			查询结果
	 * <code>
	 * {
	 * 		'count':			总数
	 * 		'offset':			回传给前端
	 * 		'appnum':			申请的军团数量
	 * 		'data':
	 * 		{
	 * 			$id
	 * 			{
	 * 				'guild_id':		军团id
	 * 				'guild_name':	名称
	 * 				'guild_level':	等级
	 * 				'fight_force':	战斗力
	 * 				'leader_uid':	团长uid
	 * 				'leader_utid':	团长utid
	 * 				'leader_name':	团长名字
	 * 				'leader_htid':	团长武将模板id
	 * 				'leader_dress':	团长时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'leader_level':	团长等级
	 * 				'leader_force': 团长战斗力
	 * 				'member_num':	成员数量
	 * 				'member_limit':	成员上限
	 * 				'slogan':		宣言
	 * 				'rank':			排名
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getGuildList($offset, $limit);
	
	/**
	 * 搜索名字获取军团列表
	 *
	 * @param int $offset		分页位置
	 * @param int $limit		每页大小
	 * @param string $name		军团名称
	 * @return array 			查询结果
	 * <code>
	 * {
	 * 		'count':			总数
	 * 		'offset':			回传给前端
	 * 		'data':
	 * 		{
	 * 			$id
	 * 			{
	 * 				'guild_id':		军团id
	 * 				'guild_name':	名称
	 * 				'guild_level':	等级
	 * 				'fight_force':	战斗力
	 * 				'leader_uid':	团长uid
	 * 				'leader_utid':	团长utid
	 * 				'leader_name':	团长名字
	 * 				'leader_htid':	团长武将模板id
	 * 				'leader_dress':	团长时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'leader_level':	团长等级
	 * 				'member_num':	成员数量
	 * 				'member_limit':	成员上限
	 * 				'slogan':		宣言
	 * 				'rank':			排名
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getGuildListByName($offset, $limit, $name);
	
	/**
	 * 获取军团排行列表
	 *
	 * @return array 			查询结果
	 * <code>
	 * {
	 * 		$id
	 * 		{
	 * 			'guild_id':		军团id
	 * 			'guild_name':	名称
	 * 			'guild_level':	等级
	 * 			'fight_force':	战斗力
	 * 			'leader_name':	团长名字
	 * 			'leader_utid':	团长utid
	 * 			'rank':			排名
	 * 		}
	 * }
	 * </code>
	 */
	function getGuildRankList();
	
	/**
	 * 获取成员列表
	 * 
	 * @param int $offset		分页位置
	 * @param int $limit		每页大小
	 * @return array 
	 * <code>
	 * {
	 * 		'count':			总数
	 * 		'offset':			回传给前端
	 * 		'data':
	 * 		{
	 * 			$uid =>
	 * 			{
	 * 				'uid':			用户uid
	 * 				'utid':			用户utid
	 * 				'uname':		用户name
	 * 				'htid':			武将模板id
	 * 				'dress':		时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'level':		等级
	 * 				'vip':			vip等级
	 * 				'status':		状态,1：online, 2：offline,
	 * 				'fight_force':	战力	
	 * 				'last_logoff_time':最后一次登出时间
	 * 				'position':		竞技场排名
	 * 				'contri_type':	贡献类型
	 * 				'contri_point':	贡献值
	 * 				'contri_total':	总贡献值
	 * 				'contri_time':	贡献时间
	 * 				'member_type':	职位
     *              'playwith_num': 当天切磋次数
     *              'be_playwith_num': 当天被切磋次数
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getMemberList($offset, $limit);
	
	/**
	 * 获取贡献记录信息
	 * 默认最近的5条
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		{
	 * 			'uid':			用户uid
	 * 			'utid':			用户utid
	 * 			'uname':		用户name
	 * 			'record_type':  贡献类型
	 * 			'record_data':	贡献数量
	 * 			'record_time':	贡献时间
	 * 		}
	 * }
	 * </code>
	 */
	function getRecordList();
	
	/**
	 * 获得留言列表
	 * 
	 * @param int $offset	分页位置
	 * @param int $limit	每页大小
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'list':array
	 * 		{
	 * 			{
	 * 				'uid':			用户uid
	 * 				'utid':			用户utid
	 * 				'uname':		用户name
	 * 				'htid':			武将模板id
	 * 				'dress':		时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'level':		等级
	 * 				'time':			时间
	 * 				'type':			职务类型：0团员，1团长，2副团
	 * 				'message':		留言
	 * 			}
	 * 		}
	 * 		'num':				剩余留言次数
	 * }
	 * </code>
	 */
	function getMessageList($offset, $limit);
	
	/**
	 * 获取军团动态信息
	 * 默认最近的50条
	 * @return array
	 * <code>
	 * {
	 * 		{
	 * 			'user':
	 * 			{
	 * 				'uid':			用户uid
	 * 				'utid':			用户utid
	 * 				'uname':		用户name
	 * 				'htid':			武将模板id
	 * 				'dress':		时装信息
	 * 				{
	 *      			$posId => $dressTplId 位置id对应时装模板id
	 * 				}
	 * 				'level':		等级
	 * 			}
	 * 			'info':
	 * 			{
	 * 				'type':			信息类型
	 * 				'time':			信息时间
	 * 				'uname':		另一个用户name
	 * 				'upgrade':		升级
	 * 				{
	 * 					'type':		建筑类型，目前支持：1忠义堂，2关公殿
	 * 					'oldLevel':	原来等级
	 * 					'newLevel':	新的等级
	 * 				}
	 * 				'reward':		参拜奖励
	 * 				{
	 * 					'execution'：体力
	 * 					'stamina'：	耐力
	 * 					'prestige'：声望
	 * 					'soul'：		将魂
	 * 					'silver'：	银币
	 * 					'gold'：		金币
	 * 				}
	 * 				'contribute':
	 * 				{
	 * 					'silver':   花费银币
	 * 					'gold':		花费金币
	 * 					'exp':		增加军团建设度
	 * 					'point':	增加个人贡献值
	 * 				}
	 * 			}
	 * 		}
	 * }
	 * </code>
	 * 
	 */
	function getDynamicList();
	
	/**
	 * 获得宿敌列表
	 * 
	 * @param int $offset	分页位置
	 * @param int $limit	每页大小
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		{
	 *			'guild_id':			军团id
	 * 			'guild_name':		军团名称
	 * 			'rob_grain':		抢粮数量
	 * 			'rob_free':			可抢粮数量
	 * 			'rob_time':			抢粮时间
	 * 			'shelter_time':		保护时间
	 * 		}
	 * }
	 * </code>
	 */
	function getEnemyList($offset, $limit);
	
	/**
	 * 获得粮田的采集列表
	 *
	 * @param int $fieldId			粮田id
	 * @return array
	 * <code>
	 * {
	 * 		{
	 *			'uname':			用户名称
	 *			'time:				采集时间
	 *			'num':				采集次数
	 * 			'add_exp':			增加经验
	 * 			'add_level':		增加等级
	 * 			'add_grain':		增加粮草
	 * 			'grain_output':		粮草产量
	 * 			'merit_output':		功勋产量
	 * 			'add_extra':		增加物品
	 * 			{
	 * 				'item'
	 *     			{
	 *         			$itemTmplId => $num
	 *     			}
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getHarvestList($fieldId);
	
	/**
	 * 获取军团信息
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		'guild_id':			军团id
	 * 		'guild_name':		军团名称
	 * 		'guild_level':		军团等级
	 * 		'guild_icon':		军团徽章
	 * 		'fight_force':		军团战斗力
	 * 		'upgrade_time':		升级时间
	 * 		'create_uid':		创建者uid
	 * 		'create_time':		创建时间
	 * 		'join_num':			当天加入人数
	 * 		'join_time':		上次加入时间
	 * 		'contri_num':		当天贡献次数
	 * 		'contri_time':		上次贡献时间
	 * 		'reward_num':		当天领奖次数
	 * 		'reward_time':		上次领奖时间
	 * 		'grain_num':		粮草数量
	 * 		'attack_num':		抢粮次数
	 * 		'defend_num':       被抢次数
	 * 		'refresh_num':		刷新次数
	 *      'refresh_num_byexp': 用军团建设度刷新次数
	 * 		'fight_book':		战书数量
	 * 		'curr_exp':			当前贡献值
	 * 		'share_cd':			分粮冷却时间
	 * 		'status':			状态
	 * 		'va_info':
	 * 		{	
	 * 			0 =>			
	 * 			{	
	 * 				'slogan':	宣言
	 * 				'post':		公告
	 * 			}
	 * 			1 =>			忠义堂
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			2 =>			关公殿
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			3 =>			商城
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			4 =>			副本
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			5 =>			任务
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			6 =>			粮仓
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 				'fields':	粮田
	 * 				{
	 * 					$id		粮田id
	 * 					{
	 * 						0 => $level 等级
	 * 						1 => $exp	经验
	 * 					}
	 * 				}
	 * 			}
	 * 			7 =>			科技
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 				'skills':	技能列表
	 * 				{
	 * 					$id => $level 技能id=>技能等级
	 * 				}
	 * 			}
	 * 		}
	 * 		'leader_uid':		团长uid
	 * 		'leader_uid':		团长utid
	 * 		'leader_name':		团长名字
	 * 		'leader_level':		团长等级
	 * 		'leader_force':		团长战斗力
	 * 		'member_num':		成员数量
	 * 		'member_limit':		成员上限
	 * 		'vp_num':			副团长数量
	 * 		'rank':				战斗力排行
	 * }
	 * </code>
	 */
	function getGuildInfo();
	
	/**
	 * 获得成员的详细信息
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		'uid':				用户id
	 * 		'guild_id':			军团id, 0是没有在任何军团里
	 * 		'guild_level':		军团等级
	 * 		'member_type':		成员类型：0团员，1团长，2副团
	 * 		'contri_point':		贡献值
	 * 		'contri_num':		当天剩余贡献次数
	 * 		'contri_time':		贡献时间
	 * 		'reward_num':		当天剩余领奖次数
	 * 		'reward_time':		领奖时间
	 * 		'reward_buy_num':	奖励购买次数
	 * 		'reward_buy_time':	奖励购买时间
	 * 		'lottery_num':		当天摇奖次数
	 * 		'lottery_time':		摇奖时间
	 * 		'grain_num':		粮草数量
	 * 		'merit_num':		功勋值
	 * 		'zg_num':			战功值
	 * 		'refresh_num':		刷新次数
	 * 		'rejoin_cd':		冷却时间
     *      'playwith_num':     当天切磋次数
     *      'be_playwith_num':  当天被切磋次数
     *      'city_id':			占领的城池Id
     *      'fight_force':int	军团战斗力
	 * 		'rank':int			军团排名
	 * 		'member_num':		成员数量
	 * 		'join_time':		加入时间
	 * 		'va_member':array
	 * 		{
	 * 			'fields':array
	 * 			{
	 * 				$id				粮田id
	 * 				{
	 * 					0 => $num	剩余次数
	 * 					1 => $time	刷新时间
	 * 				}
	 * 			}
	 * 			'skills':array
	 * 			{
	 * 				$id => $level 技能id=>技能等级
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	function getMemberInfo();
	
	/**
	 * 获取分粮信息
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 	      职位=>(粮草数,人数)
	 * 	   0 => (total) 军团粮草总数
	 *     1 => (share, num) 军团长
	 *     2 => (share, num) 副军团长
	 *     3 => (share, num) 顶级精英(1-5)
	 *     4 => (share, num) 高级精英(6-10)
	 *     5 => (share, num) 精英成员(10-20)
	 *     6 => (share, num) 普通成员(20-30)
	 * }
	 * </code>
	 */
	function getShareInfo();
	
	/**
	 * 获取刷新的用户列表
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 	   0 => $uname 用户名
	 * }
	 * </code>
	 */
	function getRefreshInfo();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */