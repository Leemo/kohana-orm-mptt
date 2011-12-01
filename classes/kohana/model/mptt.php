<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Modified Preorder Tree Traversal Class.
 *
 * @author     Alexey Poopov
 * @author     Kiall Mac Innes
 * @author     Mathew Davies
 * @author     Mike Parkin
 * @copyright  (c) 2008-2011
 * @package    MODEL_MPTT
 */
abstract class Kohana_Model_MPTT extends ORM
{
	/**
	 * Left column name
	 *
	 * @var string
	 */
	protected $_left_column = 'lft';

	/**
	 * Right column name
	 *
	 * @var string
	 */
	protected $_right_column = 'rgt';

	/**
	 * Level column name
	 *
	 * @var string
	 */
	protected $_level_column = 'lvl';

	/**
	 * Parent column name
	 *
	 * @var string
	 */
	protected $_parent_column = 'parent';

	/**
	 * Scope column name
	 *
	 * @var string
	 */
	protected $_scope_column = 'scope';

	/**
	 * Enable/Disable path calculation
	 *
	 * @var bool
	 */
	protected $_path_calculation_enabled = FALSE;

	/**
	 * Full pre-calculated path column
	 *
	 * @var string
	 */
	protected $_path_column = 'path';

	/**
	 * Single path element
	 *
	 * @var string
	 */
	protected $_path_part_column = 'part';

	/**
	 * Path separator
	 *
	 * @var string
	 */
	protected $_path_separator = '/';

	/**
	 * New scope
	 * This also double as a new_root method allowing
	 * us to store multiple trees in the same table.
	 *
	 * @param   mixed    New scope to create
	 * @param   array    Additional fields
	 * @return  boolean
	 */
	public function new_root($scope, array $fields = array())
	{
		// Make sure the specified scope doesn't already exist.
		$root = ORM::factory($this->_object_name)
			->root($scope);

		if ($root instanceof $this AND $root->loaded())
		{
			return $root;
		}

		// Create a new root node in the new scope.
		$fields = Arr::merge($fields, array(
			$this->_left_column   => 1,
			$this->_right_column  => 2,
			$this->_level_column  => 0,
			$this->_parent_column => 0,
			$this->_scope_column  => $scope
			));

		// Other fields may be required.
		return $this
			->values($fields)
			->create();
	}

	/**
	 * Locks table.
	 *
	 * @access private
	 */
	protected function lock()
	{
		// Todo: Make it as it should be
		return $this;
	}

	/**
	 * Unlock table.
	 */
	protected function unlock()
	{
		// Todo: Make it as it should be
		return $this;
	}

	/**
	 * Does the current node have children?
	 *
	 * @return bool
	 */
	public function has_children()
	{
		return (($this->{$this->_right_column} - $this->{$this->_left_column}) > 1);
	}

	/**
	 * Is the current node a leaf node?
	 *
	 * @return bool
	 */
	public function is_leaf()
	{
		return ! $this->has_children();
	}

	/**
	 * Is the current node a descendant of the supplied node.
	 *
	 * @param   Model_MPTT  Target node
	 * @return  bool
	 */
	public function is_descendant($target)
	{
		return ($this->{$this->_left_column} > $target->{$this->_left_column} AND $this->{$this->_right_column} < $target->{$this->_right_column} AND $this->{$this->_scope_column} = $target->{$this->_scope_column});
	}

	/**
	 * Is the current node a direct child of the supplied node?
	 *
	 * @param Model_MPTT $target Target
	 * @return bool
	 */
	public function is_child($target)
	{
		return ($this->parent->{$this->_primary_key} === $target->{$this->_primary_key});
	}

	/**
	 * Is the current node the direct parent of the supplied node?
	 *
	 * @access public
	 * @param Model_MPTT $target Target
	 * @return bool
	 */
	public function is_parent($target)
	{
		return ($this->{$this->_primary_key} === $target->parent->{$this->_primary_key});
	}

	/**
	 * Is the current node a sibling of the supplied node
	 *
	 * @access public
	 * @param Model_MPTT $target Target
	 * @return bool
	 */
	public function is_sibling($target)
	{
		if ($this->{$this->_primary_key} === $target->{$this->_primary_key})
			return FALSE;

		return ($this->parent->{$this->_primary_key} === $target->parent->{$this->_primary_key});
	}

