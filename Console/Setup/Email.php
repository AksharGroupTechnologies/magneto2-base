<?php

namespace Agtech\Base\Console\Setup;

use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Email extends AbstractSetup
{
    
    /**
     * @var ConfigWriterInterface
     */
    protected $configWriter;
    
    /**
     * @var array
     */
    protected $questions = [
        'trans_email/ident_general/email' => 'Please provide a general contact email address: ',
        'trans_email/ident_general/name' => 'Please provide a name for the general contact email address: ',
        '1' => '--spacer',
        'trans_email/ident_sales/email' => 'Please provide a sales contact email address: ',
        'trans_email/ident_sales/name' => 'Please provide a name for the sales contact email address: ',
        '2' => '--spacer',
        'trans_email/ident_support/email' => 'Please provide a support contact email address: ',
        'trans_email/ident_support/name' => 'Please provide a name for the support contact email address: ',
        '3' => '--spacer',
        'trans_email/ident_custom1/email' => 'Please provide a custom contact email address: ',
        'trans_email/ident_custom1/name' => 'Please provide a name for the custom contact email address: ',
        '4' => '--spacer',
        'trans_email/ident_custom2/email' => 'Please provide a second custom contact email address: ',
        'trans_email/ident_custom2/name' => 'Please provide a name for the second custom contact email address: ',
    ];
    
    /**
     * @param ConfigWriterInterface $configWriter
     */
    public function __construct(
        ConfigWriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
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
        $continueQ = new ConfirmationQuestion('<question>Would you like to setup store email addresses?</question> ', false);
        $continueA = $helper->ask($input, $output, $continueQ);
        
        if ($continueA) {
            foreach ($this->questions as $configKey => $sentence) {
                $this->askQuestionAndProcess($input, $output, $configKey, $sentence);    
            }
        }
    }
    
    /**
     * Initial console command function
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function askQuestionAndProcess(
        InputInterface $input, 
        OutputInterface $output,
        string $configPath,
        string $sentence
    ) {
        if ($sentence === '--spacer') {
            $output->writeln("");
        }
        else {
            $helper = new QuestionHelper();
            
            $question = new Question($sentence);
            $question->setTrimmable(true);
            
            if (substr($configPath, -5) === 'email') {
                $question->setValidator(function ($answer) {
                    if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException(
                            'Invalid email format'
                        );
                    }

                    return $answer;
                });
                
                $question->setMaxAttempts(2);
            }
            
            $answer = $helper->ask($input, $output, $question);
            $this->configWriter->save($configPath, $answer);
        }
    }
    
}
