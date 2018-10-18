--   在每个服务器上使用
set names utf8;
create table if not exists t_guild_war_inner_user
(
    uid int unsigned not null comment "用户uid",
    uname varchar(64) not null comment "用户名称",
    cheer_guild_id int unsigned not null comment "助威对象",
    cheer_guild_server_id int unsigned not null comment "助威对象所在服务器ID",
    cheer_round int unsigned not null comment "助威轮次",
    buy_max_win_num int unsigned not null comment "已经购买连胜的次数",
    buy_max_win_time int unsigned not null comment "购买连胜时刻",
    worship_time int unsigned not null comment "膜拜时刻",
    fight_force int unsigned not null comment "战斗力",
    update_fmt_time int unsigned not null comment "更新战斗力时刻",
    send_prize_time int unsigned not null comment "发放跨服赛排名奖励时刻",
    last_join_time int unsigned not null comment "最后更新时间",
    va_extra blob not null comment "cheer=>array  每轮的助威信息
                  [
                    round=>array  
                    {
                      guildId
                      guildName
                      serverId
                      serverName
                      reward_time
                    }
                  ]
                  battle_fmt=>array 玩家战斗数据
                  {
                    htid
                    formation
                    arrHero
                    .....
                  }",
    primary key(uid),
    index(cheer_guild_id, cheer_guild_server_id)
)default charset utf8 engine = InnoDb;