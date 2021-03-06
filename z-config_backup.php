<?php 
//for MySQL
define("MYSQL_USER"  ,"root");
define("MYSQL_PASSWD","serverroot");

define("MASTER_TABLE","master");
    
define("DEFAULT_COMPILER","WCB");
    
//submission status
define("SUBSTATUS_UNDEFINED",0);
define("SUBSTATUS_INQUEUE"  ,1);
define("SUBSTATUS_GRADING"  ,2);
define("SUBSTATUS_ACCEPTED" ,3);
define("SUBSTATUS_REJECTED" ,4);
    
//user types
define("USERTYPE_SUPERADMIN",'SA');
define("USERTYPE_ADMIN"     , 'A');
define("USERTYPE_SUPERVISOR", 'S');
define("USERTYPE_CONTESTANT", 'C');
    
//prob types
define("PROBLEM_ONLINE" ,'ON');
define("PROBLEM_OFFLINE",'OFF');
    
//prob ready
define("PROBLEM_READY"  ,'ready');
define("PROBLEM_UNREADY",'unready');
    
    
define("COLOR_NONE",' ');
    
define("LOG_APPROVE"  ,'Y');
define("LOG_UNAPPROVE",'N');
    
//start at web-submission/
define("PATH_CONTENT_PROBLEM","../../grader/ev");
define("PATH_CONTENT_SOURCE" ,"../../grader/test-res");
define("PATH_CONTENT_GRADER" ,"..\\..\\grader");
    
define("TEMPLATE_DB_PATH", "./data/template.sql");
?>