	/**
	 * Is the current node a root node?
	 *
	 * @access public
	 * @return bool
	 */
	public function is_root()
	{
		return ($this->{$this->_left_column} === 1);
	}

	/**
	 * Returns the root node.
	 *
	 * @access protected
	 * @return Model_MPTT
	 */
	public function root($scope = NULL)
	{
		if ($scope === NULL AND $this->loaded())
		{
			$scope = $this->{$this->_scope_column};
		}
		elseif ($scope === NULL AND ! $this->loaded())
		{
			return FALSE;
		}

		return ORM::factory($this->_object_name)
			->where($this->_left_column, '=', 1)
			->where($this->_scope_column, '=', $scope)
			->find();
	}

	/**
	 * Returns the parent of the current node.
	 *
	 * @access public
	 * @return Model_MPTT
	 */
	public function parent()
	{
		return $this->parents()->where($this->_level_column, '=', $this->{$this->_level_column} - 1);
	}

	/**
	 * Returns the parents of the current node.
	 *
	 * @param   bool        Include the root node?
	 * @param   string      Direction to order the left column by.
	 * @return  Model_MPTT
	 */
	public function parents($root = TRUE, $direction = 'ASC')
	{
		$parents =  ORM::factory($this->_object_name)
			->where($this->_left_column, '<=', $this->{$this->_left_column})
			->where($this->_right_column, '>=', $this->{$this->_right_column})
			->where($this->_primary_key, '<>', $this->{$this->_primary_key})
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->order_by($this->_left_column, $direction);

		if ( ! $root)
		{
			$parents->where($this->_left_column, '!=', 1);
		}

		return $parents;
	}

	/**
	 * Returns the children of the current node.
	 *
	 * @param   bool       Include the current loaded node?
	 * @param   string     Direction to order the left column by.
	 * @return  Model_MPTT
	 */
	public function children($self = FALSE, $direction = 'ASC')
	{
		if ($self)
		{
			return $this->descendants($self, $direction)->where($this->_level_column, '<=', $this->{$this->_level_column} + 1)->where($this->_level_column, '>=', $this->{$this->_level_column});
		}

		return ORM::factory($this->_object_name)
			->where($this->_parent_column, '=', $this->pk())
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->order_by($this->_left_column);
	}

	/**
	 * Returns the descendants of the current node.
	 *
	 * @param   bool        Include the current loaded node?
	 * @param   string      Direction to order the left column by.
	 * @return  Model_MPTT
	 */
	public function descendants($self = FALSE, $direction = 'ASC')
	{
		$left_operator = $self ? '>=' : '>';
		$right_operator = $self ? '<=' : '<';

		return ORM::factory($this->_object_name)
			->where($this->_left_column, $left_operator, $this->{$this->_left_column})
			->where($this->_right_column, $right_operator, $this->{$this->_right_column})
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->order_by($this->_left_column, $direction);
	}

	/**
	 * Returns the siblings of the current node
	 *
	 * @param   bool       Include the current loaded node?
	 * @param   string     Direction to order the left column by.
	 * @return  Model_MPTT
	 */
	public function siblings($self = FALSE, $direction = 'ASC')
	{
		$parent = $this
			->parent
			->find();

		$siblings = ORM::factory($this->_object_name)
			->where($this->_left_column, '>', $parent->{$this->_left_column})
			->where($this->_right_column, '<', $parent->{$this->_right_column})
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->where($this->_level_column, '=', $this->{$this->_level_column})
			->order_by($this->_left_column, $direction);

		if ( ! $self)
		{
			$siblings->where($this->_primary_key, '<>', $this->{$this->_primary_key});
		}

		return $siblings;
	}

	/**
	 * Returns leaves under the current node.
	 *
	 * @access public
	 * @return Model_MPTT
	 */
	public function leaves()
	{
		return ORM::factory($this->_object_name)
			->where($this->_left_column, '=', Db::expr('('.$this->_right_column.' - 1)'))
			->where($this->_left_column, '>=', $this->{$this->_left_column})
			->where($this->_right_column, '<=', $this->{$this->_right_column})
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->order_by($this->_left_column, 'ASC');
	}

	/**
	 * Get Size
	 *
	 * @access protected
	 * @return integer
	 */
	protected function get_size()
	{
		return ($this->{$this->_right_column} - $this->{$this->_left_column}) + 1;
	}

