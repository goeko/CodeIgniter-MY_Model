<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Model class with basic CRUD functions and validation against a database
 * schema.
 *
 * @package CodeIgniter
 * @subpackage MY_Model
 * @license Attribution-Share Alike 2.5 <http://creativecommons.org/licenses/by-sa/2.5/>
 * @author Istvan Pusztai <http://istvanp.me>
 * @link http://bitbucket.org/istvanp/codeigniter-my_model
 * @copyright Copyright (C) 2010, Istvan Pusztai <http://istvanp.me>
 **/
 
class MY_Model extends CI_Model
{
	/**
	 * The name of the DataSource connection that this Model uses
	 *
	 * @var string
	 * @access public
	 */
	var $useDbConfig = 'default';
	
	/**
	 * Value of the primary key ID of the record that this model is currently pointing to
	 *
	 * @var unknown_type
	 * @access public
	 */
	var $id = null;
		
	/**
	 * Container for the data that this model gets from persistent storage (the database).
	 *
	 * @var array
	 * @access public
	 */
	var $data = array();
	
	/**
	 * A PHP date formatted string used to send DATETIME values
	 *
	 * @var string
	 */
	const DATE_DATETIME = 'Y-m-d H:i:s';
	
	/**
	 * Name of the table. This is set automatically via the name of the
	 * subclass (e.g. User_model => 'user').
	 * Set to a non-null value via the setter to bypass this magic (in
	 * the constructor of the subclass).
	 *
	 * @var string
	 */
	private $table = NULL;
	
	/**
	 * String to prepend in front of the column names. Use its setter to set.
	 *
	 * @var string
	 */
	private	$prefix	= '';
	
	/**
	 * Name of the primary key column. This is set automatically via the schema.
	 * Set to a non-null value to bypass.
	 *
	 * @var string
	 */
	private $primary_key = NULL;
	
	/**
	 * Set to true if you wish to throw exceptions when there is an error in
	 * the validation against the schema. Use catch blocks to handle them.
	 *
	 * @var boolean
	 */
	public $debug = TRUE;
	
	/**
	 * The last inserted ID of the data that this model created
	 *
	 * @var int
	 * @access private
	 */
	var $__insertID = null;

	/**
	 * The number of records returned by the last query
	 *
	 * @access private
	 * @var int
	 */
	var $__numRows = null;

	/**
	 * The number of records affected by the last query
	 *
	 * @access private
	 * @var int
	 */
	var $__affectedRows = null;	

	/**
	 * Tells the model whether to return results in array or not
	 *
	 * @var string
	 * @access public
	 */
	var $returnArray = TRUE;
	
	/**
	 * An array of functions to be called before a record is created.
	 *
	 * @var array
	 */
	public $before_create = array();
	
	/**
	 * An array of functions to be called before a record is updated.
	 *
	 * @var array
	 */
	public $before_update = array();
	
	/**
	 * An array of functions to be called before a record is deleted.
	 *
	 * @var array
	 */
	public $before_delete = array();
	
	/**
	 * An array containing the full schema of the table.
	 * The format is as follows:
	 *
	 * array (
	 *     'column_name_1' => array( <DATATYPE>, <LENGTH>, <FLAGS>, <DEFAULT> ),
	 *     'column_name_2' =>
	 *     [...]
	 * );
	 *
	 * Where:
	 *
	 *   column_name: Name of the column you are defining. Note that if you are
	 *                using a prefix, you should not include it in the names.
	 *   <DATATYPE> : A PHP constant of a database data type (e.g. VARCHAR).
	 *                See (**) note if using ENUM or SET.
	 *   <LENGTH>(*): An arbitrary maximum length (integer value) or NULL.
	 *                If NULL then the max length is based on the data type
	 *                otherwise the value here takes precedence.
	 *   <FLAGS>    : PHP constant(s): NN (NOT NULL) or UN (UNSIGNED) or
	 *                AI (AUTO-INCREMENT) or PK (PRIMARY KEY) or NULL.
	 *                Use the + operator for combinations (e.g. PK + AI + UN).
	 *                Note that NN is not required when PK is present.
	 *   <DEFAULT>  : Default value to assign if a value is not provided.
	 *                
	 * (*) Length must make sense for the data type specified. For example a
	 *     VARCHAR column can only have a length between 0 and 255. Hence only
	 *     specify the max length if it is not the maximum allowed for the data
	 *     type. The maximum length for the chosen data type is determined
	 *     automatically if you set LENGTH to NULL.
	 *(**) If you are using ENUM or SET data type then you must define the
	 *     values within LENGTH as an array of values.
	 *
	 * @var array
	 */
	public $schema = array();

