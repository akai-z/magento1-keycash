<?php
/**
 * KeyCash
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Keycash
 * @package     Keycash_Core
 * @copyright   Copyright (c) 2017 KeyCash. (https://keycash.co)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Keycash_Core_Model_Source_Order_Status_Closed
{
    /**
     * KeyCash acceptable and unacceptable order closed statuses
     *
     * @var array
     */
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

    /**
     * @param bool $flat
     * @return array
     */
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

    /**
     * @return array
     */
    public function getAcceptableStatuses()
    {
        return $this->closedStatuses['acceptable'];
    }

    /**
     * @return array
     */
    public function getUnacceptableStatuses()
    {
        return $this->closedStatuses['unacceptable'];
    }
}
