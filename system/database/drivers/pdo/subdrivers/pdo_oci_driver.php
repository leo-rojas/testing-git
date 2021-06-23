<?php
/**
 * CodeIgniter.
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 *
 * @link	http://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PDO Oracle Database Adapter Class.
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the query builder
 * class is being used or not.
 *
 * @category	Database
 *
 * @author		EllisLab Dev Team
 *
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_pdo_oci_driver extends CI_DB_pdo_driver
{
    /**
     * Sub-driver.
     *
     * @var string
     */
    public $subdriver = 'oci';

    // --------------------------------------------------------------------

    /**
     * List of reserved identifiers.
     *
     * Identifiers that must NOT be escaped.
     *
     * @var string[]
     */
    protected $_reserved_identifiers = ['*', 'rownum'];

    /**
     * ORDER BY random keyword.
     *
     * @var array
     */
    protected $_random_keyword = ['ASC', 'ASC']; // Currently not supported

    /**
     * COUNT string.
     *
     * @used-by	CI_DB_driver::count_all()
     * @used-by	CI_DB_query_builder::count_all_results()
     *
     * @var string
     */
    protected $_count_string = 'SELECT COUNT(1) AS ';

    // --------------------------------------------------------------------

    /**
     * Class constructor.
     *
     * Builds the DSN if not already set.
     *
     * @param array $params
     *
     * @return void
     */
    public function __construct($params)
    {
        parent::__construct($params);

        if (empty($this->dsn)) {
            $this->dsn = 'oci:dbname=';

            // Oracle has a slightly different PDO DSN format (Easy Connect),
            // which also supports pre-defined DSNs.
            if (empty($this->hostname) && empty($this->port)) {
                $this->dsn .= $this->database;
            } else {
                $this->dsn .= '//'.(empty($this->hostname) ? '127.0.0.1' : $this->hostname)
                    .(empty($this->port) ? '' : ':'.$this->port).'/';

                empty($this->database) or $this->dsn .= $this->database;
            }

            empty($this->char_set) or $this->dsn .= ';charset='.$this->char_set;
        } elseif (!empty($this->char_set) && strpos($this->dsn, 'charset=', 4) === false) {
            $this->dsn .= ';charset='.$this->char_set;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Show table query.
     *
     * Generates a platform-specific query string so that the table names can be fetched
     *
     * @param bool $prefix_limit
     *
     * @return string
     */
    protected function _list_tables($prefix_limit = false)
    {
        $sql = 'SELECT "TABLE_NAME" FROM "ALL_TABLES"';

        if ($prefix_limit === true && $this->dbprefix !== '') {
            return $sql.' WHERE "TABLE_NAME" LIKE \''.$this->escape_like_str($this->dbprefix)."%' "
                .sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Show column query.
     *
     * Generates a platform-specific query string so that the column names can be fetched
     *
     * @param string $table
     *
     * @return string
     */
    protected function _list_columns($table = '')
    {
        if (strpos($table, '.') !== false) {
            sscanf($table, '%[^.].%s', $owner, $table);
        } else {
            $owner = $this->username;
        }

        return 'SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS
			WHERE UPPER(OWNER) = '.$this->escape(strtoupper($owner)).'
				AND UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));
    }

    // --------------------------------------------------------------------

    /**
     * Returns an object with field data.
     *
     * @param string $table
     *
     * @return array
     */
    public function field_data($table)
    {
        if (strpos($table, '.') !== false) {
            sscanf($table, '%[^.].%s', $owner, $table);
        } else {
            $owner = $this->username;
        }

        $sql = 'SELECT COLUMN_NAME, DATA_TYPE, CHAR_LENGTH, DATA_PRECISION, DATA_LENGTH, DATA_DEFAULT, NULLABLE
			FROM ALL_TAB_COLUMNS
			WHERE UPPER(OWNER) = '.$this->escape(strtoupper($owner)).'
				AND UPPER(TABLE_NAME) = '.$this->escape(strtoupper($table));

        if (($query = $this->query($sql)) === false) {
            return false;
        }
        $query = $query->result_object();

        $retval = [];
        for ($i = 0, $c = count($query); $i < $c; $i++) {
            $retval[$i] = new stdClass();
            $retval[$i]->name = $query[$i]->COLUMN_NAME;
            $retval[$i]->type = $query[$i]->DATA_TYPE;

            $length = ($query[$i]->CHAR_LENGTH > 0)
                ? $query[$i]->CHAR_LENGTH : $query[$i]->DATA_PRECISION;
            if ($length === null) {
                $length = $query[$i]->DATA_LENGTH;
            }
            $retval[$i]->max_length = $length;

            $default = $query[$i]->DATA_DEFAULT;
            if ($default === null && $query[$i]->NULLABLE === 'N') {
                $default = '';
            }
            $retval[$i]->default = $query[$i]->COLUMN_DEFAULT;
        }

        return $retval;
    }

    // --------------------------------------------------------------------

    /**
     * Insert batch statement.
     *
     * @param string $table  Table name
     * @param array  $keys   INSERT keys
     * @param array  $values INSERT values
     *
     * @return string
     */
    protected function _insert_batch($table, $keys, $values)
    {
        $keys = implode(', ', $keys);
        $sql = "INSERT ALL\n";

        for ($i = 0, $c = count($values); $i < $c; $i++) {
            $sql .= '	INTO '.$table.' ('.$keys.') VALUES '.$values[$i]."\n";
        }

        return $sql.'SELECT * FROM dual';
    }

    // --------------------------------------------------------------------

    /**
     * Delete statement.
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param string $table
     *
     * @return string
     */
    protected function _delete($table)
    {
        if ($this->qb_limit) {
            $this->where('rownum <= ', $this->qb_limit, false);
            $this->qb_limit = false;
        }

        return parent::_delete($table);
    }

    // --------------------------------------------------------------------

    /**
     * LIMIT.
     *
     * Generates a platform-specific LIMIT clause
     *
     * @param string $sql SQL Query
     *
     * @return string
     */
    protected function _limit($sql)
    {
        return 'SELECT * FROM (SELECT inner_query.*, rownum rnum FROM ('.$sql.') inner_query WHERE rownum < '.($this->qb_offset + $this->qb_limit + 1).')'
            .($this->qb_offset ? ' WHERE rnum >= '.($this->qb_offset + 1) : '');
    }
}