	/**
	 * Stores all the queries if debug is TRUE
	 *
	 * @var array
	 * @access public
	 */
	var $queries = array();

	/**
	 * Container for the fields of the table that this model gets from persistent storage (the database).
	 *
	 * @var array
	 * @access public
	 */
	var $fields = array();
	/**
	 * Used by CI
	 *
	 * @var string
	 * @access private
	 */
	var $_parent_name = '';

	/**
	 * Constructor method. Defines constants.
	 *
	 * @access public
	 * @return void
	 */
	public function MY_Model()
	{
		parent::CI_Model();
		
		// Set the debug variable via an external constant
		if (defined('DEBUG_SCHEMA'))
		{
			$this->debug = DEBUG_SCHEMA;
		}
		
		// Define constants only once
		if ( ! defined('MODEL_LOADED'))
		{
			define('MODEL_LOADED', 1);
			$this->_define_constants();
		}
		
		// If the magic __get() or __set() methods are used in a Model references can't be used.
		$this->_assign_libraries( (method_exists($this, '__get') OR method_exists($this, '__set')) ? FALSE : TRUE );
		
		// We don't want to assign the model object to itself when using the
		// assign_libraries function below so we'll grab the name of the model parent
		$this->_parent_name = ucfirst(get_class($this));
		
		log_message('debug', "Model Class Initialized");
		
	}

	/**
	 * Assign Libraries
	 *
	 * Creates local references to all currently instantiated objects
	 * so that any syntax that can be legally used in a controller
	 * can be used within models.  
	 *
	 * @access private
	 */	
	function _assign_libraries($use_reference = TRUE)
	{
		$CI =& get_instance();				
		foreach (array_keys(get_object_vars($CI)) as $key)
		{
			if ( ! isset($this->$key) AND $key != $this->_parent_name)
			{			
				// In some cases using references can cause
				// problems so we'll conditionally use them
				if ($use_reference == TRUE)
				{
					// Needed to prevent reference errors with some configurations
					$this->$key = '';
					$this->$key =& $CI->$key;
				}
				else
				{
					$this->$key = $CI->$key;
				}
			}
		}		
	}
	
	/**
	 * Load the associated database table.
	 *
	 * @author goeko <goeko@goeko.de>
	 * @access public
	 */	

	function loadTable($table, $fields = NULL, $config = 'default')
	{
		if ($this->debug) log_message('debug', "Loading model table: $table");
		$this->table = $table;
		$this->useDbConfig = $config;	
		$this->load->database($config);


		if($fields == 1){
			echo "<pre>";
			// print_r($this->db->list_fields($table));
			$export_fields´= array();
			foreach ($this->db->list_fields($table) as $key => $value) {
				$export_fields[] = $value;
			}
			$export_fields = implode("',\n'", $export_fields);
			echo '$fields = array(\''.$export_fields.'\');';
			echo "</pre>";
			exit();
		}
		//$this->db->cache_on();
		// $this->db->table_cache_single($table);

		if(!is_array($fields)){
			//$this->fields = $this->db->field_names($table); // < 1.7.0
			$this->fields = $this->db->list_fields($table); // >= 1.7.0
		} else {
			$this->fields = $fields;
		}	
		if ($this->debug) 
		{
			log_message('debug', "Successfull Loaded model table: $table");
		}
	}

	/**
	 * -------------------------------------------------------------------------
	 * CRUD Funtions
	 * ------------------------------------------------------------------------- 
	 */
	
	/**
	 * Performs any defined callbacks then creates an entry using the passed
	 * data. Fields with defaults can be ommitted.
	 * Returns the newly inserted ID by default or can return TRUE instead via
	 * the 2nd parameter.
	 *
	 * @access protected
	 * @param array The data (key/value pair) to create
	 * @param bool To return TRUE on success or the inserted ID. 
	 * @return mixed
	 */
	public function create(array $data, $return_id = TRUE)
	{
		$data = $this->_run_callback($this->before_create, $data);
		$data = $this->_prepare($this->schema, $data, TRUE);
		
		if ($data !== FALSE)
		{			
			if ($this->_create($data) === 1)
			{
				return ($return_id) ? $this->db->insert_id() : TRUE;
			}
		}
		return FALSE;
	}
	
	/**
	 * Retrieves a single row from the table using a primary key value.
	 *
	 * @access public
	 * @param mixed Identifying value to retrieve
	 * @return object
	 */
	public function get($pk_value)
	{
		$query = $this->db->where($this->get_primary_key(), $pk_value)
		                  ->get($this->get_table_name());
		return ($query->num_rows() === 1) ? $query->row() : FALSE;
	}
	
