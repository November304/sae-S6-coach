<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use App\Repository\CoachRepository;

class SeanceCrudController extends AbstractCrudController
{
    private $coachRepository;

    public function __construct(CoachRepository $coachRepository)
    {
        $this->coachRepository = $coachRepository;
    }
    
    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer une nouvelle séance')
                    ->setIcon('fa fa-plus');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Séances')
            ->setPageTitle('new', 'Créer une séance')
            ->setPageTitle('edit', 'Modifier une séance')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form/seance_form_theme.html.twig']);
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('coach')
                ->setLabel("Coach")
                ->setFormTypeOption('choice_label', 'nom')
                ->formatValue(function ($value, $entity) {
                    return $entity->getCoach()->getNom();
                }),
            DateTimeField::new('date_heure')->setLabel("Date et heure"),
            ChoiceField::new('type_seance')
                ->setChoices([
                    'Solo' => 'solo',
                    'Duo' => 'duo',
                    'Trio' => 'trio',
                ])
                ->setLabel("Type de séance")
                ->setFormTypeOption('attr', [
                    'class' => 'type-seance-select',
                    'onChange' => 'updateSportifLimit(this.value)'
                ]),
            TextField::new('theme_seance')->setLabel("Thème de la séance"),
            AssociationField::new('sportifs')
                ->setLabel("Sportifs")                
                ->setFormTypeOption('choice_label', 'nom')
                ->setFormTypeOption('attr', [
                    'class' => 'sportifs-select',
                    'data-controller' => 'seance-sportifs'
                ])
                ->setHelp('<span id="sportifs-help">Sélectionnez jusqu\'à 3 sportifs.</span>'),
            AssociationField::new('exercices')
                ->setLabel("Exercices")
                ->setFormTypeOption('choice_label', 'nom'),
            ChoiceField::new('niveau_seance')
                ->setChoices([
                    'Débutant' => 'débutant',
                    'Intérmédiaire' => 'intermédiaire',
                    'Avancée' => 'avancé',
                ])
                ->setLabel("Niveau de la séance"),
            ChoiceField::new('statut')
                ->setChoices([
                    'Prévue' => 'prévue',
                    'Validée' => 'validée',
                    'Annulée' => 'annulée',
                ])
                ->setLabel("Statut"),
        ];
    }

   public function configureAssets(Assets $assets): Assets
    {
    return $assets
        ->addHtmlContentToBody('
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Récupérer la valeur initiale du type de séance
                    const typeSeanceSelect = document.querySelector(".type-seance-select");
                    const initialType = typeSeanceSelect ? typeSeanceSelect.value : "";
                    
                    // Mise à jour initiale du message d\'aide
                    updateSportifLimit(initialType);
                    
                    // Surveillance des changements de sélection de sportifs
                    let observer = new MutationObserver(function(mutations) {
                        checkSportifLimit();
                    });
                    
                    // Démarrer l\'observation après initialisation de TomSelect
                    setTimeout(function() {
                        const sportifSelect = document.querySelector(".sportifs-select");
                        if (sportifSelect && sportifSelect.tomselect) {
                            observer.observe(sportifSelect.parentNode, { 
                                childList: true, 
                                subtree: true 
                            });
                            
                            // Ajouter un écouteur d\'événement pour la sélection
                            sportifSelect.tomselect.on("item_add", function() {
                                checkSportifLimit();
                            });
                        }
                    }, 500);
                });
                
                function updateSportifLimit(typeSeance) {
                    const helpText = document.getElementById("sportifs-help");
                    let maxSportifs = 0;
                    
                    if (!typeSeance || typeSeance === "") {
                        helpText.innerHTML = "<strong>Veuillez d\'abord sélectionner un type de séance</strong> pour définir le nombre de sportifs autorisés.";
                    } else if (typeSeance === "solo") {
                        maxSportifs = 1;
                        helpText.innerHTML = "Type <strong>Solo</strong>: Sélectionnez <strong>1 seul</strong> sportif.";
                    } else if (typeSeance === "duo") {
                        maxSportifs = 2;
                        helpText.innerHTML = "Type <strong>Duo</strong>: Sélectionnez <strong>2 sportifs maximum</strong>.";
                    } else if (typeSeance === "trio") {
                        maxSportifs = 3;
                        helpText.innerHTML = "Type <strong>Trio</strong>: Sélectionnez <strong>3 sportifs maximum</strong>.";
                    }
                    
                    // Mise à jour du max-items si TomSelect est initialisé
                    const sportifSelect = document.querySelector(".sportifs-select");
                    if (sportifSelect && sportifSelect.tomselect) {
                        sportifSelect.tomselect.settings.maxItems = maxSportifs || 1; // Au moins 1 pour permettre la sélection
                        
                        // Si aucun type n\'est sélectionné, désactiver temporairement la sélection
                        if (!typeSeance || typeSeance === "") {
                            sportifSelect.tomselect.disable();
                            sportifSelect.tomselect.control.classList.add("disabled-select");
                        } else {
                            sportifSelect.tomselect.enable();
                            sportifSelect.tomselect.control.classList.remove("disabled-select");
                        }
                        
                        // Vérifier la sélection actuelle
                        checkSportifLimit();
                    }
                    
                    return maxSportifs;
                }
                
                function checkSportifLimit() {
                    const typeSeanceSelect = document.querySelector(".type-seance-select");
                    const typeSeance = typeSeanceSelect ? typeSeanceSelect.value : "";
                    const sportifSelect = document.querySelector(".sportifs-select");
                    
                    if (!sportifSelect || !sportifSelect.tomselect) return;
                    
                    const selectedCount = sportifSelect.tomselect.items.length;
                    let maxSportifs = 0;
                    
                    if (!typeSeance || typeSeance === "") {
                        maxSportifs = 0;
                    } else if (typeSeance === "solo") {
                        maxSportifs = 1;
                    } else if (typeSeance === "duo") {
                        maxSportifs = 2;
                    } else if (typeSeance === "trio") {
                        maxSportifs = 3;
                    }
                    
                    // Vérifier si trop de sportifs sont sélectionnés ou si aucun type n\'est sélectionné
                    if ((!typeSeance || typeSeance === "") && selectedCount > 0) {
                        // Message pour indiquer qu\'il faut d\'abord sélectionner un type
                        if (!document.getElementById("sportif-error-message")) {
                            const errorDiv = document.createElement("div");
                            errorDiv.id = "sportif-error-message";
                            errorDiv.className = "invalid-feedback d-block";
                            errorDiv.style.color = "orange";
                            errorDiv.textContent = "Veuillez d\'abord sélectionner un type de séance avant de choisir des sportifs.";
                            sportifSelect.parentNode.appendChild(errorDiv);
                            
                            // Indiquer visuellement
                            sportifSelect.tomselect.control.classList.add("is-warning");
                        }
                    } else if (maxSportifs > 0 && selectedCount > maxSportifs) {
                        // Message d\'erreur pour trop de sportifs
                        if (!document.getElementById("sportif-error-message")) {
                            const errorDiv = document.createElement("div");
                            errorDiv.id = "sportif-error-message";
                            errorDiv.className = "invalid-feedback d-block";
                            errorDiv.style.color = "red";
                            errorDiv.textContent = `Erreur: Vous avez sélectionné ${selectedCount} sportifs, mais le type de séance "${typeSeance}" permet un maximum de ${maxSportifs} sportif(s).`;
                            sportifSelect.parentNode.appendChild(errorDiv);
                            
                            // Mettre en surbrillance le champ
                            sportifSelect.tomselect.control.classList.add("is-invalid");
                            sportifSelect.tomselect.control.classList.remove("is-warning");
                        }
                    } else {
                        // Supprimer le message d\'erreur s\'il existe
                        const errorMessage = document.getElementById("sportif-error-message");
                        if (errorMessage) {
                            errorMessage.remove();
                            sportifSelect.tomselect.control.classList.remove("is-invalid");
                            sportifSelect.tomselect.control.classList.remove("is-warning");
                        }
                    }
                }
            </script>
            <style>
                .disabled-select {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                .is-warning {
                    border-color: #fd7e14 !important;
                    background-color: rgba(253, 126, 20, 0.1);
                }
            </style>
        ');
    }
}