	/**
	 * Create a gap in the tree to make room for a new node
	 *
	 * @param integer $start start position.
	 * @param integer $size the size of the gap (default is 2).
	 */
	protected function _create_space($start, $size = 2)
	{
		// Update the right values, then the left.
		DB::update($this->_table_name)
			->set(array($this->_right_column =>  DB::expr($this->_right_column.' + '.$size)))
			->where($this->_right_column, '>=', $start)
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->execute($this->_db);

		DB::update($this->_table_name)
			->set(array($this->_left_column => DB::expr($this->_left_column.' + '.$size)))
			->where($this->_left_column, '>=', $start)
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->execute($this->_db);
	}

	/**
	 * Closes a gap in a tree. Mainly used after a node has
	 * been removed.
	 *
	 * @param integer $start start position.
	 * @param integer $size the size of the gap (default is 2).
	 */
	protected function _delete_space($start, $size = 2)
	{
		// Update the left values, then the right.
		DB::update($this->_table_name)
			->set(array($this->_left_column => DB::expr($this->_left_column.' - '.$size)))
			->where($this->_left_column, '>', $start)
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->execute($this->_db);

		DB::update($this->_table_name)
			->set(array($this->_right_column => DB::expr($this->_right_column.' - '.$size)))
			->where($this->_right_column, '>', $start)
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->execute($this->_db);
	}

	/**
	 * Insert a node
	 */
	protected function _insert($target, $copy_left_from, $left_offset, $level_offset)
	{
		// Insert should only work on new nodes.. if its already it the tree it needs to be moved!
		if ($this->loaded())
			return FALSE;

		$this->lock();

		if ( ! $target instanceof $this)
		{
			$target = ORM::factory($this->_object_name, $target);
		}
		else
		{
			$target->reload(); // Ensure we're using the latest version of $target
		}

		$this->{$this->_left_column}   = $target->{$copy_left_from} + $left_offset;
		$this->{$this->_right_column}  = $this->{$this->_left_column} + 1;
		$this->{$this->_level_column}  = $target->{$this->_level_column} + $level_offset;
		$this->{$this->_parent_column} = $target->pk();
		$this->{$this->_scope_column}  = $target->{$this->_scope_column};

		$this->_create_space($this->{$this->_left_column});

		parent::save();

		if ($this->_path_calculation_enabled)
		{
			$this->update_path();
			parent::save();
		}

		$this->unlock();

		return $this;
	}

	/**
	 * Inserts a new node to the left of the target node.
	 *
	 * @access public
	 * @param Model_MPTT $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function insert_as_first_child($target)
	{
		return $this->_insert($target, $this->_left_column, 1, 1);
	}

	/**
	 * Inserts a new node to the right of the target node.
	 *
	 * @access public
	 * @param Model_MPTT $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function insert_as_last_child($target)
	{
		return $this->_insert($target, $this->_right_column, 0, 1);
	}

	/**
	 * Inserts a new node as a previous sibling of the target node.
	 *
	 * @access public
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function insert_as_prev_sibling($target)
	{
		return $this->_insert($target, $this->_left_column, 0, 0);
	}

	/**
	 * Inserts a new node as the next sibling of the target node.
	 *
	 * @access public
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function insert_as_next_sibling($target)
	{
		return $this->_insert($target, $this->_right_column, 1, 0);
	}

	/**
	 * Overloaded save method
	 *
	 * @param  Validation $validation Validation object
	 * @return Model_MPTT|bool
	 */
	public function save(Validation $validation = NULL)
	{
		if ($this->loaded() === TRUE)
			return parent::save($validation);

		return FALSE;
	}

	/**
	 * Removes a node and it's descendants.
	 *
	 * $usless_param prevents a strict error that breaks PHPUnit like hell!
	 * @param bool $descendants remove the descendants?
	 */
	public function delete($usless_param = NULL)
	{
		$this->lock()->reload();

		$result = DB::delete($this->_table_name)
			->where($this->_left_column, '>=', $this->{$this->_left_column})
			->where($this->_right_column, '<=', $this->{$this->_right_column})
			->where($this->_scope_column, '=', $this->{$this->_scope_column})
			->execute($this->_db);

		if ($result > 0)
		{
			$this->_delete_space($this->{$this->_left_column}, $this->get_size());
		}

		$this->unlock();
	}