	/**
	 * Retrieves a single row from the table using a where condition.
	 *
	 * @access public
	 * @param mixed Where condition
	 * @return object
	 */
	public function get_by($where, $select = '*')
	{
		$query = $this->db->select($select)
		                  ->where($where)
		                  ->get($this->get_table_name());
		return ($query->num_rows() == 1) ? $query->row() : (($query->num_rows() > 1) ? $query->result() : FALSE);
	}
	
	/**
	 * Retrieves a specified range of rows from the table.
	 *
	 * @access public
	 * @param integer Total rows to retrieve
	 * @param integer Start from an offset
	 * @return object
	 */
	public function get_partial($limit, $offset)
	{
		return $this->db->get($this->get_table_name(), $limit, $offset);
	}
	
	/**
	 * Retrieves all rows in the table
	 *
	 * @access public
	 * @return object
	 */
	public function get_all($result_type = 'object')
	{
		return ($result_type == 'object') ?
			$this->db->get($this->get_table_name())->result() :
			$this->db->get($this->get_table_name())->result_array();
	}
	
	/**
	 * Counts the number of rows in the table
	 *
	 * @access public
	 * @return int
	 */
	public function count_all()
	{
		return $this->db->count_all($this->get_table_name());
	}
	
	/**
	 * Counts the number of rows in the table with a where condition
	 *
	 * @access public
	 * @param mixed A key/value pair array
	 * @return int
	 */
	public function count_where($where)
	{
		return $this->db->select('COUNT(*) AS `numrows`')
		                ->where($where)
		                ->get($this->get_table_name())
		                ->row()
		                ->numrows;
	}
	
	/**
	 * Checks if a value exists for a particular column in the table and
	 * returns true if it exists, false otherwise. Returns NULL if the column
	 * does not exist in the schema.
	 *
	 * @access public
	 * @param string Name of the column to check against
	 * @param string Value that must exist in specified column
	 * @return mixed
	 */
	public function exists($column, $value)
	{
		return $this->_exists($column, $value);
	}
	
	/**
	 * Performs any defined callbacks then updates an entry using the passed
	 * condition and data. Returns TRUE on success.
	 *
	 * @access public
	 * @param mixed A key/value pair array if more than 1 condition, a primary key value otherwise
	 * @param array The data (key/value pair) to update
	 * @return mixed FALSE on error, number of affected rows otherwise
	 */
	public function update($where, array $data, $affected_rows = FALSE)
	{
		$data = $this->_run_callback($this->before_update, $data, $where);
		$data = $this->_prepare($this->schema, $data, FALSE);
		
		if ($data !== FALSE)
		{
			return ($affected_rows) ?
				$this->_update($where, $data) :
				$this->_update($where, $data) >= 1;
		}
		
		return FALSE;
	}
	
	/**
	 * Performs any defined callbacks then deletes an entry using the passed
	 * condition and returns TRUE on success.
	 *
	 * @access public
	 * @param mixed A key/value pair array if more than 1 condition, a primary key value otherwise
	 * @return bool
	 */
	public function delete($where, $affected_rows = FALSE)
	{
		$where = $this->_run_callback($this->before_delete, $where);
		
		if ($where !== FALSE)
		{
			return ($affected_rows) ?
				$this->_delete($where) :
				$this->_delete($where) >= 1;
		}
		
		return FALSE;
	}
	
	/**
	 * -------------------------------------------------------------------------
	 * Utility Funtions
	 * ------------------------------------------------------------------------- 
	 */
	
	/**
	 * Returns a database DATETIME formatted string from the current time.
	 *
	 * @access public
	 * @return string
	 */
	public function now()
	{
		return $this->unix2datetime($_SERVER['REQUEST_TIME']);
	}
	
	/**
	 * Converts a timestamp into a database DATETIME formatted string.
	 *
	 * @access public
	 * @param string UNIX Timestamp
	 * @return string
	 */
	public function unix2datetime($timestamp)
	{
		return date(self::DATE_DATETIME, $timestamp);
	}
	
	/**
	 * Converts any valid date & time string into a database DATETIME formatted
	 * string.
	 *
	 * @access public
	 * @param string Date & time string
	 * @return string
	 */
	public function str2datetime($str)
	{
		return date(self::DATE_DATETIME, strtotime($str));
	}
	
	/**
	 * -------------------------------------------------------------------------
	 * Basic CRUD Funtions (no callbacks and no schema check)
	 * ------------------------------------------------------------------------- 
	 */

