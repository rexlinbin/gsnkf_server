CREATE TABLE IF NOT EXISTS `t_tmp_guild` (
	`guild_id`	INT unsigned NOT NULL DEFAULT 0,
	`game_id`	VARCHAR(255) NOT NULL,
	`name`		CHAR(32) NOT NULL,
	`deal`		INT unsigned NOT NULL DEFAULT 0,
	`new_guild_id`	INT unsigned NOT NULL DEFAULT 0,
	primary key(`guild_id`, `game_id`),
	key(`guild_id`)
)engine = InnoDb default charset utf8;