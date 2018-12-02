CREATE TABLE ciyo_report.comment_reports (id BIGINT AUTO_INCREMENT NOT NULL, reporter_id BIGINT NOT NULL, comment_id BIGINT NOT NULL, time DATETIME NOT NULL, UNIQUE INDEX comment_reporter_udx (comment_id, reporter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB;
CREATE TABLE ciyo_report.post_reports (id BIGINT AUTO_INCREMENT NOT NULL, reporter_id BIGINT NOT NULL, post_id BIGINT NOT NULL, time DATETIME NOT NULL, UNIQUE INDEX post_reporter_udx (post_id, reporter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB;

ALTER TABLE `post_reports` auto_increment = 100000;
ALTER TABLE `comment_reports` auto_increment = 100000;

INSERT INTO ciyo_report.post_reports(id, reporter_id, post_id, `time`) SELECT MAX(id) as id, reporter_id, subject_id as post_id, `time` FROM `ciyocon`.`report` WHERE type = 1 GROUP BY reporter_id, subject_id ORDER BY id ASC;

INSERT INTO ciyo_report.comment_reports(id, reporter_id, comment_id, `time`) SELECT MAX(id) as id, reporter_id, subject_id as comment_id, `time` FROM `ciyocon`.`report` WHERE type = 2 GROUP BY reporter_id, subject_id ORDER BY id ASC;