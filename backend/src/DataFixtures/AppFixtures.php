<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Coach;
use App\Entity\Sportif;
use App\Entity\Utilisateur;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create();

        $mainResp = new Utilisateur();
        $mainResp->setEmail('admin@coach.fr');
        $mainResp->setNom('Admin');
        $mainResp->setPrenom('Admin');
        $mainResp->setPassword($this->passwordHasher->hashPassword($mainResp, 'admin'));
        $mainResp->setRoles(['ROLE_RESPONSABLE']);
        $manager->persist($mainResp);

        $mainCoach = new Coach();
        $mainCoach->setEmail('coach@coach.fr');
        $mainCoach->setNom('Coach');
        $mainCoach->setPrenom('Coach');
        $mainCoach->setPassword($this->passwordHasher->hashPassword($mainCoach, 'coach'));
        $mainCoach->setSpecialites(['Yoga', 'Pilates']);
        $mainCoach->setTarifHoraire(50);
        $mainCoach->setRoles(['ROLE_COACH']);
        $manager->persist($mainCoach);

        $mainSportif = new Sportif();
        $mainSportif->setEmail('sportif@coach.fr');
        $mainSportif->setNom('Sportif');
        $mainSportif->setPrenom('Sportif');
        $mainSportif->setPassword($this->passwordHasher->hashPassword($mainSportif, 'sportif'));
        $mainSportif->setDateInscription($faker->dateTime);
        $mainSportif->setNiveauSportif('avancé');
        $mainSportif->setRoles(['ROLE_SPORTIF']);
        $manager->persist($mainSportif);


        $plainPassword = 'password';

        $coaches = [$mainCoach];
        $sportifs = [$mainSportif];

        // Création de 10 coachs
        for ($i = 0; $i < 10; $i++) {
            $coach = new Coach();
            $coach->setEmail($faker->unique()->email);
            $coach->setNom($faker->lastName);
            $coach->setPrenom($faker->firstName);

            $hashedPassword = $this->passwordHasher->hashPassword($coach, $plainPassword);
            $coach->setPassword($hashedPassword);

            $coach->setSpecialites([$faker->word, $faker->word]);
            $coach->setTarifHoraire($faker->randomFloat(2, 20, 100));
            $coach->setRoles(['ROLE_COACH']);
            $manager->persist($coach);

            $coaches[] = $coach;
        }

        // Création de 10 sportifs
        for ($i = 0; $i < 10; $i++) {
            $sportif = new Sportif();
            $sportif->setEmail($faker->unique()->email);
            $sportif->setNom($faker->lastName);
            $sportif->setPrenom($faker->firstName);

            $hashedPassword = $this->passwordHasher->hashPassword($sportif, $plainPassword);
            $sportif->setPassword($hashedPassword);

            $sportif->setDateInscription($faker->dateTime);
            $sportif->setNiveauSportif($faker->randomElement(['débutant', 'intermédiaire', 'avancé']));
            $sportif->setRoles(['ROLE_SPORTIF']);
            $manager->persist($sportif);

            $sportifs[] = $sportif;
        }

        // Création de 10 responsables
        for ($i = 0; $i < 10; $i++) {
            $responsable = new Utilisateur();
            $responsable->setEmail($faker->unique()->email);
            $responsable->setNom($faker->lastName);
            $responsable->setPrenom($faker->firstName);

            $hashedPassword = $this->passwordHasher->hashPassword($responsable, $plainPassword);
            $responsable->setPassword($hashedPassword);

            $responsable->setRoles(['ROLE_RESPONSABLE']);
            $manager->persist($responsable);
        }


        // Stocker les objets déjà créés
        

        // Création de 20 exercices
        $exercices = [];
        for ($i = 0; $i < 20; $i++) {
            $exercice = new \App\Entity\Exercice();
            $exercice->setNom($faker->word)
                     ->setDescription($faker->sentence)
                     ->setDureeEstimee($faker->numberBetween(5, 30))
                     ->setDifficulte($faker->randomElement(['facile', 'moyen', 'difficile']));
            $manager->persist($exercice);
            $exercices[] = $exercice;
        }

        // Création de 15 séances
        $seances = [];
        for ($i = 0; $i < 15; $i++) {
            $seance = new \App\Entity\Seance();
            $seance->setDateHeure($faker->dateTimeBetween('-1 month', '+1 month'))
                   ->setTypeSeance($faker->randomElement(['solo', 'duo', 'trio']))
                   ->setThemeSeance($faker->word)
                   ->setNiveauSeance($faker->randomElement(['débutant', 'intermédiaire', 'avancé']))
                   ->setCoach($faker->randomElement($coaches))
                   ->setStatut($faker->randomElement(['prévue', 'validée', 'annulée']));
            // Associer 1 à 3 exercices
            $selectedExercices = $faker->randomElements($exercices, $faker->numberBetween(1, 3));
            foreach ($selectedExercices as $exercice) {
                $seance->addExercice($exercice);
            }
            // Associer 1 à 3 sportifs
            $selectedSportifs = $faker->randomElements($sportifs, $faker->numberBetween(1, 3));
            foreach ($selectedSportifs as $sportif) {
                $seance->addSportif($sportif);
            }
            $manager->persist($seance);
            $seances[] = $seance;
        }

        // Création de 10 fiches de paie
        for ($i = 0; $i < 10; $i++) {
            $fiche = new \App\Entity\FicheDePaie();
            $fiche->setCoach($faker->randomElement($coaches))
                  ->setPeriode($faker->randomElement(['mois', 'semaine']))
                  ->setTotalHeures($faker->numberBetween(10, 40))
                  ->setMontantTotal($faker->randomFloat(2, 200, 1000));
            $manager->persist($fiche);
        }


        $manager->flush();
    }
}
