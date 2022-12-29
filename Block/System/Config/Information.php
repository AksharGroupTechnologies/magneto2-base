<?php


namespace Agtech\Base\Block\System\Config;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Config\Block\System\Config\Form\Field
     */
    protected $_fieldRenderer;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\ModuleResource
     */
    private $moduleResource;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ModuleResource $moduleResource,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $html .= $this->_getInfo();

        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getInfo()
    {
        $html = '<div class="support-info">';
        $html .= '  <h3>Support Policy</h3>';
        $html .= '  <p>Agtech To report a bug, please send your email to: <a href="" title="Agtech Support" target="_top">#</a></p>';
        $html .= '  <h3>Agtech\'s Blog</h3>';
        $html .= '  <p>We will be updating this blog on a regular basis to include you in new thinking and ideas emerging at Agtech, as well as to keep you updated with what’s going on in the e-commerce world. The blog is full with industry news, tutorials, hot releases, updates, promotions and so on. Let’s visit our blog to be kept updated!</p>';
        $html .= '</div>';

        return $html;
    }
}