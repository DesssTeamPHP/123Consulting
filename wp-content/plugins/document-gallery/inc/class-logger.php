<?php
defined('WPINC') OR exit;

/**
 * Encapsulates the logic required to maintain and read log files.
 */
class DG_Logger {
   /**
    * Appends DG log file if logging is enabled. The following format is used for each line:
    * datetime | level | entry | stacktrace (optional)
    *
    * @param int The level of serverity (should be passed using DG_LogLevel consts).
    * @param string $entry Value to be logged.
    * @param bool $stacktrace Whether to include full stack trace.
    * @param bool $force Whether to ignore logging flag and log no matter what.
    */
   public static function writeLog($level, $entry, $stacktrace = false, $force = false) {
      if ($force || self::logEnabled()) {
         $fp = fopen(self::getLogFileName(), 'a');
         if (false !== $fp) {
            $fields = array(time(), $level, $entry);

            $trace = debug_backtrace(false);
            if ($stacktrace) {
               unset($trace[0]);
               
               $trace_str = '';
               $i = 1;
               
               foreach($trace as $node) {
                  $trace_str .= "#$i ";
                  
                  $file = '';
                  if (isset($node['file'])) {
                     // convert to relative path from WP root
                     $file = str_replace(ABSPATH, '', $node['file']);
                  }
                  
                  if (isset($node['line'])) {
                     $file .= "({$node['line']})";
                  }
                  
                  if ($file) {
                     $trace_str .= "$file: ";
                  }
                  
                  if(isset($node['class'])) {
                     $trace_str .= "{$node['class']}{$node['type']}";
                  }
                  
                  if (isset($node['function'])) {
                     $args = '';
                     if (isset($node['args'])) {
                        $args = implode(', ', array_map(array(__CLASS__, 'print_r'), $node['args']));
                     }
                     
                     $trace_str .= "{$node['function']}($args)" . PHP_EOL;
                  }
                  $i++;
               }
               
               $fields[] = $trace_str;
            } else {
               // Remove first item from backtrace as it's this function which is redundant.
               $caller = $trace[1];
               $caller = (isset($caller['class']) ? $caller['class'] : '') . $caller['type'] . $caller['function'];
               $fields[2] = '(' . $caller . ') ' . $fields[2];
            }
            
            fputcsv($fp, $fields);
            fclose($fp);
         } // TODO: else
      }
   }
   
   /**
    * Reads the current blog's log file, placing the values in to a 2-dimensional array.
    * @param int $skip How many lines to skip before returning rows.
    * @param int $limit Max number of lines to read.
    * @return multitype:multitype:string|null The rows from the log file or null if failed to open log.
    */
   public static function readLog($skip = 0, $limit = PHP_INT_MAX) {
      $ret = null;
      $fp = @fopen(self::getLogFileName(), 'r');
      
      if ($fp !== false) {
         $ret = array();
         while (count($ret) < $limit && ($fields = fgetcsv($fp)) !== false) {
            if ($skip > 0) {
               $skip--;
               continue;
            }
            
            if (!is_null($fields)) {
               $ret[] = $fields;
            }
         }
      }
      
      return $ret;
   }
   
   /**
    * Clears the log file for the active blog.
    */
   public static function clearLog() {
      // we don't care if the file actually exists -- it won't when we're done
      @unlink(self::getLogFileName());
   }
   
   /**
    * @return bool Whether debug logging is currently enabled.
    */
   public static function logEnabled() {
      global $dg_options;
      return $dg_options['logging'];
   }
   
   /**
    * @return string Full path to log file for current blog.
    */
   private static function getLogFileName() {
      return DG_PATH . 'log/' . get_current_blog_id() . '.log';
   }
   
   /**
    * Wraps print_r passing true for the return argument.
    * @param unknown $v Value to be printed.
    * @return string Printed value.
    */
   private static function print_r($v) {
      return print_r($v, true);
   }
}

/**
 * LogLevel acts as an enumeration of all possible log levels.
 */
class DG_LogLevel {
   /**
    * @var int Log level for anything that doesn't indicate a problem.
    */
   const Detail = 0;
   
   /**
    * @var int Log level for anything that is a minor issue.
    */
   const Warning = 1;
   
   /**
    * @var int Log level for when something went wrong.
    */
   const Error = 2;

   /**
    * @var ReflectionClass Backs the getter.
    */
   private static $ref = null;
    
   /**
    * @return ReflectionClass Instance of reflection class for this class.
    */
   private static function getReflectionClass() {
      if (is_null(self::$ref)) {
         self::$ref = new ReflectionClass(__CLASS__);
      }
   
      return self::$ref;
   }
    
   /**
    * @var multitype Backs the getter.
    */
   private static $levels = null;
   
   /**
    * @return multitype Associative array containing all log level names mapped to their int value.
    */
   public static function getLogLevels() {
      if (is_null(self::$levels)) {
         $ref = self::getReflectionClass();
         self::$levels = $ref->getConstants();
      }
      
      return self::$levels;
   }
   
   /**
    * @param string $name Name to be checked for validity.
    * @return bool Whether given name represents valid log level.
    */
   public static function isValidName($name) {
      return array_key_exists($name, self::getLogLevels());
   }
   
   /**
    * @param int $value Value to be checked for validity.
    * @return bool Whether given value represents valid log level.
    */
   public static function isValidValue($value) {
      return (false !== array_search($value, self::getLogLevels()));
   }
   
   /**
    * @param string $name The name for which to retrieve a value.
    * @return int|null The value associated with the given name.
    */
   public static function getValueByName($name) {
      $levels = self::getLogLevels();
      return array_key_exists($name, self::getLogLevels()) ? $levels[$name] : null;
   }
   
   /**
    * @param int $value The value for which to retrieve a name.
    * @return string|null The name associated with the given value.
    */
   public static function getNameByValue($value) {
      $ret = array_search($value, self::getLogLevels());
      return (false !== $ret) ? $ret : null;
   }
   
   /**
    * Blocks instantiation. All functions are static.
    */
   private function __construct() {

   }
}