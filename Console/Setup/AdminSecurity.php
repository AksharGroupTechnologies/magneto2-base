<?php

namespace Agtech\Base\Console\Setup;

use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Module\Status as ModuleStatusManager;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AdminSecurity extends AbstractSetup
{
    
    /**
     * @var ConfigWriterInterface
     */
    protected $configWriter;
    
    /**
     * @var ModuleManager
     */
    protected $moduleManager;
    
    /**
     * @var ModuleStatusManager
     */
    protected $moduleStatusManager;
    
    /**
     * @param ConfigWriterInterface $configWriter
     * @param ModuleManager $moduleManager
     * @param ModuleStatusManager $moduleStatusManager
     */
    public function __construct(
        ConfigWriterInterface $configWriter,
        ModuleManager $moduleManager,
        ModuleStatusManager $moduleStatusManager
    ) {
        $this->configWriter = $configWriter;
        $this->moduleManager = $moduleManager;
        $this->moduleStatusManager = $moduleStatusManager;
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
        $continueQ = new ConfirmationQuestion('<question>Would you like to setup admin security rules?</question> ', false);
        $continueA = $helper->ask($input, $output, $continueQ);
        
        if ($continueA) {
            $this->configWriter->save('admin/security/password_lifetime', null);
            $this->configWriter->save('admin/security/password_is_forced', 0);
            $this->configWriter->save('twofactorauth/general/force_providers', 'google');
            
            if ($this->moduleManager->isEnabled('Magento_TwoFactorAuth')) {
                $twofaQ = new ConfirmationQuestion('Do you want to <comment>DIS</comment>able Two Factory Authentication?', false);
                $twofaA = $helper->ask($input, $output, $twofaQ);
                
                if ($continueA) {
                    $this->moduleStatusManager->setIsEnabled(false, ['Magento_TwoFactorAuth']);
                    $output->writeln('<info>You need to run setup:di:compile after finishing the setup!</info>');
                }
            }
        }
    }
    
}
