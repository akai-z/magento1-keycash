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

/**
 * Block used to render verification state column values (as images)
 *
 * @category    Keycash
 * @package     Keycash_Core
 */
class Keycash_Core_Block_Adminhtml_Sales_Order_Grid_Column_Renderer_Verificationstate
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
    /**
     * Renders grid column
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = '';
        $status = $row->getData($this->getColumn()->getIndex());

        if (!$status) {
            return $html;
        }

        $verificationStates = Mage::getModel(
            'keycash_core/source_order_verification_state'
        )->toOptionArray(true);

        if (isset($verificationStates[$status]['icon'])) {
            $html = '<img src="'
                  . $this->getSkinUrl($verificationStates[$status]['icon'])
                  . '" alt="' . $verificationStates[$status]['label']
                  . '" title="' . $verificationStates[$status]['label'] . '"/>';
        }

        return $html;
    }
}
