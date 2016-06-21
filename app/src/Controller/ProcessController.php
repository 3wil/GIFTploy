<?php

namespace Controller;

use Entity\Environment;
use GIFTploy\Deployer\Assembler;
use Silicone\Route;
use Silicone\Controller;
use GIFTploy\Git\Git;
use GIFTploy\Filesystem\ServerFactory;
use GIFTploy\ProcessConsole;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/process")
 */
class ProcessController extends Controller
{

    /**
     * @Route("/deploy/{environmentId}/{serverFactoryId}/{commitHash}", name="deploy", requirements={"environmentId"="\d+", "serverFactoryId"="\d+"})
     */
    public function deploy($environmentId, $serverFactoryId, $commitHash)
    {
        /* @var \Entity\Environment $environmentObj */
        $environmentObj = $this->app->entityManager()->getRepository('Entity\Environment')->find(intval($environmentId));
        $serverFactory = $this->app->entityManager()
            ->getRepository('Entity\ServerFactory')
            ->findOneBy([
                'id' => $serverFactoryId,
                'environment' => $environmentId,
            ]);

        $server = $serverFactory->getServer(new ServerFactory($this->app->entityManager()));
        $workingTree = Git::getRepository($this->app->getProjectsDir().$environmentObj->getDirectory());

        $assembler = new Assembler($environmentObj, $server, $workingTree);
        $deployer = $assembler->getDeployer();
        $fileStack = $assembler->getDiffFileStack($commitHash);
        $console = new ProcessConsole();

        $workingTree->checkout($commitHash);
        $deployer->deploy($fileStack, function ($file, $mode, $result, $errorMessage) use ($console) {

            if ($result === null) {
                $msg = ($mode == 'delete' ? 'Deleting file: ' : 'Uploading file: ');
                $msg .= $file.'... ';

                $console->flushProgress($msg);

            } else {
                $console->flushResult($result, $errorMessage);
            }
        });

        $console->closeConsole();
        $workingTree->checkout($environmentObj->getBranch());
        $deployer->writeLastDeployedRevision($commitHash);

        return new Response();
    }

    /**
     * @Route("/mark/{environmentId}/{serverFactoryId}/{commitHash}", name="mark", requirements={"environmentId"="\d+", "serverFactoryId"="\d+"})
     */
    public function mark($environmentId, $serverFactoryId, $commitHash)
    {
        /* @var \Entity\Environment $environmentObj */
        $environmentObj = $this->app->entityManager()->getRepository('Entity\Environment')->find(intval($environmentId));
        $serverFactory = $this->app->entityManager()
            ->getRepository('Entity\ServerFactory')
            ->findOneBy([
                'id' => $serverFactoryId,
                'environment' => $environmentId,
            ]);

        $server = $serverFactory->getServer(new ServerFactory($this->app->entityManager()));
        $workingTree = Git::getRepository($this->app->getProjectsDir().$environmentObj->getDirectory());

        $assembler = new Assembler($environmentObj, $server, $workingTree);
        $deployer = $assembler->getDeployer();

        $deployer->writeLastDeployedRevision($commitHash);

        return $this->app->redirect($this->app->url('environment-show', [
            'repositoryId' => $environmentObj->getRepository()->getId(),
            'environmentId' => $environmentObj->getId(),
        ]));
    }
}