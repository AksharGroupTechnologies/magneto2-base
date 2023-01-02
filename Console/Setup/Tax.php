<?php

namespace Agtech\Base\Console\Setup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\File\Csv as CsvProcessor;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Module\Dir\Reader as ModuleReader;
use Magento\Catalog\Model\Product\ActionFactory as ProductActionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Tax\Model\Calculation\RateFactory;
use Magento\Tax\Model\Calculation\RuleFactory;
use Magento\Tax\Model\ClassModel as TaxClass;
use Magento\Tax\Model\ClassModelFactory as TaxClassFactory;
use Magento\Tax\Model\ResourceModel\Calculation\Rate\CollectionFactory as TaxRateCollectionFactory;
use Magento\Tax\Model\ResourceModel\Calculation\Rule\CollectionFactory as TaxRuleCollectionFactory;
use Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory as TaxClassCollectionFactory;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


class Tax extends AbstractSetup
{
    
    const TAX_RATES_FILE_NAME = 'taxrates.csv';
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var ConfigWriterInterface
     */
    protected $configWriter;
    
    /**
     * @var CsvProcessor
     */
    protected $csvProcessor;
    
    /**
     * @var FileDriver
     */
    protected $fileDriver;
    
    /**
     * @var ProductActionFactory
     */
    protected $productActionFactory;
    
    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;
    
    /**
     * @var CustomerGroupCollectionFactory
     */
    protected $customerGroupCollectionFactory;
    
    /**
     * @var TaxClassFactory
     */
    protected $taxClassFactory;
    
    /**
     * @var RateFactory
     */
    protected $rateFactory;
    
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;
    
    /**
     * @var TaxClassCollectionFactory
     */
    protected $taxClassCollectionFactory;
    
    /**
     * @var TaxRateCollectionFactory
     */
    protected $taxRateCollectionFactory;
    
    /**
     * @var TaxRuleCollectionFactory
     */
    protected $taxRuleCollectionFactory;
    
    /**
     * @var array
     */
    protected $productTaxClasses = [];
    
    /**
     * @var array
     */
    protected $customerTaxClasses = [];
    
    /**
     * @var array
     */
    protected $taxRates = [];
    
    /**
     * @var string
     */
    protected $taxRatesInputFile = '';
    
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigWriterInterface $configWriter
     * @param ModuleReader $moduleReader
     * @param CsvProcessor $csvProcessor
     * @param FileDriver $fileDriver
     * @param ProductActionFactory $productActionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerGroupCollectionFactory $customerGroupCollectionFactory
     * @param TaxClassFactory $taxClassFactory
     * @param RateFactory $rateFactory
     * @param RuleFactory $ruleFactory
     * @param TaxClassCollectionFactory $taxClassCollectionFactory
     * @param TaxRateCollectionFactory $taxRateCollectionFactory
     * @param TaxRuleCollectionFactory $taxRuleCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigWriterInterface $configWriter,
        ModuleReader $moduleReader,
        CsvProcessor $csvProcessor,
        FileDriver $fileDriver,
        ProductActionFactory $productActionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        TaxClassFactory $taxClassFactory,
        RateFactory $rateFactory,
        RuleFactory $ruleFactory,
        TaxClassCollectionFactory $taxClassCollectionFactory,
        TaxRateCollectionFactory $taxRateCollectionFactory,
        TaxRuleCollectionFactory $taxRuleCollectionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->fileDriver = $fileDriver;
        $this->csvProcessor = $csvProcessor;
        $this->productActionFactory = $productActionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->taxClassFactory = $taxClassFactory;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
        $this->taxClassCollectionFactory = $taxClassCollectionFactory;
        $this->taxRateCollectionFactory = $taxRateCollectionFactory;
        $this->taxRuleCollectionFactory = $taxRuleCollectionFactory;
        