	/**
	 * Creates an entry using the passed data. Returns the number of affected
	 * rows.
	 *
	 * @access protected
	 * @param array The data (key/value pair) to create
	 * @return integer
	 */
	protected function _create($data)
	{
		$this->db->insert($this->get_table_name(), $data);
		
		return $this->db->affected_rows();
	}
	
	/**
	 * Updates an entry using the passed condition and data. Returns the number
	 * of affected rows.
	 *
	 * @access protected
	 * @param mixed A key/value pair array if more than 1 condition, a primary key value otherwise
	 * @param array The data (key/value pair) to update
	 * @return integer
	 */
	protected function _update($where, $data)
	{
		if (is_array($where) && $this->get_prefix() != '')
		{
			$where_prefixed = array();
			
			foreach($where as $key => $value)
			{
				$where_prefixed[$this->get_prefix().$key] = $value;
			}
			
			$this->db->where($where_prefixed)
			         ->update($this->get_table_name(), $data);
		}
		else
		{
			$this->db->where($this->get_primary_key(), $where)
			         ->update($this->get_table_name(), $data);
		}
		         
		return $this->db->affected_rows();
	}
	
	/**
	 * Deletes an entry using the passed condition and returns the number of
	 * affected rows.
	 *
	 * @access protected
	 * @param mixed A key/value pair array if more than 1 condition, a primary key value otherwise
	 * @return integer
	 */
	protected function _delete($where)
	{		
		if (is_array($where) && $this->get_prefix() != '')
		{
			$where_prefixed = array();
			
			foreach($where as $key => $value)
			{
				$where_prefixed[$this->get_prefix().$key] = $value;
			}
			
			$this->db->where($where_prefixed)
			         ->delete($this->get_table_name());
		}
		else
		{
			$this->db->where($this->get_primary_key(), $where)
			         ->delete($this->get_table_name());
		}
		
		return $this->db->affected_rows();
	}
	
	/**
	 * Checks if a value exists for a particular column in the table and
	 * returns true if it exists, false otherwise. Returns NULL if the column
	 * does not exist in the schema.
	 *
	 * @access protected
	 * @param string Name of the column to check against
	 * @param string Value that must exist in specified column
	 * @return mixed
	 */
	protected function _exists($column, $value)
	{
		// Does the column provided exist in the schema?
		if ( ! array_key_exists($column, $this->schema))
		{
			if ($this->debug)
			{
				throw new DBSchemaException("Column '$key' is not defined in schema");
			}
			return NULL;
		}
		
		return (bool) $this->db->select('COUNT(*) AS count')
		                       ->where($this->get_prefix().$column, $value)
		                       ->get($this->get_table_name())
		                       ->row()->count;
	}
	
	/**
	 * -------------------------------------------------------------------------
	 * Getters and Setters for class variables
	 * ------------------------------------------------------------------------- 
	 */
	
	 /**
	 * Gets the column prefix
	 *
	 * @access public
	 * @return string
	 */
	public function get_prefix()
	{
		return $this->prefix;
	}
	
	/**
	 * Sets the prefix to prepend before column names
	 *
	 * @access public
	 * @param string The prefix
	 * @return void
	 */
	public function set_prefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	/**
	 * Gets the table name
	 *
	 * @access public
	 * @return string
	 */
	public function get_table_name()
	{
		if ($this->table === NULL)
		{
			$this->set_table_name($this->_get_table_name_from_class());
		}
		
		return $this->table;
	}
	
	/**
	 * Sets the name of the table
	 *
	 * @access public
	 * @param string The name of the table
	 * @return void
	 */
	public function set_table_name($name)
	{
		$this->table = $name;
	}
	
	/**
	 * Gets the name of primary key column
	 *
	 * @access public
	 * @return string
	 */
	public function get_primary_key()
	{
		if ($this->primary_key === NULL)
		{
			$this->set_primary_key($this->get_prefix().$this->_get_primary_key_from_schema());
		}
		
		return $this->primary_key;
	}
	
	/**
	 * Sets the primary key column name
	 *
	 * @access public
	 * @param string The name of the column
	 * @return void
	 */
	public function set_primary_key($pk)
	{
		$this->primary_key = $pk;
	}
	
	/**
	 * -------------------------------------------------------------------------
	 * Internal Functions
	 * ------------------------------------------------------------------------- 
	 */
	
	/**
	 * Finds the table name via the name of the child class
	 *
	 * @access private
	 * @return string
	 */
	private function _get_table_name_from_class()
	{
		return strtolower(substr(get_class($this), 0, strrpos(get_class($this), '_model')));
	}
	
