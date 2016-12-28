DROP TABLE "configuration" CASCADE\g
CREATE TABLE  "configuration" (
   "conf_index"   varchar(32) NOT NULL DEFAULT '', 
   "conf_value"   varchar(32) DEFAULT NULL, 
   primary key ("conf_index")
)  ;

INSERT INTO "configuration" VALUES (E'domain_key',E'domain_key');
INSERT INTO "configuration" VALUES (E'key_period',E'60');
INSERT INTO "configuration" VALUES (E'key_size',E'8');

DROP TABLE "ipv4" CASCADE\g
CREATE TABLE  "ipv4" (
   "ip"   varchar(15) DEFAULT NULL, 
   "ip_owner" int CHECK ("ip_owner" >= 0) DEFAULT '0',
 unique ("ip") 
)  ;

CREATE INDEX "ipv4_ip_owner_idx" ON "ipv4" USING btree ("ip_owner");

DROP TABLE "request_message" CASCADE\g
DROP SEQUENCE "request_message_request_message_id_seq" CASCADE ;

CREATE SEQUENCE "request_message_request_message_id_seq" ;

CREATE TABLE  "request_message" (
   "request_message_id" integer DEFAULT nextval('"request_message_request_message_id_seq"') NOT NULL,
   "request_topic" int CHECK ("request_topic" >= 0) DEFAULT NULL,
   "request_message"   text, 
   "request_message_date" int CHECK ("request_message_date" >= 0) DEFAULT NULL,
   "request_message_user"   int DEFAULT NULL, 
   "request_message_read"   int DEFAULT '0', 
   primary key ("request_message_id")
)  ;

CREATE INDEX "request_message_request_topic_idx" ON "request_message" USING btree ("request_topic");
CREATE INDEX "request_message_request_message_user_idx" ON "request_message" USING btree ("request_message_user");
CREATE INDEX "request_message_request_message_read_idx" ON "request_message" USING btree ("request_message_read");

DROP TABLE "request_topic" CASCADE\g
DROP SEQUENCE "request_topic_request_topic_id_seq" CASCADE ;

CREATE SEQUENCE "request_topic_request_topic_id_seq" ;

CREATE TABLE  "request_topic" (
   "request_topic_id" integer DEFAULT nextval('"request_topic_request_topic_id_seq"') NOT NULL,
   "request_topic_title"   varchar(255) DEFAULT NULL, 
   "request_topic_created" int CHECK ("request_topic_created" >= 0) DEFAULT NULL,
   "request_topic_author" int CHECK ("request_topic_author" >= 0) DEFAULT NULL,
   "request_topic_resolved"   int DEFAULT '0', 
   primary key ("request_topic_id")
)  ;

CREATE INDEX "request_topic_request_topic_author_idx" ON "request_topic" USING btree ("request_topic_author");
CREATE INDEX "request_topic_request_topic_resolved_idx" ON "request_topic" USING btree ("request_topic_resolved");
CREATE INDEX "request_topic_request_topic_created_idx" ON "request_topic" USING btree ("request_topic_created");

DROP TABLE "schedule" CASCADE\g
DROP SEQUENCE "schedule_schedule_id_seq" CASCADE ;

CREATE SEQUENCE "schedule_schedule_id_seq" ;

CREATE TABLE  "schedule" (
   "schedule_id" integer DEFAULT nextval('"schedule_schedule_id_seq"') NOT NULL,
   "schedule_vps" int CHECK ("schedule_vps" >= 0) DEFAULT NULL,
   "name"   varchar(63) DEFAULT NULL, 
   "minute"  smallint CHECK ("minute" >= 0) DEFAULT NULL,
   "hour"  smallint CHECK ("hour" >= 0) DEFAULT NULL,
   "dayw" int CHECK ("dayw" >= 0) DEFAULT NULL,
   "dayn" int CHECK ("dayn" >= 0) DEFAULT NULL,
   "month" int CHECK ("month" >= 0) DEFAULT NULL,
   primary key ("schedule_id")
)  ;

CREATE INDEX "schedule_schedule_vps_idx" ON "schedule" USING btree ("schedule_vps");

DROP TABLE "server" CASCADE\g
DROP SEQUENCE "server_server_id_seq" CASCADE ;

CREATE SEQUENCE "server_server_id_seq" ;

CREATE TABLE  "server" (
   "server_id" integer DEFAULT nextval('"server_server_id_seq"') NOT NULL,
   "server_name"   varchar(63) DEFAULT NULL, 
   "server_address"   varchar(63) DEFAULT NULL, 
   "server_port" smallint CHECK ("server_port" >= 0) DEFAULT '10000',
   "server_description"   text, 
   "server_key"   varchar(64) DEFAULT NULL, 
   primary key ("server_id")
)  ;