        $this->taxRatesInputFile = sprintf(
            '%s/%s',
            $moduleReader->getModuleDir(
                \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
                'Agtech_Base'
            ),
            self::TAX_RATES_FILE_NAME
        );
    }
 
    /**
     * Initial console command function
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output) 
    {       
        $helper = new QuestionHelper();
        
        $outputStyle = new OutputFormatterStyle('red', 'yellow', ['bold', 'blink']);
        $output->getFormatter()->setStyle('fire', $outputStyle);
        
        $continueQ = new ConfirmationQuestion('<question>Would you like to setup tax rates and rules?</question>', false);
		$continueQ .=  $output->writeln('<fire>This will remove any existing rates and rules!</fire> ');
        $continueA = $helper->ask($input, $output, $continueQ);
        
        if ($continueA) {
            $this->removeTaxRules();
            $this->removeTaxRates();
            $this->removeTaxClassFromProducts();
            $this->removeTaxClassFromCustomerGroups();
            
            $this->setupProductTaxClasses($input, $output);
            $this->setupCustomerTaxClasses($input, $output);
            
            $this->setupTaxRates($input, $output);
            $this->setupTaxRules($input, $output);
            
            $this->correctSettings($input, $output);
        }
    }
    
    /**
     * Remove all tax rules
     *
     * @return void
     */
    protected function removeTaxRules() 
    {
        $rules = $this->taxRuleCollectionFactory->create();
        foreach ($rules as $rule) {
            $rule->delete();
        }
    }
    
    /**
     * Remove all tax rates
     *
     * @return void
     */
    protected function removeTaxRates() 
    {
        $rates = $this->taxRateCollectionFactory->create();
        foreach ($rates as $rate) {
            $rate->delete();
        }
    }
    
    /**
     * Set tax_class_id attribute values to null for all products
     *
     * @return void
     */
    protected function removeTaxClassFromProducts() 
    {
        $productIds = $this->productCollectionFactory->create()
            ->getAllIds();

        $this->productActionFactory->create()
            ->updateAttributes(
                $productIds,
                ['tax_class_id' => null],
                0
            );
    }
    
    /**
     * Set tax_class_id attribute values to null for all products
     *
     * @return void
     */
    protected function removeTaxClassFromCustomerGroups() 
    {
        $customerGroups = $this->customerGroupCollectionFactory->create();
        
        foreach ($customerGroups as $customerGroup) {
            $customerGroup->setTaxClassId(0)
                ->save();
        }
    }
    
    
    
    /**
     * Setup product tax classes
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setupProductTaxClasses(InputInterface $input, OutputInterface $output) 
    {
        // Remove all product tax classes
        $taxClasses = $this->taxClassCollectionFactory->create()
            ->setClassTypeFilter(TaxClass::TAX_CLASS_TYPE_PRODUCT);
        foreach ($taxClasses as $taxClass) {
            $taxClass->delete();
        }
        
        $productTaxClasses = [
            'standard_rate' => 'Standard rate',
            'reduced_rate' => 'Reduced rate',
            'superreduced_rate' => 'Super-reduced rate',
            'zero_rate' => 'Zero rate'
        ];
        
        foreach ($productTaxClasses as $code => $title) {
            $taxClass = $this->taxClassFactory->create()
                ->setClassName($title)
                ->setClassType(TaxClass::TAX_CLASS_TYPE_PRODUCT);
            $taxClass->save();
            
            $this->productTaxClasses[$code] = $taxClass;
        }
    }
    
    /**
     * Setup customer tax classes
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setupCustomerTaxClasses(InputInterface $input, OutputInterface $output) 
    {
        // Remove all customer tax classes
        $taxClasses = $this->taxClassCollectionFactory->create()
            ->setClassTypeFilter(TaxClass::TAX_CLASS_TYPE_CUSTOMER);
        foreach ($taxClasses as $taxClass) {
            $taxClass->delete();
        }
        
        $customerTaxClasses = [
            'domestic' => 'Domestic',
            'consumer' => 'EU Consumer',
            'business' => 'EU Business',
            'outside_eu' => 'Non EU'
        ];
        
        foreach ($customerTaxClasses as $code => $title) {
            $taxClass = $this->taxClassFactory->create()
                ->setClassName($title)
                ->setClassType(TaxClass::TAX_CLASS_TYPE_CUSTOMER);
            $taxClass->save();
            
            $this->customerTaxClasses[$code] = $taxClass;
        }
    }
    
    /**
     * Setup tax rates
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setupTaxRates(InputInterface $input, OutputInterface $output) 
    {
        $helper = new QuestionHelper();
        $continueQ = new ConfirmationQuestion('Do you want to set up VAT to be intra-Community for all EU countries? ', false);
        $continueA = $helper->ask($input, $output, $continueQ);
        
        //$output->writeln('<error>'.$this->taxRatesInputFile.'</error>');
        
        if ($continueA) {
            $this->setupTaxRatesIntraCommunity();
        }
        else {
            $euCountries = explode(',', $this->scopeConfig->getValue('general/country/eu_countries'));
            
            $countryQ = new ChoiceQuestion(
                'Please select the country from which you want to use the VAT',
                $euCountries,
                'NL'
            );
            $countryQ->setErrorMessage('Country %s is invalid.');

            $countryA = $helper->ask($input, $output, $countryQ);
            $output->writeln('You have just selected: '.$countryA);
            
            $this->setupTaxRatesDomestic($countryA);
        }  
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setupTaxRules(InputInterface $input, OutputInterface $output) 
    {
        $rules = [
            'zero_rate' => 'Zero Rate',
            'superreduced_rate' => 'Super-Reduced Rate',
            'reduced_rate' => 'Reduced Rate',
            'standard_rate' => 'Standard Rate'
        ];
        
        foreach ($rules as $ruleCode => $ruleTitle) {
            $productTaxClass = [$this->productTaxClasses[$ruleCode]->getId()];
            $taxRates = $this->taxRates[$ruleCode];
            
            $customerTaxClasses = [
                $this->customerTaxClasses['domestic']->getId(), 
                $this->customerTaxClasses['consumer']->getId()
            ];
            
            $rule = $this->ruleFactory->create();
            $rule->setCode($ruleTitle)
                ->setPriority(0)
                ->setCustomerTaxClassIds($customerTaxClasses)
                ->setProductTaxClassIds($productTaxClass)
                ->setTaxRateIds($taxRates)
                ->save();
        }
        
        $productTaxClasses = [
            $this->productTaxClasses['zero_rate']->getId(),
            $this->productTaxClasses['superreduced_rate']->getId(),
            $this->productTaxClasses['reduced_rate']->getId(),
            $this->productTaxClasses['standard_rate']->getId()
        ];
        $customerTaxClasses = [
            $this->customerTaxClasses['business']->getId(), 
            $this->customerTaxClasses['outside_eu']->getId()
        ];
        $taxRates = $this->taxRates['zero_rate'];
        
        $rule = $this->ruleFactory->create();
        $rule->setCode('Intra-Community')
            ->setPriority(0)
            ->setCustomerTaxClassIds($customerTaxClasses)
            ->setProductTaxClassIds($productTaxClasses)
            ->setTaxRateIds($taxRates)
            ->save();
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function correctSettings(InputInterface $input, OutputInterface $output) 
    {  
        $this->correctDefaultTaxClasses($input, $output);
        $this->setupTaxClassesPerCustomerGroup($input, $output);      
        
        $this->correctCalculationSettings($input, $output);  
        $this->correctDisplaySettings($input, $output);  
        
        $this->correctDefaultTaxCalc($input, $output);
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function correctDefaultTaxClasses(InputInterface $input, OutputInterface $output) 
    {   
        
        $helper = new QuestionHelper();

        $productTaxClassArray = [];
        foreach ($this->productTaxClasses as $taxClass) {
            $productTaxClassArray[$taxClass->getId()] = $taxClass->getClassName();
        }
        $taxClassQ = new ChoiceQuestion(
            'What is the default tax class for products?',
            $productTaxClassArray,
            'Standard rate'
        );
        $taxClassQ->setErrorMessage('Tax class %s is invalid.');
        
        $taxClassA = array_search($helper->ask($input, $output, $taxClassQ), $productTaxClassArray);   
        
        $this->configWriter->save('tax/classes/default_product_tax_class', $taxClassA);
        $this->configWriter->save('tax/classes/shipping_tax_class', $taxClassA);
        
        $customerTaxClassArray = [];
        foreach ($this->customerTaxClasses as $taxClass) {
            $customerTaxClassArray[$taxClass->getId()] = $taxClass->getClassName();
        }
        $taxClassQ = new ChoiceQuestion(
            'What is the default tax class for customers?',
            $customerTaxClassArray,
            'Domestic'
        );
        $taxClassQ->setErrorMessage('Tax class %s is invalid.');
        
        $taxClassA = array_search($helper->ask($input, $output, $taxClassQ), $customerTaxClassArray);   
        
        $this->configWriter->save('tax/classes/default_customer_tax_class', $taxClassA);                
        
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function setupTaxClassesPerCustomerGroup(InputInterface $input, OutputInterface $output) 
    {
        $helper = new QuestionHelper();
        
        $customerGroups = $this->customerGroupCollectionFactory->create();
        $customerTaxClassArray = [];
        foreach ($this->customerTaxClasses as $taxClass) {
            $customerTaxClassArray[$taxClass->getId()] = $taxClass->getClassName();
        }
        
        foreach ($customerGroups as $customerGroup) {
            $taxClassQ = new ChoiceQuestion(
                'What is the correct tax class for customergroup <info>'.$customerGroup->getCustomerGroupCode().'</info>?',
                $customerTaxClassArray,
                'Domestic'
            );
            $taxClassQ->setErrorMessage('Tax class %s is invalid.');
            
            $taxClassA = array_search($helper->ask($input, $output, $taxClassQ), $customerTaxClassArray);   
            $customerGroup->setTaxClassId($taxClassA)
                ->save();
        }
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function correctCalculationSettings(InputInterface $input, OutputInterface $output) 
    {
        $helper = new QuestionHelper();
        $this->configWriter->save('tax/calculation/algorithm', 'TOTAL_BASE_CALCULATION');           
        $this->configWriter->save('tax/calculation/based_on', 'shipping');           
        
        $inOrExcl = [
            '0' => 'Excluding Tax',
            '1' => 'Including Tax'
        ];
        $taxInputQ = new ChoiceQuestion(
            'Prices as given in backend are including or excluding tax?',
            $inOrExcl,
            'Including Tax'
        );
        $taxInputQ->setErrorMessage('whut?');
        
        $taxInputA = array_search($helper->ask($input, $output, $taxInputQ), $inOrExcl);     
        
        $this->configWriter->save('tax/calculation/price_includes_tax', $taxInputA);     
        $this->configWriter->save('tax/calculation/shipping_includes_tax', $taxInputA);
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function correctDisplaySettings(InputInterface $input, OutputInterface $output) 
    {
        $helper = new QuestionHelper();  
        $inOrExcl = [
            '1' => 'Excluding Tax',
            '2' => 'Including Tax',
            '3' => 'Both Excluding and Including'
        ];
        $taxInputQ = new ChoiceQuestion(
            'Prices as given in backend are including or excluding tax?',
            $inOrExcl,
            'Including Tax'
        );
        $taxInputQ->setErrorMessage('whut?');
        
        $taxInputA = array_search($helper->ask($input, $output, $taxInputQ), $inOrExcl);     
        
        $this->configWriter->save('tax/display/type', $taxInputA);     
        $this->configWriter->save('tax/display/shipping', $taxInputA);
        
        $this->configWriter->save('tax/cart_display/price', $taxInputA);     
        $this->configWriter->save('tax/cart_display/subtotal', $taxInputA);
        $this->configWriter->save('tax/cart_display/shipping', $taxInputA);     

        $this->configWriter->save('tax/sales_display/price', $taxInputA);
        $this->configWriter->save('tax/sales_display/subtotal', $taxInputA);     
        $this->configWriter->save('tax/sales_display/shipping', $taxInputA);
    }
    
    /**
     * Setup tax rules
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function correctDefaultTaxCalc(InputInterface $input, OutputInterface $output) 
    {
        $helper = new QuestionHelper();
        $euCountries = explode(',', $this->scopeConfig->getValue('general/country/eu_countries')); 
        $taxInputQ = new ChoiceQuestion(
            'What is the default tax country?',
            $euCountries,
            'Including Tax'
        );
        $taxInputQ->setErrorMessage('whut?');
        
        $taxInputA = $helper->ask($input, $output, $taxInputQ);     
        
        $this->configWriter->save('tax/defaults/country', $taxInputA);     
        $this->configWriter->save('tax/defaults/region', 0);
        $this->configWriter->save('tax/defaults/postcode', '*');
    }
    
    /**
     * Setup tax rates for intra community VAT
     *
     * @return void
     */
    protected function setupTaxRatesIntraCommunity() 
    {
        $csvData = $this->getTaxCsvData();
        
        foreach ($csvData as $line) {
            $this->setupTaxRatesForCountry($line['country_code'], $line);
        }
    }
    
    /**
     * Setup tax rates for domestic VAT
     *
     * @return void
     */
    protected function setupTaxRatesDomestic($countryCode) 
    {
        $domesticRates = $this->getDomesticRates($countryCode);
        
        if ($domesticRates === false) {
            throw new \RuntimeException('Could not find domestic rate for ' . $countryCode);
        }
        
        $euCountries = explode(',', $this->scopeConfig->getValue('general/country/eu_countries')); 
        
        foreach ($euCountries as $euCountry) {
            $this->setupTaxRatesForCountry($euCountry, $domesticRates);
        }  
    }
    
    /**
     * Get tax rates for single country
     */
    protected function getDomesticRates($countryCode) 
    {
        $csvData = $this->getTaxCsvData();
        foreach ($csvData as $line) {
            if ($line['country_code'] === $countryCode) {
                return $line;
            }
        }
        return false;
    }
    
    /**
     * @return array
     */
    protected function getTaxCsvData() 
    {
        if (!$this->fileDriver->isExists($this->taxRatesInputFile)) {
            throw new \RuntimeException($this->taxRatesInputFile . ' is missing');
        }
        
        $data = $this->csvProcessor->getData($this->taxRatesInputFile);
        
        $headers = $data[0];
        unset($data[0]);
        foreach ($headers as $pos => $header) {
            foreach ($data as $lineNum => $line) {
                $data[$lineNum][$header] = $line[$pos];
            }
        }        

        return $data;
    }
    
    /**
     * @param string $countryCode
     * @param array $data
     * @return void
     */
    protected function setupTaxRatesForCountry($countryCode, $data) 
    {
        $rateCodes = [
            'zero_rate' => 'Zero Rate',
            'superreduced_rate' => 'Super-Reduced Rate',
            'reduced_rate' => 'Reduced Rate',
            'standard_rate' => 'Standard Rate'
        ];
        
        foreach ($rateCodes as $rateCode => $rateTitle) {
            $code = sprintf('%s %s', $countryCode, $rateTitle);
            $rate = $this->rateFactory->create()
                ->setCode($code)
                ->setTaxCountryId($countryCode)
                ->setTaxRegionId('*')
                ->setTaxPostcode('*')
                ->setZipIsRange(0)
                ->setRate($data[$rateCode])
                ->save();
                
            $this->taxRates[$rateCode][] = $rate->getId();
        }
        
    }
    
}