	/**
	 * Finds the primary key column name via the schema
	 *
	 * @access private
	 * @return string
	 */
	private function _get_primary_key_from_schema()
	{		
		foreach($this->schema as $name => $definition)
		{
			if ($definition[2] & PK)
			{
				return $name;
			}
		}
		
		// Primary key not defined, try the default
		return 'id';
	}
	
	/**
	 * Runs callback function(s) before or after an operation
	 *
	 * @access private
	 * @param array List of function names to call
	 * @param array	The data passed
	 * @param mixed The where condition(s)
	 * @return array The data (modified or intact)
	 */
	private function _run_callback(array $callbacks, $data, $where = '')
	{
		foreach ($callbacks as $method)
		{
			$data = call_user_func_array(array($this, $method), array($data, $where));
		}
		
		return $data;
	}
	
	/**
	 * Defines constants to be used for validation with a schema.
	 * Only modify the datatypes if you are not using MySQL or you want to
	 * define some custom data types or aliases.
	 *
	 * @access private
	 * @return void
	 */
	private function _define_constants()
	{
		// Flags
		define('PK',			1);	// PRIMARY KEY
		define('NN', 			2); // NOT NULL
		define('UN', 			4); // UNSIGNED
		define('AI',			8); // AUTO-INCREMENT
		
		// Abstract data types
		define('ADT_STRING',	1);		// Data is a string
		define('ADT_NUMERIC',	2);		// Data is numeric
		define('ADT_DATE',		4);		// Data is a date and/or time
		
		// ADT Flags
		define('ADTF_VARLEN',	8);		// Data type allows arbitral length
		define('ADTF_SCALE',	16);	// Data type allows specifying scale (not fully handled yet)
		define('ADTF_ENUM',		32);	// Data type is an enumration
		
		// Powers of 2
		// Used to determine max length
		define('POW2_8',		64);	// 1 byte
		define('POW2_16',		128);	// 2 bytes
		define('POW2_24',		256);	// 3 bytes
		define('POW2_32',		512);	// 4 bytes
		define('POW2_64',		1024);	// 8 bytes
		
		// Datatypes (for MySQL)
		define('CHAR',		ADT_STRING + POW2_8 + ADTF_VARLEN);
		define('VARCHAR',	ADT_STRING + POW2_8 + ADTF_VARLEN);
		define('BINARY',	ADT_STRING + POW2_8 + ADTF_VARLEN);
		define('VARBINARY',	ADT_STRING + POW2_8 + ADTF_VARLEN);
		define('TINYBLOB',	ADT_STRING + POW2_8);
		define('TINYTEXT',	ADT_STRING + POW2_8);
		define('BLOB',		ADT_STRING + POW2_16);
		define('TEXT',		ADT_STRING + POW2_16);
		define('MEDIUMBLOB',ADT_STRING + POW2_24);
		define('MEDIUMTEXT',ADT_STRING + POW2_24);
		define('LONGBLOB',	ADT_STRING + POW2_32);
		define('LONGTEXT',	ADT_STRING + POW2_32);
		define('ENUM',		ADT_STRING + ADTF_ENUM);
		define('SET',		ADT_STRING + ADTF_ENUM);
		define('BOOL',		ADT_NUMERIC + POW2_8);
		define('BOOLEAN',	ADT_NUMERIC + POW2_8);
		define('TINYINT',	ADT_NUMERIC + POW2_8);
		define('SMALLINT',	ADT_NUMERIC + POW2_16);
		define('MEDIUMINT',	ADT_NUMERIC + POW2_24);
		define('INT',		ADT_NUMERIC + POW2_32 + ADTF_VARLEN);
		define('INTEGER',	ADT_NUMERIC + POW2_32 + ADTF_VARLEN);
		define('BIGINT',	ADT_NUMERIC + POW2_64);
		define('FLOAT',		ADT_NUMERIC + POW2_32);
		define('DOUBLE',	ADT_NUMERIC + POW2_64);
		define('DECIMAL',	ADT_NUMERIC + POW2_64 + ADTF_VARLEN + ADTF_SCALE);
		define('NUMERIC',	ADT_NUMERIC + POW2_64 + ADTF_VARLEN + ADTF_SCALE);
		define('BIT',		ADT_NUMERIC + ADTF_VARLEN);
		define('DATE',		ADT_DATE);
		define('TIME',		ADT_DATE);
		define('DATETIME',	ADT_DATE);
		define('TIMESTAMP',	ADT_DATE);
		define('YEAR',		ADT_DATE);
	}
	
