<?php

namespace dbsnOOp;

//TAGs
const TYPE_APP_DATABASE = 'type_database';
const TYPE_APP_WEB = 'type_web';
const TYPE_APP_INIT_APP = 'type_init_app';
const TYPE_APP_FINISH_APP = 'type_finish_app';
const TYPE_APP_DEFAULT = 'type_default';
const TYPE_TRIGGER_EXCEPTION = 'trigger_exception';
const TYPE_TRIGGER_ERROR = 'trigger_error';
const TYPE_TRIGGER_WARNING = 'trigger_warning';
const TYPE_TRIGGER_NOTICE = 'trigger_notice';


//Rerite prefix
const __REDEFINED_NAME_FUNCTION__ = "__dbsnoop_renamed_function__";
const __REDEFINED_NAME_METHOD__ = "__dbsnoop_renamed_method__";

//Modes 
const INTEGRAL_MODE = 0;
const INTEGRAL_DEBUG_MODE = 1;
const TRACK_ONLY_MODE = 2;
const TRACK_ONLY_DEBUG_MODE = 3;
