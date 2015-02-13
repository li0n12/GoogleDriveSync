<?php
namespace GoogleDriveSyncBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleDriveListRemoteCommand extends ContainerAwareCommand {
    protected function configure() {
        $this->setName('google:drive:list')->setDescription('List Files on remote Google Drive')
            ->addArgument('path', InputOption::VALUE_REQUIRED, 'Path on remote Google Drive', '/')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $path = $input->getArgument('path');
        $output->writeln('List of files on ' . $path);
    }


}