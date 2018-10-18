--   在跨服机器上使用
set names utf8;
create table if not exists t_guild_war_cross_server
(
	session int unsigned not null comment "报名参与第几届跨服赛",
	guild_id int unsigned not null comment "军团Id",
	guild_server_id int unsigned not null comment "军团所在服务器Id",
    team_id int unsigned not null comment "分组Id",
    sign_time int unsigned not null comment "报名时间",
    guild_level int unsigned not null comment "军团等级",
    guild_badge int unsigned not null comment "军团徽章",
    guild_name varchar(64) not null comment "军团名称",
    guild_server_name varchar(64) not null comment "军团所在服务器名称",
    final_rank int unsigned not null comment "最终名次",
    lose_times int unsigned not null comment "海选时候失败的次数",
    pos int unsigned not null comment "晋级赛中随机的位置",
    fight_force int unsigned not null comment "战斗力",
    last_fight_time int unsigned not null comment "上次战斗的时间",
    va_replay blob not null comment "
									audition => array	海选赛战报
									[
										{
											replay_id
											result	战斗评价，ABCDEF等
											attacker => array	胜者信息，如果胜者是自己，则为空数组
											{
												guild_id			军团Id
												guild_name			军团名称
												guild_server_id		服务器Id
												guild_server_name	服务器名称
											}
											defender => array
											{
												guild_id			军团Id
												guild_name			军团名称
												guild_server_id		服务器Id
												guild_server_name	服务器名称
											}
										}
									]
									finals => array 	晋级赛战报
									[
										round => array					轮次做key    例如：8强赛，4强赛，半决赛，决赛
										{
											result						战斗评价，ABCDEF等
											attacker => array
											{
												guild_id			军团Id
												guild_name			军团名称
												guild_server_id		服务器Id
												guild_server_name	服务器名称
											}
											defender => array
											{
												guild_id			军团Id
												guild_name			军团名称
												guild_server_id		服务器Id
												guild_server_name	服务器名称
											}
											sub_round => array
											[
												{
													replay_id
												}
											]
                                            left_user => array
                                            [
                                                sub_round => []
                                            ]
										}
									]
									",
    va_extra blob not null comment "
									candidates			候选者
									[
										uid
									]
									fighters			已经上阵的玩家信息
									[
										uid=>胜利次数
									]
									losers				已经死掉的人
									[
										uid
									]
									hp					血量信息
									[
										uid=>array
										[
											hid=>血量
										]
									]
									president_info
                                    {
                                    }
									",
    primary key(session, guild_id, guild_server_id),
    index(session, team_id)
)default charset utf8 engine = InnoDb;