	/**
	 * Overloads the select_list method to
	 * support indenting.
	 *
	 * @param string $key first table column.
	 * @param string $val second table column.
	 * @param string $indent character used for indenting.
	 * @return array
	 */
	public function select_list($key = NULL, $val = NULL, $indent = NULL)
	{
		if (is_string($indent))
		{
			if ($key === NULL)
			{
				// Use the default key
				$key = $this->_primary_key;
			}

			if ($val === NULL)
			{
				// Use the default value
				$val = $this->_primary_val;
			}

			$result = $this->load_result(TRUE);

			$array = array();
			foreach ($result as $row)
			{
				$array[$row->$key] = str_repeat($indent, $row->{$this->_level_column}).$row->$val;
			}

			return $array;
		}

		return parent::select_list($key, $val);
	}

	/**
	 * Move to First Child
	 *
	 * Moves the current node to the first child of the target node.
	 *
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function move_to_first_child($target)
	{
		return $this->move($target, TRUE, 1, 1, TRUE);
	}

	/**
	 * Move to Last Child
	 *
	 * Moves the current node to the last child of the target node.
	 *
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function move_to_last_child($target)
	{
		return $this->move($target, FALSE, 0, 1, TRUE);
	}

	/**
	 * Move to Previous Sibling.
	 *
	 * Moves the current node to the previous sibling of the target node.
	 *
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function move_to_prev_sibling($target)
	{
		return $this->move($target, TRUE, 0, 0, FALSE);
	}

	/**
	 * Move to Next Sibling.
	 *
	 * Moves the current node to the next sibling of the target node.
	 *
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @return Model_MPTT
	 */
	public function move_to_next_sibling($target)
	{
		return $this->move($target, FALSE, 1, 0, FALSE);
	}

