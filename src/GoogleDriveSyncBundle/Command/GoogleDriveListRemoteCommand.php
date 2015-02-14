<?php
namespace GoogleDriveSyncBundle\Command;

use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Google_AssertionCredentials;
use Google_DriveFile;
use Google_DriveFileLabels;
use Soramugi\GoogleDrive\Client;
use Soramugi\GoogleDrive\Files;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleDriveListRemoteCommand extends ContainerAwareCommand {

    private $output;
    private $client;

    /**
     * @return mixed
     */
    private function getOutput() {
        return $this->output;
    }

    protected function configure() {
        $this->setName('google:drive:list')->setDescription('List Files on remote Google Drive')
            ->addArgument('path', InputOption::VALUE_REQUIRED, 'Path on remote Google Drive', '/')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->output = $output;
        $path = $input->getArgument('path');

        $output->writeln('List of files on ' . $path);
        $client = $this->getClient();
        $files = new Files($client);


        /** @var Google_DriveFile $item */
        $fileList = $files->listFiles();
        foreach ($fileList->getItems() as $item) {
            $this->listRecursive($item);
        }

    }

    /**
     * @return Client
     */
    protected function getClient() {
        if (!$this->client) {
            /** @var \AppKernel $kernel */
            $kernel = $this->getContainer()->get('kernel');
            $p12key = file_get_contents($kernel->getRootDir() . '/config/' . $this->getContainer()->getParameter('google-client-p12-file'));

            $credentials = new Google_AssertionCredentials($this->getContainer()->getParameter('google-client-email'), ['https://www.googleapis.com/auth/drive',], $p12key
//            , 'notasecret', 'http://oauth.net/grant_type/jwt/1.0/bearer', 'li0n@uagroup.com'
            );
            $client = new Client;
//        $client->setAssertionCredentials($credentials);

            $client->setClientId($this->getContainer()->getParameter('google-client-id'));
            $client->setClientSecret($this->getContainer()->getParameter('google-client-secret'));
            $client->setScopes(array('https://www.googleapis.com/auth/drive'));
            $client->setRedirectUri('http://localhost');


//        $client->authenticate('4/OrTcmMAcIUZtoUtezoe_Bkmf0I-awMZ9OQSkKB88sc4.gkOEI0iZhOEXYFZr95uygvXTJC4LlwI');
//        echo $client->getAccessToken();

//        echo  $authUrl = $client->createAuthUrl();
//exit;
            $token = '{"access_token": "' . $this->getContainer()->getParameter('google-access-token') . '",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "' . $this->getContainer()->getParameter('google-refresh-token') . '"
}';
            $client->setAccessToken($token);
            $client->refreshToken($this->getContainer()->getParameter('google-refresh-token'));
            $this->client = $client;
        }
        return $this->client;
    }

    /**
     * @param Google_DriveFile $item
     * @param int $level
     */
    protected function listRecursive(Google_DriveFile $item, $level = 0) {
        $this->getClient()->setUseBatch(true);
        $service = new \Google_DriveService($this->getClient());
        $files = new Files($this->getClient());
        /** @var \Google_ChildrenServiceResource $childrenService */
        $childrenService = $service->children;

        /** @var Google_DriveFileLabels $labels */
        $labels = $item->getLabels();
        if (!$labels->getTrashed()) {
            $this->getOutput()->writeln(str_pad(' ', $level * 4) . "file : {$item->getTitle()}: " . $item->getMimeType() . ' ' . $item->getId());
//                if ($item->getId() == '0B5c_uiWHCaifSThVbmE2eGl4YVk') { // Photos
            if ($item->getMimeType() == 'application/vnd.google-apps.folder') {
                $children = $childrenService->listChildren($item->getId());

                foreach ($children['items'] as $child) {
                    $childItem = $files->get($child['id']);
                    $this->listRecursive($childItem, $level + 1);
                }
            }
        }
    }


}