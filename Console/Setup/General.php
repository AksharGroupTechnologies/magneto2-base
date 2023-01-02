<?php

namespace Agtech\Base\Console\Setup;

use Magento\Config\Model\Config\Source\Locale\Country;
use Magento\Config\Model\Config\Source\Locale\Timezone;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\Locale\Bundle\CurrencyBundle;
use Magento\Framework\Locale\ConfigInterface as LocaleConfigInterface;
use Magento\Framework\Locale\Resolver;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class General extends AbstractSetup
{
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var ConfigWriterInterface
     */
    protected $configWriter;
    
    /**
     * @var Country
     */
    protected $countryConfig;
    
    /**
     * @var LocaleConfigInterface
     */
    protected $localeConfig;
    
    /**
     * @var Timezone
     */
    protected $timezoneConfig;
    
    /**
     * @var array
     */
    protected $storeQuestions = [
        'general/store_information/name' => 'What is the stores name?',
        'general/store_information/street_line1' => 'What is the stores street address (including housenumber)?',
        'general/store_information/postcode' => 'What is the stores ZIP code?',
        'general/store_information/city' => 'What is the stores city?',
        'general/store_information/phone' => 'What is the stores phonenumber?',
        'general/store_information/merchant_vat_number' => 'What is the stores VAT number?',
    ];
    
    /**
     * @param ConfigWriterInterface $configWriter
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigWriterInterface $configWriter,
        Country $countryConfig,
        LocaleConfigInterface $localeConfig,
        Timezone $timezoneConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->countryConfig = $countryConfig;
        $this->localeConfig = $localeConfig;
        $this->timezoneConfig = $timezoneConfig;
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
        $continueQ = new ConfirmationQuestion('<question>Would you like to setup the store basics information?</question> ', false);
        $continueA = $helper->ask($input, $output, $continueQ);
        
        if ($continueA) {
            $this->setupStoreAddress($input, $output);
            $this->setupLocaleOptions($input, $output);
            $this->setupAllowedCountries($input, $output);
        }
    }
    
    
    /**
     * Setup Store Address
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function setupStoreAddress(InputInterface $input, OutputInterface $output) 
    {       
        $helper = new QuestionHelper();
        $countries = $this->countryConfig->toOptionArray();
        
        $countryOptions = [];
        foreach ($countries as $country) {
            $countryOptions[$country['value']] = $country['label'];
        }
        
        $countryQ = new ChoiceQuestion(
            'From which country does the store operate?',
            $countryOptions,
            'NL'
        );
        $countryQ->setErrorMessage('Country %s is invalid.');
        $countryA = $helper->ask($input, $output, $countryQ);
        
        $this->configWriter->save('general/country/default', $countryA);
        $this->configWriter->save('general/store_information/country_id', $countryA);
        
        foreach ($this->storeQuestions as $configPath => $sentence) {
            $question = new Question($sentence);
            $question->setTrimmable(true);
            
            $answer = $helper->ask($input, $output, $question);   
            $this->configWriter->save($configPath, $answer);
        }     
    }
    
    /**
     * Setup Locale options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function setupLocaleOptions(InputInterface $input, OutputInterface $output) 
    {   
        $helper = new QuestionHelper();
        $locales = $this->localeConfig->getAllowedLocales();
        
        $localeQ = new ChoiceQuestion(
            'What is the locale code for the store?',
            $locales,
            'nl_NL'
        );
        $localeQ->setErrorMessage('Locale %s is invalid.');
        $localeA = $helper->ask($input, $output, $localeQ);

        $this->configWriter->save('general/locale/code', $localeA);
        
        $timezones = $this->timezoneConfig->toOptionArray();
        $timezoneOptions = [];
        foreach ($timezones as $timezone) {
            $timezoneOptions[$timezone['value']] = $timezone['label'];
        }        
        
        $timezoneQ = new ChoiceQuestion(
            'What is the locale code for the store?',
            $timezoneOptions,
            'nl_NL'
        );
        $timezoneQ->setErrorMessage('Timezone %s is invalid.');
        $timezoneA = $helper->ask($input, $output, $timezoneQ);

        $this->configWriter->save('general/locale/timezone', $timezoneA);
        
        $currencies = (new CurrencyBundle())->get(Resolver::DEFAULT_LOCALE)['Currencies'];
        $currencyOptions = [];
        foreach ($currencies as $code => $currency) {
            $currencyOptions[$code] = $currency[1] . ' (' . $code . ')';
        }
        
        $currencyQ = new ChoiceQuestion(
            'What is the locale code for the store?',
            $currencyOptions,
            'nl_NL'
        );
        $currencyQ->setErrorMessage('Timezone %s is invalid.');
        $currencyA = $helper->ask($input, $output, $currencyQ);

        $this->configWriter->save('currency/options/base', $currencyA);
        $this->configWriter->save('currency/options/default', $currencyA);
        $this->configWriter->save('currency/options/allow', $currencyA);
        
        $this->configWriter->save('general/locale/weight_unit', 'kgs');
        $this->configWriter->save('general/locale/firstday', 1);
    }
    
    /**
     * Setup Store Address
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function setupAllowedCountries(InputInterface $input, OutputInterface $output) 
    {       
        $helper = new QuestionHelper();
        $countries = $this->countryConfig->toOptionArray();
        
        $countryOptions = [];
        foreach ($countries as $country) {
            $countryOptions[$country['value']] = $country['label'];
        }
        
        $countryQ = new ChoiceQuestion(
            'From which countries are customers allow to order?',
            $countryOptions,
            'NL'
        );
        $countryQ->setErrorMessage('Country %s is invalid.');
        $countryQ->setMultiselect(true);
        $countryA = $helper->ask($input, $output, $countryQ);

        $this->configWriter->save('general/country/allow', implode(',',$countryA));
    }
}