	/**
	 * Move
	 *
	 * @param Model_MPTT|integer $target target node id or Model_MPTT object.
	 * @param bool $left_column use the left column or right column from target
	 * @param integer $left_offset left value for the new node position.
	 * @param integer $level_offset level
	 * @param bool allow this movement to be allowed on the root node
	 */
	protected function move($target, $left_column, $left_offset, $level_offset, $allow_root_target)
	{
		if (!$this->loaded())
			return FALSE;

		// Make sure we have the most upto date version of this AFTER we lock
		$this->lock()->reload();

		if ( ! $target instanceof $this)
		{
			$target = ORM::factory($this->_object_name, $target);

			if ( ! $target->loaded())
			{
				$this->unlock();
				return FALSE;
			}
		}
		else
		{
			$target->reload();
		}

		// Stop $this being moved into a descendant or disallow if target is root
		if ($target->is_descendant($this) OR ($allow_root_target === FALSE AND $target->is_root()))
		{
			$this->unlock();
			return FALSE;
		}

		$left_offset = ($left_column === TRUE ? $target->{$this->_left_column} : $target->{$this->_right_column}) + $left_offset;
		$level_offset = $target->{$this->_level_column} - $this->{$this->_level_column} + $level_offset;

		$size = $this->get_size();

		$this->_create_space($left_offset, $size);

		// if node is moved to a position in the tree "above" its current placement
		// then its lft/rgt may have been altered by create_space
		$this->reload();

		$offset = ($left_offset - $this->{$this->_left_column});

		// Update the values.
		$this->_db->query(Database::UPDATE, 'UPDATE '.$this->_table_name.' SET `'.$this->_left_column.'` = `'.$this->_left_column.'` + '.$offset.', `'.$this->_right_column.'` = `'.$this->_right_column.'` + '.$offset.'
		, `'.$this->_level_column.'` = `'.$this->_level_column.'` + '.$level_offset.'
		, `'.$this->_scope_column.'` = '.$target->{$this->_scope_column}.'
		WHERE `'.$this->_left_column.'` >= '.$this->{$this->_left_column}.' AND `'.$this->_right_column.'` <= '.$this->{$this->_right_column}.' AND `'.$this->_scope_column.'` = '.$this->{$this->_scope_column}, FALSE);

		$this->_delete_space($this->{$this->_left_column}, $size);


		if ($this->_path_calculation_enabled)
		{
			$this->update_path();
			parent::save();
		}

		$this->unlock();

		return $this;
	}

	/**
	 * Magic
	 *
	 * @param   $column - Which field to get.
	 * @return  mixed
	 */
	public function __get($column)
	{
		if (in_array($column, array('parent', 'parents', 'children', 'siblings', 'root', 'leaves', 'descendants')))
		{
			return call_user_func(array($this, $column));
		}

		return parent::__get($column);
	}

	/**
	 * Verify the tree is in good order
	 *
	 * This functions speed is irrelevant - its really only for debugging and unit tests
	 *
	 * @todo Look for any nodes no longer contained by the root node.
	 * @todo Ensure every node has a path to the root via ->parents();
	 * @return boolean
	 *
	public function verify_tree()
	{
		// Select all scopes
		foreach ($this->get_scopes() as $scope)
		{
			if ( ! $this->verify_scope($scope->{$this->_scope_column}))
			{
				return FALSE;
			}
		}

		return TRUE;
	}
*/
	/**
	 * Returns all available scopes
	 *
	 * @return array
	 */
	public function get_scopes()
	{
		return DB::select($this->_scope_column)
			->from($this->_table_name)
			->group_by($this->_scope_column)
			->execute($this->_db)
			->as_array();

		// TODO... redo this so its proper :P and open it public
		// used by verify_tree()
		//return $this->_db->query(Database::SELECT, 'SELECT DISTINCT(`'.$this->_scope_column.'`) from `'.$this->_table_name.'`', TRUE);
	}

	/**
	 * Verifying error
	 *
	 * @var array
	 */
	protected $_verify_error = array();

	public function verify()
	{
		if ( ! $this->loaded())
		{
			return FALSE;
		}

		$scope = $this->{$this->_scope_column};

		$root  = $this->root($scope);

		$end   = $root->{$this->_right_column};

		// Find nodes that have slipped out of bounds
		$result = DB::select(DB::expr('COUNT(*) AS nodes'))
			->from($this->_table_name)
			->where($this->_scope_column, '=', $scope)
			->where($this->_left_column, '>', $end)
			->where($this->_right_column, '>', $end)
			->execute($this->_db)
			->get('nodes');

		if ($result > 0)
		{
			$this->_verify_error = array
			(
				'title'       => __('Some nodes slipped out of bounds'),
				'description' => __('Some nodes slipped out of bounds')
			);

			return FALSE;
		}

		// Find nodes that have the same left and right value
		// and nodes that right value is less than the left value
		$result = DB::select(DB::expr('COUNT(*) AS nodes'))
			->from($this->_table_name)
			->where($this->_left_column, '>=', DB::expr($this->_right_column))
			->where($this->_scope_column, '=', $scope)
			->execute($this->_db)
			->get('nodes');

		if ($result > 0)
		{
			$this->_verify_error = array(
				'title'       => __('Disturbance of structure'),
				'description' => ''
			);

			return FALSE;
		}

		// Make sure no 2 nodes share a left/right value
		$i = 1;
		while ($i <= $end)
		{
			$result = DB::select(DB::expr('COUNT(*) AS nodes'))
				->from($this->_table_name)
				->or_where_open()
				->or_where($this->_left_column, '=', $i)
				->or_where($this->_right_column, '=', $i)
				->or_where_close()
				->where($this->_scope_column, '=', $scope)
				->execute($this->_db)
				->get('nodes');

			if ($result > 1)
			{
				$this->_verify_error = array(
					'title'       => __('Broken structure'),
					'description' => __('There are nodes with the same values of left or right parameters')
				);

				return FALSE;
			}

			$i++;
		}

		// Check to ensure that all nodes have a "correct" level
		//TODO

		return TRUE;
	}

	/**
	 * Returns verify error text
	 *
	 * @return array|bool
	 */
	public function verify_error()
	{
		if ( ! $this->loaded() OR empty($this->_verify_error))
		{
			return FALSE;
		}

		return $this->_verify_error;
	}

	public function update_path()
	{
		$path = '';

		$parents = $this
			->parents(FALSE)
			->find_all();

		foreach ($parents as $parent)
		{
			$path .= $this->_path_separator.trim($parent->{$this->_path_part_column});
		}

		$path .= $this->_path_separator.trim($this->{$this->_path_part_column});

		$path = trim($path, $this->_path_separator);

		$this->{$this->_path_column} = $path;

		return $this;
	}
}