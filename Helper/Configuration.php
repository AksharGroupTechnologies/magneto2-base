<?php

namespace Agtech\Base\Helper;

/**
 * Copyright (c) agtech B.V.
 *
 * Author: 
 * Created: 2019-12-24
 */
 
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Base helper for all agtech modules to get configuration 
 * from system.xml in various formats
 */
class Configuration extends AbstractHelper
{
	
	const DEFAULT_SECTION = 'agtech_base';
	
	const DEFAULT_GROUP = 'general';
	
	/**
	 * @var StoreManagerInterface $storeManager
	 */
	protected $storeManager;
	
	/**
	 * @var EncryptorInterface $encryptor
	 */
	protected $encryptor;
	
	/**
	 * @var string $group
	 */
	private $group = '';
	
	/**
	 * @var string $section
	 */
	private $section = '';
	
	/**
	 * @var int|null $storeId
	 */
	private $storeId = null;
	
	/**
	 * Constructor
	 *
	 * @param Context $context
	 * @param EncryptorInterface $encryptor
	 * @param StoreManagerInterface $storeManager
	 * @return void
	 */
	public function __construct(
		Context $context,
		EncryptorInterface $encryptor,
		StoreManagerInterface $storeManager
	)
	{
		$this->encryptor = $encryptor;
		$this->storeManager = $storeManager;
		parent::__construct($context);
	}
	
	/**
	 * Set the configuration group
	 *
	 * @param string $group
	 * @return $this
	 */
	public function setGroup(string $group): Configuration
	{
		$this->group = $group;
		return $this;
	}
	
	/**
	 * Get the configuration group
	 *
	 * @param string $group
	 * @return string
	 */
	private function getGroup(string $group): string
	{
		if (!empty($group)) {
			return $group;
		}
		elseif (!empty($this->group)) {
			return $this->group;
		}
		return static::DEFAULT_GROUP;
	}
	
	/**
	 * Set the configuration section
	 *
	 * @param string $section
	 * @return $this
	 */
	public function setSection(string $section): Configuration
	{
		$this->section = $section;
		return $this;
	}
	
	/**
	 * Get the configuration section
	 *
	 * @param string $section
	 * @return string
	 */
	private function getSection(string $section): string
	{
		if (!empty($section)) {
			return $section;
		}
		elseif (!empty($this->section)) {
			return $this->section;
		}
		return static::DEFAULT_SECTION;
	}
	
	/**
	 * Set the configuration store id
	 *
	 * @param int|null $storeId
	 * @return $this
	 */
	public function setStoreId($storeId): Configuration
	{
		$this->storeId = $storeId;
		return $this;
	}
	
	/**
	 * Get the configuration store id
	 *
	 * @param int|null $storeId
	 * @return int
	 */
	private function getStoreId($storeId): int
	{
		if ($storeId !== null) {
			return $storeId;
		}
		elseif ($this->storeId !== null) {
			return $this->storeId;
		}
		// Always asume the current store
		return $this->storeManager->getStore()->getId();
	}
	
	/**
	 * Get configuration value
	 * 
	 * @param string $field
	 * @param string $group
	 * @param string $section
	 * @param int|null $storeId
	 * @return string
	 * @throws LocalizedException
	 */
	public function getSetting(
		string $field, 
		string $group = '', 
		string $section = '', 
		$storeId = null
	): string
	{
		if (empty($field)) {
			throw new LocalizedException(__('$field can not be empty'));
		}
		
		$group = $this->getGroup($group);
		$section = $this->getSection($section);
		$storeId = $this->getStoreId($storeId);
		
		$path = implode('/', [$section, $group, $field]);
		
		if ($storeId === 0) {
			return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
		}
		
		return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
	}
	
	/**
	 * Get configuration value from multiselect as array
	 * 
	 * @param string $field
	 * @param string $group
	 * @param string $section
	 * @param int|null $storeId
	 * @return array
	 */
	public function getMultiSelectSetting(
		string $field, 
		string $group = '', 
		string $section = '', 
		$storeId = null
	): array
	{
		/** @var string */
		$setting = $this->getSetting($field, $group, $section, $store);
		return $this->trimExplode(',', $setting);
	}
	
	/**
	 * Get configuration value as boolean, requires yes/no field
	 * 
	 * @param string $field
	 * @param string $group
	 * @param string $section
	 * @param int|null $storeId
	 * @return bool
	 */
	public function getBooleanSetting(
		string $field, 
		string $group = '', 
		string $section = '', 
		$storeId = null
	): array
	{
		/** @var string */
		$setting = $this->getSetting($field, $group, $section, $store);
		return ($settings == 1) ? true : false;
	}
	
	/**
	 * Get configuration value as boolean, assumes group is general and
	 * field is enabled
	 * 
	 * @param string $section
	 * @param int|null $storeId
	 * @return bool
	 */
	public function isEnabled(
		string $section = '', 
		$storeId = null
	): array
	{
		/** @var string */
		$setting = $this->getSetting('enabled', 'general', $section, $store);
		return ($settings == 1) ? true : false;
	}
	
	/**
	 * Get obscured configuration value
	 * 
	 * @param string $field
	 * @param string $group
	 * @param string $section
	 * @param int|null $storeId
	 * @return string
	 */
	public function getObscuredSetting(
		string $field, 
		string $group = '', 
		string $section = '', 
		$storeId = null
	): string
	{
		/** @var string */
		$setting = $this->getSetting($field, $group, $section, $store);
		return $this->encryptor->decrypt($setting);
	}
	
	/**
	 * Get configuration value from multiselect as array
	 * 
	 * @param string $delimiter
	 * @param string $string
	 * @param bool $removeEmptyValues
	 * @param int $limit
	 * @return array
	 */
	public function trimExplode(
		string $delimiter, 
		string $string, 
		bool $removeEmptyValues = true, 
		int $limit = 0
	): array
	{
		if ($limit > 0)  {
			$result = array_map('trim', explode($delimiter, $string, $limit));
		} else {
			$result = array_map('trim', explode($delimiter, $string));
		}  
		if ($removeEmptyValues === true) {
			$result = array_filter($result, function($k){
				return  $k !== '';
			});
		}
		return $result;
	}
}
