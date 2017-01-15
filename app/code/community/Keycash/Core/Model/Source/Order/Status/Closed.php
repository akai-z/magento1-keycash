<?php

class Keycash_Core_Model_Source_Order_Status_Closed
{
    protected $closedStatuses = array(
        'acceptable' => array(
            Mage_Sales_Model_Order::STATE_COMPLETE,
            Mage_Sales_Model_Order::STATE_CLOSED
        ),
        'unacceptable' => array(
            Mage_Sales_Model_Order::STATE_CANCELED,
            Mage_Sales_Model_Order::STATUS_FRAUD
        )
    );

    public function toOptionArray($flat = false)
    {
        $options = $this->closedStatuses;

        if ($flat) {
            $options = array_merge(
                $options['acceptable'],
                $options['unacceptable']
            );
        }

        return $options;
    }

    public function getAcceptableStatuses()
    {
        return $this->closedStatuses['acceptable'];
    }

    public function getUnacceptableStatuses()
    {
        return $this->closedStatuses['unacceptable'];
    }
}
