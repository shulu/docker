CREATE TABLE IF NOT EXISTS rec_group_posts (
  `seq_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  PRIMARY KEY (`seq_id`),
  KEY `gid_sid_idx` (`group_id`, `seq_id`),
  UNIQUE KEY `gid_pid_udx` (`group_id`, `post_id`)
);

TRUNCATE rec_group_posts;
INSERT INTO rec_group_posts(group_id, post_id) SELECT 1, post_id FROM post_category_score WHERE only_category = 0 ORDER BY id ASC;
INSERT INTO rec_group_posts(group_id, post_id) SELECT category_id, post_id FROM post_category_score ORDER BY id ASC;