	/**
	 * Validates table data (key/values pairs) against the schema, prepends
	 * the column prefix and finally defines any column that was undefined.
	 *
	 * @access private
	 * @param array The schema definitions
	 * @param array The key/value pairs
	 * @param bool Whether or not to add columns that are not in values but are in the schema
	 * @return array
	 */
	private function _prepare($schema, $values, $apply_defaults = FALSE)
	{
		// Is a schema defined?
		if (empty($schema))
		{
			// We can't do anything with an empty schema!
			if ($this->debug)
			{
				throw new DBSchemaException("Schema is not defined");
			}
			return FALSE;
		}
		
		// This is where we store what we will return
		$result = array();
		
		// Iterate through all key/value pairs
		foreach ($values as $key => $value)
		{
			// Does the key provided exist in the schema?
			if ( ! array_key_exists($key, $schema))
			{
				if ($this->debug)
				{
					throw new DBSchemaException("Column '$key' is not defined in schema");
				}
				return FALSE;
			}
			
			// Is it a primary key?
			if ($schema[$key][2] & PK)
			{
				if ($schema[$key][2] & AI)
				{
					if ( ! $schema[$key][0] & ADT_NUMERIC)
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Column '$key' cannot be auto-increment if its data type is not numeric");
						}
					}
					else
					{
						// Do not insert this column
						continue;
					}
				}
				else
				{
					// Is it null?
					if (is_null($value))
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Primary key value cannot be null for column $key");
						}
					}
				}
			}
			
			// Is the value null?
			if (is_null($value))
			{
				// Can the value be null?
				if ($schema[$key][2] & NN)
				{
					if ($this->debug)
					{
						throw new DBSchemaException("Value cannot be null for column $key");
					}
					return FALSE;
				}
			}
			// Check column data type
			else if ($schema[$key][0] & ADT_STRING)
			{
				// Is the value actually a string?
				if ( ! is_string($value))
				{
					if ($this->debug)
					{
						throw new DBSchemaException("Value for column $key must be a string");
					}
					return FALSE;
				}
				
				// Is the string empty?
				if (empty($value))
				{
					// Can we make it null instead?
					if ( ! ($schema[$key][2] & NN))
					{
						$value = NULL;
					}
					else
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Value for column $key cannot be empty");
						}
					}
				}
				// Is the data type of variable length? 
				else if ($schema[$key][0] & ADTF_VARLEN)
				{
					// Does the schema define the length properly?
					if ( ! is_numeric($schema[$key][1]))
					{
						// Nope, use the default for the data type
						$max = 256;
						if ($schema[$key][0] & POW2_8)
							$max = pow(2, 8);
						else if ($schema[$key][0] & POW2_16)
							$max = pow(2, 16);
						else if ($schema[$key][0] & POW2_24)
							$max = pow(2, 24);
						else if ($schema[$key][0] & POW2_32)
							$max = pow(2, 32);
						else if ($schema[$key][0] & POW2_64)
							$max = pow(2, 64);
						
						// Is the string too long?
						if (strlen($value) >= $max)
						{
							if ($this->debug)
							{
								throw new DBSchemaException("Value for column $key exceeds maximum length for its datatype");
							}
							return FALSE;
						}
					}
					// Then use it to check if the string is too long for this data type
					else if (strlen($value) > $schema[$key][1])
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Value for column $key exceeds maximum length specified in schema");
						}
						return FALSE;
					}
				}
				// Is it an enumeration of values?
				else if ($schema[$key][0] & ADTF_ENUM)
				{
					// Does the schema enumerate the possible values for this column?
					if ( ! is_array($schema[$key][1]))
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Schema definition for $key does not enumerate possible values");
						}
						return FALSE;
					}
					// Is the value given present in the schema definition?
					else if ( ! in_array($value, $schema[$key][1]))
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Enum value given for column $key is not in schema");
						}
						return FALSE;
					}
				}
				// Then we just make sure the length is good
				else
				{
					// Is the data type of variable length? Is it also well defined?
					if (($schema[$key][0] & ADTF_VARLEN) AND is_numeric($schema[$key][1]))
					{
						if (strlen($value) > $schema[$key][1])
						{
							if ($this->debug)
							{
								throw new DBSchemaException("Value for column $key exceeds maximum length specified in schema");
							}
							return FALSE;
						}
					}
					else // Just use the default maximum length for the data type
					{
						$max = 256;
						if ($schema[$key][0] & POW2_8)
							$max = pow(2, 8);
						else if ($schema[$key][0] & POW2_16)
							$max = pow(2, 16);
						else if ($schema[$key][0] & POW2_24)
							$max = pow(2, 24);
						else if ($schema[$key][0] & POW2_32)
							$max = pow(2, 32);
						else if ($schema[$key][0] & POW2_64)
							$max = pow(2, 64);
							
						if (strlen($value) >= $max)
						{
							if ($this->debug)
							{
								throw new DBSchemaException("Value for column $key exceeds maximum length for its datatype");
							}
							return FALSE;
						}
					}
				}
			}
			else if ($schema[$key][0] & ADT_NUMERIC)
			{
				// Is the value given numeric?
				if ( ! is_numeric($value))
				{
					if ($this->debug)
					{
						throw new DBSchemaException("Value is not numeric for numeric column $key");
					}
					return FALSE;
				}
				// Is the column unsigned?
				if ($schema[$key][2] & UN)
				{
					// Then is the value non-negative?
					if ($value < 0)
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Value cannot be negative for unsigned column $key");
						}
						return FALSE;
					}
				}
				// Is the data type of variable length? Is it also well defined?
				if (($schema[$key][0] & ADTF_VARLEN) AND is_numeric($schema[$key][1]))
				{
					if (($schema[$key][0] & ADTF_SCALE))
					{
						if ( ! is_float($schema[$key][1]))
						{
							if ($this->debug)
							{
								throw new DBSchemaException("Schema is not using a float value for specifying precision and scale for the column $key");
							}
							return FALSE;
						}
						// else [TODO: check if float or double value will be truncated]
					}
					else if ($value > $schema[$key][1])
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Value for column $key exceeds maximum length specified in schema");
						}
						return FALSE;
					}
				}
				else // Just use the default maximum length for the data type
				{
					// Just check the max value now
					$max = 256;
					if ($schema[$key][0] & POW2_8)
						$max = pow(2, 8);
					else if ($schema[$key][0] & POW2_16)
						$max = pow(2, 16);
					else if ($schema[$key][0] & POW2_24)
						$max = pow(2, 24);
					else if ($schema[$key][0] & POW2_32)
						$max = pow(2, 32);
					else if ($schema[$key][0] & POW2_64)
						$max = pow(2, 64);
						
					if ($value >= $max)
					{
						if ($this->debug)
						{
							throw new DBSchemaException("Value for column $key exceeds maximum length for its datatype");
						}
						return FALSE;
					}
				}
			}
			else if ($schema[$key][0] & ADT_DATE)
			{
				if (FALSE === strtotime($value))
				{
					if ($this->debug)
					{
						throw new DBSchemaException("Value for column $key is not a correct date/time");
					}
					return FALSE;
				}
			}
			
			$result[$this->get_prefix().$key] = $value;
		}
		
		if ($apply_defaults)
		{
			$defaults = array();
			
			// Fill the result array with defaults for values that are undefined
			foreach ($schema as $key => $definition)
			{
				if ( ! array_key_exists($this->get_prefix().$key, $result))
				{
					$defaults[$key] = $schema[$key][3];
				}
			}
			
			// Send it back through this method to make sure they are also conform
			$defaults = $this->_prepare($schema, $defaults, FALSE);
			
			if (FALSE !== $defaults)
			{
				return array_merge($defaults, $result);
			}
		}
		
		return $result;
	}
	
	
	
	
