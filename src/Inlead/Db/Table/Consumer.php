<?php
/**
 * Table Definition for comments
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2012.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Inlead\Db\Table;

use Laminas\Db\Adapter\Adapter;
use Inlead\Db\Row\RowGateway;
use VuFind\Db\Table\DbTableAwareTrait;

/**
 * Table Definition for comments
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class Consumer extends Gateway
{
    use DbTableAwareTrait;

    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(Adapter $adapter, PluginManager $tm, $cfg,
        ?RowGateway $rowObj = null, $table = 'inlead_consumer'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * @inheritDoc
     */
    public function getDbTable($table)
    {
        return $this->getDbTableManager()->get($table);
    }

    /**
     * @return \Laminas\Db\ResultSet\ResultSetInterface|null
     */
    public function getAllConsumers()
    {
        return $this->select();
    }

    /**
     * @param $id
     * @return \Laminas\Db\ResultSet\ResultSetInterface|null
     */
    public function getConsumer($id)
    {
        return $this->select(['id' => $id]);
    }

    /**
     * @param $consumer
     * @return \Laminas\Db\ResultSet\ResultSetInterface|null
     */
    public function createConsumer($consumer)
    {
        $this->insert((array) $consumer);
        return $this->getConsumer($this->getLastInsertValue());
    }

    /**
     * @param $id
     * @param $consumer
     * @return \Laminas\Db\ResultSet\ResultSetInterface|null
     */
    public function updateConsumer($id, $consumer)
    {
        $this->update((array) $consumer, ['id' => $id]);
        return $this->getConsumer($id);
    }

    /**
     * @param $id
     */
    public function deleteConsumer($id)
    {
        $this->delete(['id' => $id]);
    }
}
