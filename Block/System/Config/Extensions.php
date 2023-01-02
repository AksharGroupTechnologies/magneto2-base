<?php

namespace Agtech\Base\Block\System\Config;


use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

class Extensions extends \Magento\Config\Block\System\Config\Form\Fieldset
{
	// This is for module list need to remove for getName() function 6th and 7th line of file
    /**
     * @var \Magento\Config\Block\System\Config\Form\Field
     */
    protected $_fieldRenderer;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;
    /**
     * An associative array of modules
     *
     * The possible values are 1 (enabled) or 0 (disabled)
     *
     * @var int[]
     */
    private $configData;
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
		DeploymentConfig $deployConf,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\ModuleResource $moduleResource,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->_moduleList = $moduleList;
        $this->moduleResource = $moduleResource;
		$this->modmod = $deployConf;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
	 
	 //Loadconfig data for disable module list
	private function loadConfigData()
    {
        if (null === $this->configData && null !== $this->modmod->get(ConfigOptionsListConstants::KEY_MODULES)) {
            $this->configData = $this->modmod->get(ConfigOptionsListConstants::KEY_MODULES);
        }
    }
	  public function getNames()
    {
        $this->loadConfigData();
      
        $result = array_keys(array_filter($this->configData));
        return $result;
    }
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);

        $modules = $this->getNames(); //before $this->_moduleList->getNames(); Replaced with created function`

        $dispatchResult = new \Magento\Framework\DataObject($modules);

        $modules = $dispatchResult->toArray();

        sort($modules);

        foreach ($modules as $moduleName) {
            if (strstr($moduleName, 'Agtech_') === false) {
                continue;
            }
            if ($moduleName === 'Agtech_Base') {
                continue;
            }
            $html .= $this->_getFieldHtml($element, $moduleName);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getFieldHtml($fieldset, $moduleCode)
    {
        $currentVer = $this->moduleResource->getDataVersion($moduleCode);

        if (!$currentVer) {
            return '';
        }

        $moduleName = substr($moduleCode, strpos($moduleCode, '_') + 1);

        $status = '<a  target="_blank"><img src="' . $this->getViewFileUrl('Agtech_Base::images/ok.gif') . '" title="' . __("Installed") . '"/></a>';

        $moduleName = '<span class="extension-name">' . $moduleName . '</span>';

        $moduleName = $status . ' ' . $moduleName;

        $field = $fieldset->addField($moduleCode, 'label', array(
            'name' => 'dummy',
            'label' => $moduleName,
            'value' => $currentVer,
        ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }

    /**
     * @return \Magento\Config\Block\System\Config\Form\Field
     */
    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = $this->_layout->getBlockSingleton(
                'Magento\Config\Block\System\Config\Form\Field'
            );
        }
        return $this->_fieldRenderer;
    }
}