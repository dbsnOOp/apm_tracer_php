<?php

namespace dbsnOOp;

//TYPESs
const TYPE_APP_DATABASE = 'type_database';
const TYPE_APP_WEB = 'type_web';
const TYPE_APP_INIT_APP = 'type_init_app';
const TYPE_APP_FINISH_APP = 'type_finish_app';
const TYPE_APP_DEFAULT = 'type_default';
const TYPE_TRIGGER_EXCEPTION = 'trigger_exception';
const TYPE_TRIGGER_ERROR = 'trigger_error';
const TYPE_TRIGGER_WARNING = 'trigger_warning';
const TYPE_TRIGGER_NOTICE = 'trigger_notice';


//Database TYPEs
const TYPE_APP_DATABASE_CONNECT = 'type_database_connect';
const TYPE_APP_DATABASE_QUERY = 'type_database_query';
const TYPE_APP_DATABASE_TRANSACTION = 'type_database_transaction';


//Rerite prefix
const __REDEFINED_NAME_FUNCTION__ = "__dbsnoop_renamed_function__";
const __REDEFINED_NAME_METHOD__ = "__dbsnoop_renamed_method__";

//Modes 
const INTEGRAL_MODE = 0;
const INTEGRAL_DEBUG_MODE = 1;
const TRACK_ONLY_MODE = 2;
const TRACK_ONLY_DEBUG_MODE = 3;


//INFO Names
const DB_TRANSACTION = "database_in_transaction";
const DB_HOST = "database_host";
const DB_PORT = "database_port";
const DB_NAME = "database_name";
const DB_TYPE = "database_type";
const DB_QUERY = "database_query";
const DB_VERSION = "database_version";
const QUERY_NUM_ROWS = "query_num_rows";



//INFO Web
const WEB_METHOD = "web_method";
const WEB_TARGET = "web_target";
const WEB_STATUS_CODE = "web_status_code";
const WEB_USER_AGENT = "web_user_agent";
const WEB_REMOTE_ADDR = "web_remote_addr";