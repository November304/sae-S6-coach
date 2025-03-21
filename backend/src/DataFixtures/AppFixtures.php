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

    private const THEMES_SEANCES = [
        'fitness',
        'cardio',
        'muscu',
        'crossfit',
        'yoga',
        'pilates',
        'zumba',
        'hiit',
        'aerobic',
        'boxing',
        'spinning',
        'piloxing',
        'step',
        'aquagym',
        'tai-chi',
        'circuit training',
        'kickboxing',
        'bootcamp',
        'running'
    ];

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
        $mainCoach->setDescription('Coach sportif depuis sa naissance, lui il est coach.');
        $mainCoach->setImageFilename('0.jpeg');
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

        for ($i = 0; $i < 10; $i++) {
            $coach = new Coach();
            $coach->setEmail($faker->unique()->email);
            $coach->setNom($faker->lastName);
            $coach->setPrenom($faker->firstName);

            $hashedPassword = $this->passwordHasher->hashPassword($coach, $plainPassword);
            $coach->setPassword($hashedPassword);

            $coach->setSpecialites([$faker->word, $faker->word]);
            $coach->setTarifHoraire($faker->randomFloat(2, 20, 100));
            $coach->setDescription($faker->sentence);
            $coach->setImageFilename(($i + 1) . '.jpeg');
            $coach->setRoles(['ROLE_COACH']);
            $manager->persist($coach);

            $coaches[] = $coach;
        }

        for ($i = 0; $i < 50; $i++) {
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

        $exercices = [];
        for ($i = 0; $i < 50; $i++) {
            $exercice = new \App\Entity\Exercice();
            $exercice->setNom($faker->word)
                ->setDescription($faker->sentence)
                ->setDureeEstimee($faker->numberBetween(5, 30))
                ->setDifficulte($faker->randomElement(['facile', 'moyen', 'difficile']));
            $manager->persist($exercice);
            $exercices[] = $exercice;
        }

        foreach ($coaches as $coach) {
            for ($i = 0; $i < 30; $i++) {
                $seance = new \App\Entity\Seance();
                $nbPersonne = $faker->numberBetween(1, 3);
                $dateHeure = $faker->dateTimeBetween('-3 months', '+1 month');
                $seance->setDateHeure($dateHeure);

                if ($dateHeure < new \DateTime()) {
                    $statut = $faker->randomElement(['validée', 'annulée']);
                } else {
                    $statut = $faker->randomElement(['prévue', 'prévue', 'annulée']);
                }

                $selectedSportifs = $faker->randomElements($sportifs, $faker->numberBetween(0, $nbPersonne));
                foreach ($selectedSportifs as $sportif) {
                    $seance->addSportif($sportif);
                    if ($dateHeure < new \DateTime()) {
                        $presence = new \App\Entity\Presence();
                        $presence->setSeance($seance);
                        $presence->setSportif($sportif);
                        if ($statut === 'annulée') {
                            $presence->setPresent('Annulé');
                        } elseif ($statut === 'validée') {
                            $presence->setPresent($faker->randomElement(['Présent', 'Absent']));
                        }
                        $manager->persist($presence);
                    }
                }

                $seance->setStatut($statut);
                // Détermine le type de séance en fonction du nombre de sportifs sélectionnés
                $seance->setTypeSeance(
                    $nbPersonne === 1 ? 'solo' : ($nbPersonne === 2 ? 'duo' : 'trio')
                )
                    ->setThemeSeance($faker->randomElement(self::THEMES_SEANCES))
                    ->setNiveauSeance($faker->randomElement(['débutant', 'intermédiaire', 'avancé']))
                    ->setCoach($coach);

                $selectedExercices = $faker->randomElements($exercices, $faker->numberBetween(1, 5));
                foreach ($selectedExercices as $exercice) {
                    $seance->addExercice($exercice);
                }
                $manager->persist($seance);
            }

            for ($i = 0; $i < 3; $i++) {
                $fiche = new \App\Entity\FicheDePaie();
                $totalHeures = $faker->numberBetween(10, 40);
                $fiche->setCoach($coach)
                    ->setPeriode($faker->randomElement(['mois', 'semaine']))
                    ->setTotalHeures($totalHeures)
                    ->setMontantTotal(round($coach->getTarifHoraire() * $totalHeures, 2));
                $manager->persist($fiche);
            }
        }

        $manager->flush();
    }
}
