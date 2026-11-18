<?php declare(strict_types=1);

namespace Abeta\PunchOut\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Dynamic rows field: master account email => allowed buyer email domains
 */
class BuyerDomains extends AbstractFieldArray
{

    /**
     * @inheritDoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn('master_email', [
            'label' => __('Master Account Email'),
            'class' => 'required-entry validate-email',
        ]);
        $this->addColumn('allowed_domains', [
            'label' => __('Allowed Buyer Domains'),
            'class' => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Master Account');
    }
}
