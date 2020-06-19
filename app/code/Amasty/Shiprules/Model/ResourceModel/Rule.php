<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Shiprules\Model\ResourceModel;

class Rule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_shiprules_rule', 'rule_id');
    }

    public function massChangeStatus($ids, $status)
    {
        $db = $this->getConnection();
        $ids = array_map('intval', $ids);
        $db->update($this->getMainTable(),
            array('is_active' => $status), 'rule_id IN(' . implode(',', $ids) . ') ');

        return true;
    }

    /**
     * Return codes of all product attributes currently used in promo rules
     *
     * @return array
     */
    public function getAttributes()
    {
        $db = $this->getConnection();
        $tbl   = $this->getTable('amasty_shiprules_attribute');

        $select = $db->select()->from($tbl, new \Zend_Db_Expr('DISTINCT code'));
        return $db->fetchCol($select);
    }

    /**
     * Save product attributes currently used in conditions and actions of the rule
     *
     * @param int $id rule id
     * @param mixed $attributes
     * return $this
     */
    public function saveAttributes($id, $attributes)
    {
        $db = $this->getConnection();
        $tbl   = $this->getTable('amasty_shiprules_attribute');

        $db->delete($tbl, array('rule_id=?' => $id));

        $data = array();
        foreach ($attributes as $code){
            $data[] = array(
                'rule_id' => $id,
                'code'    => $code,
            );
        }
        $db->insertMultiple($tbl, $data);

        return $this;
    }
}
