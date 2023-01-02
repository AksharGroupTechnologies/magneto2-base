<?php

namespace Agtech\Base\Console\Setup;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSetup
{
 
    /**
     * Initial console command function
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    abstract public function execute(InputInterface $input, OutputInterface $output);
    
}
