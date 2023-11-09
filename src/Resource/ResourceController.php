<?php

namespace App\Resource;

use App\CachedJsonResponse;
use App\Search\RelatedSearchService;
use App\Utils\SearchUtils;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ResourceController extends AbstractController
{
    #[Get('/resource/{id}')]
    public function get(ManagerRegistry $doctrine, $id, Request $request)
    {
        $repository = $doctrine->getRepository(Resource::class);
        $resource = $repository->findOneBy(['id' => $id]);

        $similarIds = $resource->getSimilar() ?? [];

        $similarHydratedResources = $this->getHydratedResources($similarIds, $doctrine);
        $resource->setHydratedSimilarResources($similarHydratedResources);

        return new CachedJsonResponse($resource);
    }

    #[Get('/resource/{id}/nearest-neighbor')]
    public function getNearestNeighbor(string $id, Request $request, ManagerRegistry $doctrine): JsonResponse
    {
        $delivery = $request->get('delivery');
        $lat = ($request->get('lat') === 'null') ? null : $request->get('lat');
        $lng = ($request->get('lng') === 'null') ? null : $request->get('lng');

        $nearestNeighborService = new NearestNeighborService();

        $nearestNeighborService->id($id)
            ->delivery($delivery)
            ->lat($lat)
            ->lng($lng);

        $idsFromChroma = $nearestNeighborService->getNeighbours();

        $neighborResources = $this->getHydratedResources($idsFromChroma, $doctrine);

        return new CachedJsonResponse(['data' => $neighborResources]);
    }

    #[Get('/resource/{id}/narrow-similar')]
    public function getNarrowTermSimilarResources(string $id, NarrowTaxonomyService $narrowTaxonomyService, ManagerRegistry $doctrine): JsonResponse
    {
        $narrowTaxonomyService->executeNarrowTaxonomyQuery($id);
        $narrowTaxonomyIds = $narrowTaxonomyService->narrowTaxonomyIds;
        $narrowTaxonomyTerm = $narrowTaxonomyService->narrowTaxonomyTerm;

        $narrowTermSimilarRecords = $this->getHydratedResources($narrowTaxonomyIds, $doctrine);

        return new CachedJsonResponse(['data' => [
            'results' => $narrowTermSimilarRecords,
            'narrowTaxonomyTerm' => $narrowTaxonomyTerm
        ]]);
    }

    #[Get('/resource/{id}/related')]
    public function getRelated(ResourceService $resourceService, $id)
    {
        $data = $resourceService->getRelated($id);
        return new CachedJsonResponse([
            "data" => $data
        ]);
    }

    #[Post('/resource/{id}/inquiry')]
    public function makeInquiry(ManagerRegistry $doctrine, $id, Request $request)
    {
        $sessionId = self::getSessionId($request);

        $repository = $doctrine->getRepository(Inquiry::class);
        $resource = $repository->findOneBy(['sessionId' => $sessionId, 'resourceId' => $id]);
        if (!$resource) {
            $repository = $doctrine->getRepository(Resource::class);
            $resource = $repository->findOneBy(['id' => $id]);
            $inquiry = new Inquiry();
            $inquiry->setSessionId($sessionId);
            $inquiry->setResource($resource);
            $inquiry->setCreatedDate(time());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($inquiry);
            $entityManager->flush();
        }

        return new JsonResponse();
    }

    private static function getSessionId(Request $request): string
    {
        $session = $request->getSession();
        $session->set('forcePhpSession', true);
        return $request->getSession()->getId();
    }

    private function getHydratedResources(array $idsToHydrate, ManagerRegistry $doctrine): ?array
    {
        $dql = 'SELECT r FROM App\Resource\Resource r
                WHERE r.id IN (:idsToHydrate)
                ORDER BY FIELD(r.id, :idsToHydrate)';

        $query = $doctrine->getManager()->createQuery($dql)->setParameter('idsToHydrate', $idsToHydrate);
        return $query->getResult();
    }

    #[Get('/resource/{id}/additional-related')]
    public function additionalSearchQuery(string $id, Request $request, ManagerRegistry $doctrine)
    {
        $q = $request->get('q');
        $resourceLat = $request->get('resourceLat');
        $resourceLng = $request->get('resourceLng');

        $searchLat = $request->get('searchLat') ? (float)$request->get('searchLat') : null;
        $searchLng = $request->get('searchLng') ? (float)$request->get('searchLng') : null;

        $delivery = $request->query->get('delivery');

        $searchProvince = null;
        if (!empty($searchLat) && !empty($searchLng)) {
            $searchProvince = SearchUtils::getProvince($searchLat, $searchLng);
        }

        $resourcesByProvince = [];

        $searchService = new RelatedSearchService();
        $searchService
            ->q($q)
            ->lat($resourceLat)
            ->lng($resourceLng)
            ->offset(1)
            ->currentResourceDelivery($delivery)
            ->provinceFromSearch($searchProvince)
            ->showDistances(true)
            ->pageSize(5)
            ->currentResourceId($id)
            ->execute($doctrine);

        if (empty($searchProvince)) {
            $searchProvince = 'default';
        }

        $resourcesByProvince[$searchProvince] = $searchService->getResources();

        return new CachedJsonResponse(['data' => [
            'resources' => $resourcesByProvince,
            'provinceKey' => $searchProvince
        ],]);
    }

}