// some crud functionality
function findAll($conditions = NULL, $fields = '*', $order = NULL, $start = 0, $limit = NULL) 
{
	if ($conditions != NULL) 
	{ 
		$this->db->where($conditions);
	}

	if ($fields != NULL) 
	{ 
		$this->db->select($fields);
	}
	
	if ($order != NULL) 
	{ 
		$this->db->order_by($order);
	}
	
	if ($limit != NULL) 
	{ 
		$this->db->limit($limit, $start);
	}
	
	$query = $this->db->get($this->table);
	$this->__numRows = $query->num_rows();
	
	if ($this->debug)
	{
		$this->queries[] = $this->db->last_query();
	}
	
	return ($this->returnArray) ? $query->result_array() : $query->result();
}
function find($conditions = NULL, $fields = '*', $order = 'id ASC') 
{
	$data = $this->findAll($conditions, $fields, $order, 0, 1);
	
	if ($data) 
	{
		return $data[0];
	} 
	else 
	{
		return false;
	}
}
function field($conditions = null, $name, $fields = '*', $order = 'id ASC') 
{
	$data = $this->findAll($conditions, $fields, $order, 0, 1);
	
	if ($data) 
	{	
		$row = $data[0];
		
		if (isset($row[$name])) 
		{
			return $row[$name];
		} 
		else 
		{
			return false;
		}	
	} 
	else 
	{	
		return false;	
	}
	
}
function findCount($conditions = null) 
{
	$data = $this->findAll($conditions, 'COUNT(*) AS count', null, 0, 1);
	
	if ($data) 
	{
		return $data[0]['count'];
	} 
	else 
	{
		return false;
	}
}
function generateList($conditions = null, $order = 'id ASC', $start = 0, $limit = NULL, $key = null, $value = null)
{
	$data = $this->findAll($conditions, "$key, $value", $order, $start, $limit);

	if ($data) 
	{
		foreach ($data as $row) 
		{
			$keys[] = ($this->returnArray) ? $row[$key] : $row->$key;
			$vals[] = ($this->returnArray) ? $row[$value] : $row->$value;
		}
			
		if (!empty($keys) && !empty($vals)) 
		{
			$return = array_combine($keys, $vals);
			return $return;
		}			
	} 
	else 
	{
		return false;
	}
}
function generateSingleArray($conditions = null, $field = null, $order = 'id ASC', $start = 0, $limit = NULL) 
{
	$data = $this->findAll($conditions, "$field", $order, $start, $limit);

	if ($data) 
	{	
		foreach ($data as $row) 
		{
			$arr[] = ($this->returnArray) ? $row[$field] : $row->$field;
		}
		
		return $arr;
	} 
	else 
	{
		return false;
	}
}
// function create() 
// {
// 	$this->id = false;
// 	unset ($this->data);
// 	
// 	$this->data = array();
// 	return true;
// }	
function read($id = null, $fields = null) 
{
	if ($id != null) 
	{
		$this->id = $id;
	}

	$id = $this->id;

	if ($this->id !== null && $this->id !== false) 
	{
		$this->data = $this->find($this->primary_key . ' = ' . $id, $fields);
		return $this->data;	
	} 
	else 
	{
		return false;	
	}
}
function insert($data = null) 
{
	if ($data == null)
	{
		return FALSE;
	}
	
	$this->data = $data;
	$this->data['create_date'] = date("Y-m-d H:i:s");
	
	foreach ($this->data as $key => $value) 
	{
		if (array_search($key, $this->fields) === FALSE) 
		{
			unset($this->data[$key]);
		}
	}

	$this->db->insert($this->table, $this->data); 
	
	if ($this->debug)
	{
		$this->queries[] = $this->db->last_query();
	}
	
	$this->__insertID = $this->db->insert_id();
	return $this->__insertID;
}	
function save($id = null, $data = null) 
{
	if ($data) 
	{
		$this->data = $data;
	}
			
	foreach ($this->data as $key => $value) 
	{
		if (array_search($key, $this->fields) === FALSE) 
		{
			unset($this->data[$key]);
		}
	}

	if ($id != null) 
	{
		$this->id = $id;
	}

	$id = $this->id;

	if ($this->id !== null && $this->id !== false) 
	{	
		$this->db->where($this->primary_key, $id);
		$this->db->update($this->table, $this->data);
		
		if ($this->debug)
		{
			$this->queries[] = $this->db->last_query();
		}
		
		$this->__affectedRows = $this->db->affected_rows(); 
		return $this->id;			
	} 
	else 
	{
		$this->db->insert($this->table, $this->data); 
		
		if ($this->debug)
		{
			$this->queries[] = $this->db->last_query();
		}
		
		$this->__insertID = $this->db->insert_id();
		return $this->__insertID;
	}
}	
function remove($id = null) 
{
	if ($id != null) 
	{
		$this->id = $id;
	}

	$id = $this->id;

	if ($this->id !== null && $this->id !== false) 
	{	
		if ($this->db->delete($this->table, array($this->primary_key => $id))) 
		{
			$this->id = null;
			$this->data = array();
			
			if ($this->debug)
			{
				$this->queries[] = $this->db->last_query();
			}
			
			return true;	
		} 
		else 
		{	
			return false;	
		}
	} 
	else 
	{
		return false; 	
	}
}	
function query($sql) 
{
	$ret = $this->db->query($sql);
	
	if ($this->debug)
	{
		$this->queries[] = $this->db->last_query();
	}
	
	return $ret;
}
function lastQuery() 
{
	return $this->db->last_query();
}
function debugQueries() 
{
	$queries = array_reverse($this->queries);
	return $queries;
}		
function insertString($data) 
{
	return $this->db->insert_string($this->table, $data);
}
function getID() 
{
	return $this->id;
}
function getInsertID() 
{
	return $this->__insertID;
}
function getNumRows() 
{
	return $this->__numRows;
}
function getAffectedRows() 
{
	return $this->__affectedRows;
}
	
}
/* End of file MY_Model.php */