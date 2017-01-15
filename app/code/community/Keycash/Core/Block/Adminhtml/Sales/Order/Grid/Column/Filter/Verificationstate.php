<?php

class Keycash_Core_Block_Adminhtml_Sales_Order_Grid_Column_Filter_Verificationstate
    extends Mage_Adminhtml_Block_Abstract
{
    public function filterOrderByVerificationState($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        $verificationStates = Mage::getModel(
            'keycash_core/source_order_verification_state'
        )->getFlatOptions();

        if (!isset($verificationStates[$value])) {
            $value = Keycash_Core_Model_Source_Order_Verification_State::UNVERIFIED;
        }

        $keycashOrderTable = Mage::getSingleton('core/resource')
            ->getTableName('keycash_core/keycash_order');

        if ($value == Keycash_Core_Model_Source_Order_Verification_State::VERIFIED) {
            $collection->getSelect()->where(
                $keycashOrderTable . '.verification_state = ?', $value
            );
        } else {
            $collection->getSelect()->where(
                $keycashOrderTable . '.verification_state <> ?',
                Keycash_Core_Model_Source_Order_Verification_State::VERIFIED
            );
        }

        $canceledOrderStatuses = Mage::getModel(
            'keycash_core/source_order_status_closed'
        )->getUnacceptableStatuses();

        $customCanceledOrderStatuses =  Mage::helper('keycash_core')
            ->getCustomCanceledOrderStatuses();

        if ($customCanceledOrderStatuses) {
            $canceledOrderStatuses = array_unique(
                array_merge(
                    $canceledOrderStatuses,
                    $customCanceledOrderStatuses
                )
            );
        }

        $collection->getSelect()->where(
            'main_table.status NOT IN (?)',
            $canceledOrderStatuses
        );

        return $collection;
    }
}
