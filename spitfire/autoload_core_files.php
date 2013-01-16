<?php

#Set the base directory
$cur_dir = dirname(__FILE__);

#Define default classes and their locations
_SF_AutoLoad::registerClass('fileNotFoundException', $cur_dir.'/exceptions.php');
_SF_AutoLoad::registerClass('publicException',       $cur_dir.'/exceptions.php');
_SF_AutoLoad::registerClass('_SF_ExceptionHandler',  $cur_dir.'/exceptions.php');
_SF_AutoLoad::registerClass('_SF_Path',              $cur_dir.'/core/path.php');

_SF_AutoLoad::registerClass('router',                $cur_dir.'/router.php');
_SF_AutoLoad::registerClass('environment',           $cur_dir.'/environment.php');

_SF_AutoLoad::registerClass('_SF_MVC',               $cur_dir.'/mvc/mvc.php');
_SF_AutoLoad::registerClass('Controller',            $cur_dir.'/mvc/controller.php');
_SF_AutoLoad::registerClass('View',                  $cur_dir.'/mvc/view.php');
_SF_AutoLoad::registerClass('_SF_ViewElement',       $cur_dir.'/mvc/view_element.php');

_SF_AutoLoad::registerClass('_SF_Memcached',         $cur_dir.'/storage.php');
_SF_AutoLoad::registerClass('Image',                 $cur_dir.'/image.php');

#Database related imports
_SF_AutoLoad::registerClass('DBInterface',           $cur_dir.'/db/db.php');
_SF_AutoLoad::registerClass('_SF_Queriable',         $cur_dir.'/db/queriable.php');
_SF_AutoLoad::registerClass('_SF_DBTable',           $cur_dir.'/db/table.php');
_SF_AutoLoad::registerClass('_SF_DBQuery',           $cur_dir.'/db/dbquery.php');
_SF_AutoLoad::registerClass('_SF_Restriction',       $cur_dir.'/db/restriction.php');
_SF_AutoLoad::registerClass('_SF_DBDriver',          $cur_dir.'/db/driver.php');
_SF_AutoLoad::registerClass('Pagination',            $cur_dir.'/db/pagination.php');

_SF_AutoLoad::registerClass('resultSetInterface',    $cur_dir.'/db/resultset.php');
_SF_AutoLoad::registerClass('databaseRecord',        $cur_dir.'/db/databaseRecord.php');

_SF_AutoLoad::registerClass('_SF_stdSQLDriver',      $cur_dir.'/db/drivers/stdSQL.php');
_SF_AutoLoad::registerClass('_SF_mysqlPDODriver',    $cur_dir.'/db/drivers/mysqlPDO.php');
_SF_AutoLoad::registerClass('_SF_mysqlPDOResultSet', $cur_dir.'/db/drivers/mysqlPDORes.php');

_SF_AutoLoad::registerClass('_SF_InputSanitizer',    $cur_dir.'/security_io_sanitization.php');
_SF_AutoLoad::registerClass('_SF_Invoke',            $cur_dir.'/mvc/invoke.php');
_SF_AutoLoad::registerClass('_SF_Class',             $cur_dir.'/class.php');

_SF_AutoLoad::registerClass('Strings',               $cur_dir.'/strings.php');
_SF_AutoLoad::registerClass('Headers',               $cur_dir.'/headers.php');

_SF_AutoLoad::registerClass('ComponentManager',      $cur_dir.'/components/componentManager.php');
_SF_AutoLoad::registerClass('Component',             $cur_dir.'/components/component.php');
_SF_AutoLoad::registerClass('assetsController',      $cur_dir.'/components/assets.php');

_SF_AutoLoad::registerClass('URL',                   $cur_dir.'/url.php');
_SF_AutoLoad::registerClass('session',               $cur_dir.'/session.php');

_SF_AutoLoad::registerClass('Email',                 $cur_dir.'/mail.php');