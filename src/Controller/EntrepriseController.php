<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Philippe2463\RechercheEntreprisesBundle\Client\EntrepriseSearchClientInterface;


final class EntrepriseController extends AbstractController
{
    public function __construct(
        private readonly EntrepriseSearchClientInterface $entrepriseSearch
    ) {
    }
    
    #[Route('/api/entreprises/search', name: 'entreprise_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if(empty($query)){
            return $this->json(['error' => 'Le paramètre "q" est requis'], 400);
        }

        try{
            $result = $this->entrepriseSearch->search($query, 1, 10);

            return $this->json([
                'total' => $result->totalResults,
                'page' => $result->page,
                'results' => array_map(fn($e) => [
                    'siren' => $e->siren,
                    'nom' => $e->nomComplet,
                    'adresse' => $e->siege?->adresse,
                    'code_postal' => $e->siege?->codePostal,
                    'actif' => $e->isActif(),

                ], $result->results),
            ]);
        } catch(\Exception $e){
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*#[Route('/api/entreprises/search/{codepostal}', name: 'entreprise_codepostal', methods: ['GET'])]
    public function codepostal(string $codepostal): JsonResponse
    {
        try {
            $result = $this->entrepriseSearch->searchByCodePostal($codepostal);
            //dd(!$result->results);
            if (!$result->results) {
                return $this->json(['error' => 'Aucune entreprise trouvée'], 404);
            }
            return $this->json([
                'total' => $result->totalResults,
                'page' => $result->page,
                'results' => array_map(fn($e) => [
                    'siren' => $e->siren,
                    'nom' => $e->nomComplet,
                    'adresse' => $e->siege?->adresse,
                    'code_postal' => $e->siege?->codePostal,
                    'actif' => $e->isActif(),

                ], $result->results),
            ]);
            return $this->json([
                'test' => 'Test'
            ]);
        } catch(\Exception $e){
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }*/

    #[Route('/api/entreprises/{siren}', name: 'entreprise_detail', methods: ['GET'])]
    public function detail(string $siren): JsonResponse
    {
        try {
            $entreprise = $this->entrepriseSearch->findBySiren($siren);
            if (!$entreprise) {
                return $this->json(['error' => 'Entreprise non trouvée'], 404);
            }
            return $this->json([
                'siren' => $entreprise->siren,
                'nom' => $entreprise->nomComplet,
                'adresse' => $entreprise->siege?->adresse,
                'code_postal' => $entreprise->siege?->codePostal,
                'code_naf' => $entreprise->activitePrincipale,
                'actif' => $entreprise->isActif(),
            ]);
        } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    
}
