import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Sportif } from '../models/sportif';
import { Seance } from '../models/seance';
import { forkJoin } from 'rxjs';

@Component({
  selector: 'app-espace-perso',
  templateUrl: './espace-perso.component.html',
  styleUrl: './espace-perso.component.css',
})
export class EspacePersoComponent implements OnInit {
  sportif: Sportif | null = null;
  seances: Seance[] = [];
  loading: boolean = true;
  error: string | null = null;

  // Pour les statistiques
  nombreTotalSeances: number = 0;
  typesExercices: { [key: string]: number } = {};
  typesSeances: { [key: string]: number } = {};

  // Pour l'affichage des listes
  showAllSeanceTypes: boolean = false;
  showAllExerciceTypes: boolean = false;
  expandedSeances: boolean[] = [];

  constructor(private apiService: ApiService) {}

  ngOnInit(): void {
    this.loadUserData();
  }

  // Méthodes pour utiliser dans le template
  hasExerciceTypes(): boolean {
    return Object.keys(this.typesExercices).length > 0;
  }

  toggleSeanceExpand(index: number): void {
    this.expandedSeances[index] = !this.expandedSeances[index];
  }

  getTotalExercices(): number {
    return Object.values(this.typesExercices).reduce((a, b) => a + b, 0);
  }

  // Méthode pour obtenir les N premiers éléments d'un objet
  getTopItems(
    obj: { [key: string]: number },
    count: number
  ): { [key: string]: number } {
    const entries = Object.entries(obj)
      .sort((a, b) => b[1] - a[1]) // Trie par valeur décroissante
      .slice(0, count);
    return Object.fromEntries(entries);
  }

  // Méthode pour obtenir le reste des éléments
  getRestItems(
    obj: { [key: string]: number },
    count: number
  ): { [key: string]: number } {
    const entries = Object.entries(obj)
      .sort((a, b) => b[1] - a[1]) // Trie par valeur décroissante
      .slice(count);
    return Object.fromEntries(entries);
  }

  private loadUserData(): void {
    // Utiliser forkJoin pour faire les deux appels API en parallèle
    forkJoin({
      sportif: this.apiService.getSportifMe(),
      seances: this.apiService.getMySeances(),
    }).subscribe({
      next: (results) => {
        // Il y a une différence entre le type retourné par l'API et ce que nous attendons
        // L'API renvoie un tableau pour getSportifMe() mais nous attendons un seul objet
        if (Array.isArray(results.sportif) && results.sportif.length > 0) {
          this.sportif = results.sportif[0];
        } else {
          this.sportif = results.sportif as unknown as Sportif;
        }

        // Pour les séances, vérifier le format de la réponse
        console.log('API response for seances:', results.seances);

        // Si la réponse est un tableau direct
        if (Array.isArray(results.seances)) {
          this.seances = results.seances;
        }
        // Si la réponse est un objet contenant un tableau "seances"
        else if (results.seances && results.seances.hasOwnProperty('seances')) {
          this.seances = (results.seances as any).seances;
        }
        // Si d'autres formats
        else {
          this.seances = [];
          console.error(
            'Format de réponse inattendu pour les séances:',
            results.seances
          );
        }

        // Initialiser le tableau d'états d'expansion des séances
        this.expandedSeances = new Array(this.seances.length).fill(false);

        this.calculerStatistiques();
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur lors du chargement des données', err);
        this.error = 'Erreur lors du chargement des données';
        this.loading = false;
      },
    });
  }

  getObjectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

  private calculerStatistiques(): void {
    this.nombreTotalSeances = this.seances.length;

    // Calculer le nombre de séances par type
    this.typesSeances = {};
    this.seances.forEach((seance) => {
      const typeSeance = seance.type_seance || 'Non spécifié';

      if (this.typesSeances[typeSeance]) {
        this.typesSeances[typeSeance]++;
      } else {
        this.typesSeances[typeSeance] = 1;
      }
    });

    // Calculer le nombre d'exercices par type
    this.typesExercices = {};
    this.seances.forEach((seance) => {
      if (seance.exercices && seance.exercices.length > 0) {
        seance.exercices.forEach((exercice) => {
          const nomExercice = exercice.nom || 'Non spécifié';

          if (this.typesExercices[nomExercice]) {
            this.typesExercices[nomExercice]++;
          } else {
            this.typesExercices[nomExercice] = 1;
          }
        });
      }
    });
  }
}
