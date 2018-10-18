--   在每个服务器上使用
set names utf8;
CREATE TABLE IF NOT EXISTS t_guild_war_inner_temple
(
    session     int unsigned not null comment "第几届,本表只保存一届的",
    va_extra    blob not null comment "本组冠军军团信息以及军团长信息",
    primary key(session)
)default charset utf8 engine = InnoDb;