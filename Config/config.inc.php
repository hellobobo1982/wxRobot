<?php
if(!defined('authority')) {header("Location: ./404.html");}

/*
 * 配置文件
 *      目录路径
 */

define('Dir_Root',$_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.'lousi_tool'.DIRECTORY_SEPARATOR);
define('Dir_Config' , Dir_Root.'config'.DIRECTORY_SEPARATOR);
define('Dir_Application' , Dir_Root.'application'.DIRECTORY_SEPARATOR);
    define('Dir_Moudule' , Dir_Application.'module'.DIRECTORY_SEPARATOR);
    //define('Dir_Moudule' , $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lousiModule'.DIRECTORY_SEPARATOR);
    define('Dir_Control' , Dir_Application.'control'.DIRECTORY_SEPARATOR);
    define('Dir_View' , Dir_Application.'view'.DIRECTORY_SEPARATOR);
        define('Dir_Js' , Dir_View.'js'.DIRECTORY_SEPARATOR);
    define('Dir_Lib' , Dir_Application.'lib'.DIRECTORY_SEPARATOR);
    define('Dir_Run' , Dir_Application.'run'.DIRECTORY_SEPARATOR);

define('Dir_Log' , Dir_Root.'log'.DIRECTORY_SEPARATOR);
define('Dir_Cache' , Dir_Root.'cache'.DIRECTORY_SEPARATOR);
    define('Dir_Cookie' , Dir_Cache.'cookie'.DIRECTORY_SEPARATOR);
    define('Dir_Qcoder' , Dir_Cache.'QcoderImg'.DIRECTORY_SEPARATOR);
    define('Dir_HeadImg' , Dir_Cache.'headImg'.DIRECTORY_SEPARATOR);
if(!is_dir(Dir_Cookie)){mkdir(iconv("UTF-8", "GBK", Dir_Cookie),0777,true);}
if(!is_dir(Dir_Qcoder)){mkdir(iconv("UTF-8", "GBK", Dir_Qcoder),0777,true);}
if(!is_dir(Dir_HeadImg)){mkdir(iconv("UTF-8", "GBK", Dir_HeadImg),0777,true);}

set_include_path(Dir_Config.PATH_SEPARATOR.Dir_Moudule.PATH_SEPARATOR.Dir_Lib.PATH_SEPARATOR.get_include_path());

define('Net_Root',"http://".$_SERVER['HTTP_HOST']."/lousi_tool/");
define('Net_Qcoder',Net_Root.'cache'.DIRECTORY_SEPARATOR.'QcoderImg'.DIRECTORY_SEPARATOR);
define('Net_HeadImg',Net_Root.'cache'.DIRECTORY_SEPARATOR.'headImg'.DIRECTORY_SEPARATOR);
define('Net_Control',Net_Root.'application'.DIRECTORY_SEPARATOR.'control'.DIRECTORY_SEPARATOR);
define('Net_Run',Net_Root.'application'.DIRECTORY_SEPARATOR.'run'.DIRECTORY_SEPARATOR);
/*
 * JD数据
 */
define('Jd_appKey',"");
define('Jd_appSecret',"");
define('Jd_unionId', '');
define('Jd_key','');

//echo Dir_Moudule;

