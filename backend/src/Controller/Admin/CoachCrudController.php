<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;



class CoachCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coach::class;
    }

    public function __construct(
        private UserPasswordHasherInterface $encoder
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['hashPassword'],
        ];
    }   


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer un nouveau coach')
                    ->setIcon('fa fa-plus');
            })
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Coachs')
            ->setPageTitle('new', 'Créer un coach')
            ->setPageTitle('edit', 'Modifier un coach')
            ->setPageTitle('detail', 'Profil du coach')
            ->setEntityLabelInSingular('Coach')
            ->setEntityLabelInPlural('Coachs')
            ->setDefaultSort(['nom' => 'ASC']);
    }
    
   public function configureFields(string $pageName): iterable
    {
        $fields = [
            // Champs communs pour toutes les pages
            TextField::new('nom')->setLabel("Nom"),
            TextField::new('prenom')->setLabel("Prénom"),
            EmailField::new('email')->setLabel("Email"),
            MoneyField::new('tarif_horaire')
                ->setCurrency('EUR')
                ->setLabel("Tarif horaire")
                ->setStoredAsCents(false),
            CollectionField::new('specialites')
                ->setLabel("Spécialités"),
        ];
        
        // Personnalisation spécifique pour la page détail
        if ($pageName === Crud::PAGE_DETAIL) {
            // Remplacer les champs standards par des champs avec templates personnalisés
            $fields = [
                TextField::new('nomComplet', 'Coach')
                    ->formatValue(function ($value, $entity) {
                        return $entity->getPrenom() . ' ' . strtoupper($entity->getNom());
                    })
                    ->setTemplatePath('admin/coach/header_card.html.twig'),
                
                EmailField::new('email')
                    ->setLabel("Contact")
                    ->setTemplatePath('admin/coach/contact_card.html.twig'),
                    
                MoneyField::new('tarif_horaire')
                    ->setCurrency('EUR')
                    ->setLabel("Tarif horaire")
                    ->setStoredAsCents(false)
                    ->setTemplatePath('admin/coach/tarif_card.html.twig'),
                    
                CollectionField::new('specialites')
                    ->setLabel("Spécialités")
                    ->setTemplatePath('admin/coach/specialites_card.html.twig'),
            ];
        }
        
        // Ajout des champs de mot de passe uniquement pour la création
        if ($pageName === Crud::PAGE_NEW) {
            $fields[] = TextField::new('password')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => 'Mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;']
                    ],
                    'second_options' => [
                        'label' => 'Confirmer le mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;']
                    ],
                    'invalid_message' => 'Les mots de passe ne correspondent pas.',
                ]);
        }
        
        return $fields;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Coach) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }
        
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        
        if (count($existingSportifs) > 0) {
            $this->addFlash('danger', "Un utilisateur avec cet email existe déjà.");
            return;
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Coach) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }
        
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        foreach ($existingSportifs as $sportif) {
            if ($sportif->getId() !== $entityInstance->getId()) {
                $this->addFlash('danger', "Un autre utilisateur avec cet email existe déjà.");
                return;
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword() {
        return function($event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $password = $form->get('password')->getData();
            if ($password === null) {
                return;
            }

            $hash = $this->encoder->hashPassword($event->getData(), $password);
            $form->getData()->setPassword($hash);
        };
    }    
    
}
