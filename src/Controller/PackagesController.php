<?php

namespace App\Controller;

use App\Repository\PackageRepository;
use DatatablesApiBundle\DatatablesColumnConfiguration;
use DatatablesApiBundle\DatatablesQuery;
use DatatablesApiBundle\DatatablesRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackagesController extends AbstractController
{
    /** @var PackageRepository */
    private $packageRepository;

    /** @var DatatablesQuery */
    private $datatablesQuery;

    /**
     * @param PackageRepository $packageRepository
     * @param DatatablesQuery $datatablesQuery
     */
    public function __construct(PackageRepository $packageRepository, DatatablesQuery $datatablesQuery)
    {
        $this->packageRepository = $packageRepository;
        $this->datatablesQuery = $datatablesQuery;
    }

    /**
     * @Route("/packages", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $search = $request->get('search');
        $defaultArchitecture = $this->getParameter('app.packages.default_architecture');
        $architecture = $request->get('architecture', $defaultArchitecture);
        $repository = $request->get('repository');

        return $this->render(
            'packages/index.html.twig',
            [
                'architecture' => $architecture,
                'defaultArchitecture' => $defaultArchitecture,
                'repository' => $repository,
                'search' => $search
            ]
        );
    }

    /**
     * @Route("/packages/datatables", methods={"GET"})
     * @param DatatablesRequest $request
     * @return Response
     */
    public function datatablesAction(DatatablesRequest $request): Response
    {
        $columnConfiguration = (new DatatablesColumnConfiguration())
            ->addCompareableColumn('repository.name', 'repository.name')
            ->addCompareableColumn('architecture', 'repository.architecture')
            ->addTextSearchableColumn('name', 'package.name')
            ->addTextSearchableColumn('description', 'package.description')
            ->addTextSearchableColumn('groups', 'package.groups')
            ->addOrderableColumn('builddate', 'package.buildDate')
            ->addOrderableColumn('name', 'package.name');

        $response = $this->datatablesQuery->getResult(
            $request,
            $columnConfiguration,
            $this->packageRepository
                ->createQueryBuilder('package')
                ->addSelect('repository')
                ->join('package.repository', 'repository'),
            $this->packageRepository->getSize()
        );

        $jsonResponse = $this->json($response);
        // Only cache the first draw
        if ($response->getDraw() == 1) {
            $jsonResponse->setMaxAge(300);
            $jsonResponse->setSharedMaxAge(3600);
        }
        return $jsonResponse;
    }

    /**
     * @Route("/packages/opensearch", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function openSearchAction(): Response
    {
        $response = $this->render('packages/opensearch.xml.twig');
        $response->headers->set('Content-Type', 'application/opensearchdescription+xml; charset=UTF-8');
        return $response;
    }

    /**
     * @Route("/packages/feed", methods={"GET"})
     * @Cache(smaxage="600")
     * @return Response
     */
    public function feedAction(): Response
    {
        $packages = $this->packageRepository->findLatestByArchitecture(
            $this->getParameter('app.packages.default_architecture'),
            25
        );

        $response = $this->render(
            'packages/feed.xml.twig',
            ['packages' => $packages]
        );
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        return $response;
    }

    /**
     * @Route("/packages/suggest", methods={"GET"})
     * @Cache(smaxage="600")
     * @param Request $request
     * @return Response
     */
    public function suggestAction(Request $request): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $this->packageRepository->findByTerm($term, 10);

        return $this->json(array_column($suggestions, 'name'));
    }
}
