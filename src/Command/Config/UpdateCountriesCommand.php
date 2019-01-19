<?php

namespace App\Command\Config;

use App\Command\Exception\ValidationException;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\CountryFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateCountriesCommand extends Command
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var CountryFetcher */
    private $countryFetcher;

    /** @var CountryRepository */
    private $countryRepository;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CountryFetcher $countryFetcher
     * @param CountryRepository $countryRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CountryFetcher $countryFetcher,
        CountryRepository $countryRepository,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->countryFetcher = $countryFetcher;
        $this->countryRepository = $countryRepository;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this->setName('app:config:update-countries');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $codes = [];
        /** @var Country $country */
        foreach ($this->countryFetcher as $country) {
            $errors = $this->validator->validate($country);
            if ($errors->count() > 0) {
                throw new ValidationException($errors);
            }
            $this->entityManager->merge($country);
            $codes[] = $country->getCode();
        }
        foreach ($this->countryRepository->findAllExceptByCodes($codes) as $country) {
            $this->entityManager->remove($country);
        }

        $this->entityManager->flush();
    }
}