DROP TABLE "uservca" CASCADE\g
DROP SEQUENCE "uservca_user_id_seq" CASCADE ;

CREATE SEQUENCE "uservca_user_id_seq"  START WITH 2 ;

CREATE TABLE  "uservca" (
   "user_id" integer DEFAULT nextval('"uservca_user_id_seq"') NOT NULL,
   "user_name"   varchar(63) DEFAULT NULL, 
   "user_mail"   varchar(63) DEFAULT NULL, 
   "user_rank"  smallint CHECK ("user_rank" >= 0) DEFAULT '0',
   "user_password"   varchar(128) DEFAULT NULL, 
   "user_token"   varchar(128) DEFAULT NULL, 
   "user_language"   varchar(15) DEFAULT 'en_GB', 
   "user_created" bigint CHECK ("user_created" >= 0) DEFAULT '0',
   "user_activity" bigint CHECK ("user_activity" >= 0) DEFAULT '0',
   "user_bkppass"   varchar(64) DEFAULT '', 
   "user_dropbox"   varchar(64) DEFAULT NULL, 
   "user_strongauth"  smallint CHECK ("user_strongauth" >= 0) DEFAULT '0',
   "user_tokenid"   varchar(32) DEFAULT NULL, 
   "user_pin"   varchar(4) DEFAULT NULL, 
   primary key ("user_id")
)   ;

INSERT INTO "uservca" VALUES (1,E'Admin',NULL,1,E'90a3d5e5e8bd5fd4acfaf86b304fa4e4bb7172238a19df25737a71e9e260201fbc6a19a0eee10f84d1e9ab443ee9aaabd9d4a1b5d2bb4c1813767adfdb144250',NULL,E'en_GB',0,0,E'',NULL,0,NULL,NULL); 

DROP TABLE "vps" CASCADE\g
DROP SEQUENCE "vps_vps_id_seq" CASCADE ;

CREATE SEQUENCE "vps_vps_id_seq" ;

CREATE TABLE  "vps" (
   "vps_id" integer DEFAULT nextval('"vps_vps_id_seq"') NOT NULL,
   "vps_name"   varchar(63) DEFAULT NULL, 
   "vps_ipv4"   varchar(15) DEFAULT NULL, 
   "vps_description"   text, 
   "vps_owner" int CHECK ("vps_owner" >= 0) DEFAULT NULL,
   "vps_protected"  smallint CHECK ("vps_protected" >= 0) DEFAULT '0',
   "server_id" int CHECK ("server_id" >= 0) DEFAULT '0',
   "last_maj" bigint CHECK ("last_maj" >= 0) DEFAULT '0',
   "vps_cpulimit" int CHECK ("vps_cpulimit" >= 0) DEFAULT NULL,
   "vps_cpus" int CHECK ("vps_cpus" >= 0) DEFAULT NULL,
   "vps_cpuunits" int CHECK ("vps_cpuunits" >= 0) DEFAULT NULL,
   "ostemplate"   varchar(63) DEFAULT NULL, 
   "origin_sample"   varchar(63) DEFAULT NULL, 
   "onboot"  smallint CHECK ("onboot" >= 0) DEFAULT '1',
   "quotatime" int CHECK ("quotatime" >= 0) DEFAULT NULL,
   "diskspace" bigint CHECK ("diskspace" >= 0) DEFAULT NULL,
   "ram" bigint CHECK ("ram" >= 0) DEFAULT NULL,
   "ram_current" int CHECK ("ram_current" >= 0) DEFAULT '0',
   "swap" int CHECK ("swap" >= 0) DEFAULT '0',
   "diskinodes" int CHECK ("diskinodes" >= 0) DEFAULT NULL,
   "nproc" int CHECK ("nproc" >= 0) DEFAULT NULL,
   "loadavg"   varchar(63) DEFAULT NULL, 
   "diskspace_current" bigint CHECK ("diskspace_current" >= 0) DEFAULT '0',
   "backup_limit"  smallint CHECK ("backup_limit" >= 0) DEFAULT NULL,
   "mod_fuse"  smallint CHECK ("mod_fuse" >= 0) DEFAULT '0',
   "mod_tun"  smallint CHECK ("mod_tun" >= 0) DEFAULT '0',
   primary key ("vps_id")
)  ;

CREATE INDEX "vps_vps_owner_idx" ON "vps" USING btree ("vps_owner");
CREATE INDEX "vps_server_id_idx" ON "vps" USING btree ("server_id");
