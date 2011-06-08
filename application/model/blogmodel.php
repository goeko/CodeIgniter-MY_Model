<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
class BlogModel extends MY_Model {

	function BlogModel()
	{
		parent::MY_Model();
		log_message('debug', 'BlogModel Initialized');
		// load this first
		// $this->loadTable('blog', 1, 'default');
		$fields = array('id',
		'free',
		'title',
		'contents',
		'date');
		$this->loadTable('blog', $fields, 'default');
	}

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

}
?>