<?php

class Keycash_Core_Block_Adminhtml_Sales_Order_Grid_Column_Renderer_Verificationstate
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{
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
                  . '" alt="' . $verificationStates[$status]['label'] . '"/>';
        }

        return $html;
    }
}
