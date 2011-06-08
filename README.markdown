CodeIgniter-MY_Model
====================
Replaces CodeIgniter's default model and adds CRUD helper functions along with
validation against a schema.

Example
-------
The following is an example of the schema you need to write for each table/model
in your application. Since this custom class provides the basic CRUD functions
such as create() you do not need to define these for every model and eliminates
repeated code. The CRUD functions are all validated against the schema you write
so that if you inadvertly forget to pass a value for a required (not null)
column, a catachable exception will be thrown with a clear message why the query
was rejected, all before the query is even sent to the database. Other
validations include data type checking, protection against string truncation,
conversion from empty string to NULL (when applicable).

	class User_model extends MY_Model 
	{	
		function __construct()
		{
			parent::MY_Model();
			
			$this->schema = array( //       < DATATYPE  | LENGTH | FLAGS  | DEFAULT >
				'id'				=> array(INT		, NULL	, PK+AI+UN,	NULL),
				'username'			=> array(VARCHAR	, 45	, NN,		NULL),
				'password'			=> array(CHAR		, 40	, NN,		NULL),
				'salt'				=> array(CHAR		, 40	, NN,		NULL),
				'email'				=> array(VARCHAR	, NULL	, NN,		NULL),
				'realname'			=> array(VARCHAR	, NULL	, NULL,		NULL),
				'date_joined'		=> array(DATETIME	, NULL	, NN,		$this->now()),
				'last_login'		=> array(DATETIME	, NULL	, NN,		$this->now()),
				'active'			=> array(TINYINT	, 1		, NN,		1)
			);
			
			$this->before_create[] = '_prepare_password';
			$this->before_update[] = '_prepare_password';
		}
	}

Requirements
------------
* CodeIgniter 2.0
* Schema definitions for each model


Model: Blog Example
-------------------

	class BlogModel extends MY_Model
	{
	function BlogModel()
	{
		parent::MY_Model();
		log_message('debug', 'BlogModel Initialized');
		
		$this->schema = array( //   < DATATYPE  | LENGTH | FLAGS  | DEFAULT >
		            'id'            => array(INT        , NULL  , PK+AI+UN, NULL),
		            'free'          => array(TINYINT	, 1		, NN,		1),
		            'title'         => array(VARCHAR	, 72	, NN,		NULL),
		            'contents'      => array(VARCHAR    , 255    , NN,       NULL),
		            'date'          => array(DATETIME	, NULL	, NN,		$this->now()),
		            'create_date'          => array(DATETIME	, NULL	, NN,		$this->now()) // auto implemented
		            // NEED: auto implemented update_date
		        );
		
		foreach ($this->schema as $key) {
			$fields[] = $key;
		}
		$this->loadTable('blog', $fields, 'default');
		
		// OR
		// load this first
		/**
		 * $this->loadTable('blog', 1, 'default');
		 */
		// and add this next
		/**
		 * $fields = array('id',
		 * 'free',
		 * 'title',
		 * 'contents',
		 * 'date');
		 * $this->loadTable('blog', $fields, 'default');
		 */
		
		// OR
		// AUTOLOAD TABLE FIELDS
		/**
		 *
		 * $this->loadTable('blog', 0, 'default');
		 *
		 */
	}

Model: add your own function
----------------------------
	function findAllBlog($free, $start = 0, $limit = NULL)
	{
		$sql = "SELECT DATE_FORMAT(blog.date, '%d.%m.%Y') AS date,
			blog.date AS date_sort,
			blog.contents, 
			blog.title, 
			blog.free,
			blog.id
		FROM blog
		WHERE blog.id > 0";
		// WHERE blog.".$this->primary_key." > 0";
		$sql .= ($free==1) ? " AND blog.free = 1 ":"";
		$sql .= " ORDER BY date_sort DESC";
		$sql .= ($start>0) ? " LIMIT $start, $limit" : " LIMIT $limit";
		
		$query = $this->db->query($sql);
	   if ($query->num_rows() > 0) {
	      foreach ($query->result_array() as $row)      // Go through the result set
	      {
				$query_results['id']		 = $row['id'];
				$query_results['free']	 = $row['free'];
				$query_results['date']	 = $row['date'];
				$query_results['title']	 = $row['title'];
				$query_results['contents']		 = $row['contents'];
				$results[]		 = $query_results;
	      }
	   }
	   return $results;
	}