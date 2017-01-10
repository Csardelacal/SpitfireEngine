<?php namespace spitfire\storage\database\drivers;


/**
 * MySQL driver via PDO. This driver does <b>not</b> make use of prepared 
 * statements, prepared statements become too difficult to handle for the driver
 * when using several JOINs or INs. For this reason the driver has moved from
 * them back to standard querying.
 */
class mysqlPDODriver extends mysqlpdo\Driver
{
}
