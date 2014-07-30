ALTER TABLE  `media` CHANGE  `user_id`  `user_id` INT( 11 ) NULL DEFAULT NULL;

ALTER TABLE  `media` CHANGE  `bundle`  `folder` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'default';