<?php

namespace App\Controller\Api;

use App\Entity\Presence;
use App\Entity\Seance;
use App\Entity\Sportif;
use App\Repository\SeanceRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SeanceController extends AbstractController {
    #[Route('/api/public/seances', name: 'api_pub_get_seances', methods: ['GET'])]
    public function getPublicSeanceList(SeanceRepository $seanceRepo): JsonResponse
    {
        $seances = $seanceRepo->findAll();
        return $this->json($seances, JsonResponse::HTTP_OK, [], ['groups' => 'seance:public:read']);
    }

    #[Route('/api/seances', name: 'api_get_seances', methods: ['GET'])]
    public function getSeanceList(SeanceRepository $seanceRepo): JsonResponse
    {
        $seances = $seanceRepo->findAll();
        return $this->json($seances, JsonResponse::HTTP_OK, [], ['groups' => 'seance:read']);
    }

    #[Route('/api/seances/resa/{id}', name: 'api_resa_seance', methods: ['POST'])]
    public function reserveSeance(int $id, Security $security,SeanceRepository $seanceRepo, EntityManagerInterface $em): JsonResponse
    {   
        $seance = $seanceRepo->find($id);

        if(!$seance || !$seance instanceof Seance){
            return $this->json(['error' => 'Seance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $security->getUser();
        if (!$user instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        //Check si la seance est reservable
        
        //Check si la seance est pleine
        if($seance->getRemainingPlaces() <= 0){
            return $this->json(['error' => 'Seance pleine'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //Check si le sportif est deja inscrit dans la seance
        if($seance->getSportifs()->contains($user)){
            return $this->json(['error' => 'Vous êtes déjà inscrit à la séance'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //Check si le sportif a deja une seance à cette heure et si le sportif a plus de 3 séances à venir
        $seances = $user->getSeances();
        $cptSeances = 0;
        foreach($seances as $s){
            $dateDebut = $s->getDateHeure();
            if($dateDebut > new DateTime()){
                $cptSeances++;
                if($cptSeances >= 3){
                    return $this->json(['error' => 'Vous êtes déjà inscrit sur 3 séances à venir'], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
            $dateFin = DateTime::createFromInterface($dateDebut)->add(new \DateInterval('PT' . $seance->getDureeEstimeeTotal() . 'M'));
            if($seance->getDateHeure() >= $dateDebut && $seance->getDateHeure() <= $dateFin){
                return $this->json(['error' => 'Vous êtes déjà inscrit à une séance à cette heure'], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        //Check si le sportif a le bon niveau
        if($seance->getNiveauSeance() > $user->getNiveauSportif()){
            return $this->json(['error' => 'Niveau sportif insuffisant'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //Check si la seance est pas déjà passée
        if($seance->getDateHeure() < new DateTime()){
            return $this->json(['error' => 'Seance déjà passée'], JsonResponse::HTTP_BAD_REQUEST);
        }

        //Check si le sportif a annulé cette séance plus de 2 fois auparavant.
        $presences = $user->getPresences();
        $cptAnnulation = 0;
        foreach($presences as $presence){
            if($presence->getSeance() == $seance && $presence->getPresent() == 'Annulé'){
                $cptAnnulation++;
                if($cptAnnulation >= 2){
                    return $this->json(['error' => 'Vous avez déjà annulé cette séance 2 fois','code'=>JsonResponse::HTTP_BAD_REQUEST], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
        }

        $seance->addSportif($user);
        $em->flush();

        return $this->json(['message'=>'Vous avez bien été enregistré à la séance'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/seances/resa/{id}', name: 'api_unresa_seance', methods: ['DELETE'])]
    public function annulerSeance(int $id, Security $security, SeanceRepository $seanceRepo, EntityManagerInterface $em) : JsonResponse
    {
        $seance = $seanceRepo->find($id);

        if(!$seance || !$seance instanceof Seance){
            return $this->json(['error' => 'Seance non trouvée'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $security->getUser();
        if (!$user instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        if(!$seance->getSportifs()->contains($user)){
            return $this->json(['error' => 'Sportif non inscrit à la séance'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if($seance->getDateHeure() < new DateTime()){
            return $this->json(['error' => 'Seance déjà passée'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dateLimit = DateTime::createFromInterface($seance->getDateHeure())->sub(new \DateInterval('PT24H'));
        $presence = new Presence();
        $presence->setSportif($user);
        $presence->setSeance($seance);
        if($dateLimit < new DateTime()){ //Si la séance est dans moins de 24h
            
            
            $presence->setPresent('Absent');
            $em->persist($presence);
            $em->flush();
            return $this->json(['message'=>'Vous avez marqué absent de la séance'], JsonResponse::HTTP_OK);

        }
        else 
        {
            $presence->setPresent('Annulé');
            $em->persist($presence);
            $seance->removeSportif($user);
            $em->flush();
            return $this->json(['message'=>'Vous avez bien été désinscrit de la séance'], JsonResponse::HTTP_OK);

        }
    }


}
