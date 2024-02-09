<?php


namespace dbsnOOp\Utils;

final class Parameter
{
    //App Types
    const APP_DATABASE = 'database';
    const APP_WEB = 'web';
    const APP_DEFAULT = 'default';
    const TRIGGER_EXCEPTION = 'exception';
    const TRIGGER_ERROR = 'error';
    const TRIGGER_WARNING = 'warning';
    const TRIGGER_NOTICE = 'notice';

    //App Modes
    const INTEGRAL = 0;
    const INTEGRAL_DEBUG = 1;
    const TRACK_ONLY = 2;
    const TRACK_ONLY_DEBUG = 3;

    //Tags 
    //Application 
    const APP_VERSION = "app.version";
    const APP_ENV = "app.env";
    //DB
    const DB_TRANSACTION = "db.transaction";
    const DB_HOST = "db.host";
    const DB_PORT = "db.port";
    const DB_NAME = "db.name";
    const DB_TYPE = "db.type";
    const DB_QUERY = "db.query";
    const DB_VERSION = "db.version";
    const QUERY_NUM_ROWS = "db.num_rows_result";
    //Web
    const WEB_METHOD = "web.request.method";
    const WEB_TARGET = "web.request.target";
    const WEB_USER_AGENT = "web.request.user_agent";
    const WEB_REMOTE_ADDR = "web.request.remote_addr";
    const WEB_STATUS_CODE = "web.response.status_code";


    //Metas Segment
    const SEGMENT_START = "segment.times.start";
    const SEGMENT_FINISH = "segment.times.finish";
    const SEGMENT_START_NS = "segment.times.start_ns";
    const SEGMENT_FINISH_NS = "segment.times.finish_ns";
    const SEGMENT_DURATION = "segment.times.duration";
    const SEGMENT_METRIC_UTIME = "segment.metric.utime";
    const SEGMENT_METRIC_STIME = "segment.metric.stime";
    const SEGMENT_METRIC_IN_BLOCK = "segment.metric.in_block";
    const SEGMENT_METRIC_OUT_BLOCK = "segment.metric.out_block";
    const SEGMENT_METRIC_MSG_RECV = "segment.metric.msg_recv";
    const SEGMENT_METRIC_MSG_SEND = "segment.metric.msg_send";
    const SEGMENT_METRIC_IX_RSS = "segment.metric.ix_rss";
    const SEGMENT_METRIC_ID_RSS = "segment.metric.id_rss";
    const SEGMENT_METRIC_IS_RSS = "segment.metric.is_rss";

    //Error Definition
    const ERR_TYPE = "error.type";
    const ERR_NO = "error.no";
    const ERR_MSG = "error.msg";
    const ERR_FILE = "error.file";
    const ERR_LINE = "error.line";
    const ERR_TRACE = "error.trace";

}
