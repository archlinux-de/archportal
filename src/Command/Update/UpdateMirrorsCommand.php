<?php

namespace App\Command\Update;

use App\Entity\Mirror;
use App\Repository\MirrorRepository;
use App\Service\MirrorFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateMirrorsCommand extends Command
{
    use LockableTrait;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MirrorFetcher */
    private $mirrorFetcher;

    /** @var MirrorRepository */
    private $mirrorRepository;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param MirrorFetcher $mirrorFetcher
     * @param MirrorRepository $mirrorRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MirrorFetcher $mirrorFetcher,
        MirrorRepository $mirrorRepository,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->mirrorFetcher = $mirrorFetcher;
        $this->mirrorRepository = $mirrorRepository;
        $this->validator = $validator;
    }

    protected function configure()
    {
        $this->setName('app:update:mirrors');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->lock('cron.lock', true);

        $urls = [];
        /** @var Mirror $mirror */
        foreach ($this->mirrorFetcher as $mirror) {
            $errors = $this->validator->validate($mirror);
            if ($errors->count() > 0) {
                throw new \RuntimeException((string)$errors);
            }

            $this->entityManager->merge($mirror);
            $urls[] = $mirror->getUrl();
        }
        foreach ($this->mirrorRepository->findAllExceptByUrls($urls) as $mirror) {
            $this->entityManager->remove($mirror);
        }

        $this->entityManager->flush();
        $this->release();
    }
}
