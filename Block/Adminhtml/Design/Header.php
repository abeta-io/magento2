<?php declare(strict_types=1);

namespace Abeta\PunchOut\Block\Adminhtml\Design;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * System Configuration Module information Block
 */
class Header extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Abeta_PunchOut::system/config/fieldset/header.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->addClass('abeta');
        return $this->toHtml();
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink(): string
    {
        return '';
    }
}
