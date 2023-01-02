<?php

namespace Agtech\Base\Console;

use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Setup extends Command
{
    
    /**
     * @var array
     */
    protected $steps = [];
    
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;
    
    /**
     * @var Pool
     */
    protected $cacheFrontendPool;
    
    /**
     * @param TypeListInterface $cacheTypeList
     * @param Pool $cacheFrontendPool
     * @param array $steps
     */
    public function __construct(
        TypeListInterface $cacheTypeList, 
        Pool $cacheFrontendPool,
        array $steps = []
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->steps = $steps;
        parent::__construct();
    }

    /**
     * Configure the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('agtech:setup');
        $this->setDescription('Setup for agtech PaaS projects');
        
        parent::configure();
    }
    
    /**
     * Initial console command function
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("

                     █████╗  ██████╗ ████████╗███████╗ ██████╗██╗  ██╗                   
                    ██╔══██╗██╔════╝ ╚══██╔══╝██╔════╝██╔════╝██║  ██║                   
                    ███████║██║  ███╗   ██║   █████╗  ██║     ███████║                   
                    ██╔══██║██║   ██║   ██║   ██╔══╝  ██║     ██╔══██║                   
                    ██║  ██║╚██████╔╝   ██║   ███████╗╚██████╗██║  ██║                   
                    ╚═╝  ╚═╝ ╚═════╝    ╚═╝   ╚══════╝ ╚═════╝╚═╝  ╚═╝                   
                                                                                        
███████╗███████╗████████╗██╗   ██╗██████╗     ████████╗ ██████╗  ██████╗ ██╗     ███████╗
██╔════╝██╔════╝╚══██╔══╝██║   ██║██╔══██╗    ╚══██╔══╝██╔═══██╗██╔═══██╗██║     ██╔════╝
███████╗█████╗     ██║   ██║   ██║██████╔╝       ██║   ██║   ██║██║   ██║██║     ███████╗
╚════██║██╔══╝     ██║   ██║   ██║██╔═══╝        ██║   ██║   ██║██║   ██║██║     ╚════██║
███████║███████╗   ██║   ╚██████╔╝██║            ██║   ╚██████╔╝╚██████╔╝███████╗███████║
╚══════╝╚══════╝   ╚═╝    ╚═════╝ ╚═╝            ╚═╝    ╚═════╝  ╚═════╝ ╚══════╝╚══════╝                                                                                                    
");
        $output->writeln("This command will help you set up most basic PaaS configuration.");
        $output->writeln("There are ".count($this->steps)." steps to complete.");
       
        $helper = $this->getHelper('question');
        $continueQ = new ConfirmationQuestion('Are you ready to continue? (yes or no) ', false);
        $continueA = $helper->ask($input, $output, $continueQ);

        if ($continueA) {
            foreach ($this->steps as $step) {
                $output->writeln("");
                $step->execute($input, $output);
            }
            $this->flushCache();
            $output->writeln('Done!');
            
        } else {
            $output->writeln('To bad, come back an other time!');
        }
        
    }
    
    /**
     * Flush caches
     *
     * @return void
     */
    protected function flushCache() 
    {
        $types = [
            'config',
            'full_page',
        ];
 
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}
