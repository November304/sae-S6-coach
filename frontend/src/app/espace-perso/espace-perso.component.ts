import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { Sportif } from '../models/sportif';
import { Seance } from '../models/seance';
import { forkJoin } from 'rxjs';
import { Router } from '@angular/router';

@Component({
  selector: 'app-espace-perso',
  templateUrl: './espace-perso.component.html',
  styleUrl: './espace-perso.component.css',
})
export class EspacePersoComponent implements OnInit {
  sportif: Sportif | null = null;
  seances: Seance[] = [];
  seancesPrevues: Seance[] = [];
  seancesPassees: Seance[] = [];
  loading: boolean = true;
  error: string | null = null;
  cancellationLoading: { [id: number]: boolean } = {};

  seanceToCancel: number | null = null;
  showConfirmModal: boolean = false;

  nombreTotalSeances: number = 0;
  typesExercices: { [key: string]: number } = {};
  typesSeances: { [key: string]: number } = {};

  showAllSeanceTypes: boolean = false;
  showAllExerciceTypes: boolean = false;
  expandedSeances: boolean[] = [];

  constructor(private apiService: ApiService, private router: Router) {}

  ngOnInit(): void {
    this.loadUserData();
  }

  hasExerciceTypes(): boolean {
    return Object.keys(this.typesExercices).length > 0;
  }

  toggleSeanceExpand(index: number): void {
    this.expandedSeances[index] = !this.expandedSeances[index];
  }

  getTotalExercices(): number {
    return Object.values(this.typesExercices).reduce((a, b) => a + b, 0);
  }

  getTopItems(
    obj: { [key: string]: number },
    count: number
  ): { [key: string]: number } {
    const entries = Object.entries(obj)
      .sort((a, b) => b[1] - a[1])
      .slice(0, count);
    return Object.fromEntries(entries);
  }

  getRestItems(
    obj: { [key: string]: number },
    count: number
  ): { [key: string]: number } {
    const entries = Object.entries(obj)
      .sort((a, b) => b[1] - a[1])
      .slice(count);
    return Object.fromEntries(entries);
  }

  successMessage: string | null = null;

  annulerSeance(seanceId: number): void {
    if (confirm('Êtes-vous sûr de vouloir annuler cette séance ?')) {
      this.cancellationLoading[seanceId] = true;
      this.successMessage = null;

      this.apiService.UnreserveSeance(seanceId).subscribe({
        next: (response) => {
          this.seancesPrevues = this.seancesPrevues.filter(
            (seance) => seance.id !== seanceId
          );
          this.cancellationLoading[seanceId] = false;

          if (response && response.message) {
            this.successMessage = response.message;
          } else {
            this.successMessage = 'Vous avez bien été désinscrit de la séance';
          }

          setTimeout(() => {
            this.successMessage = null;
          }, 5000);
        },
        error: (err) => {
          console.error("Erreur lors de l'annulation de la séance", err);
          this.error = "Une erreur est survenue lors de l'annulation";
          this.cancellationLoading[seanceId] = false;
        },
      });
    }
  }

  private loadUserData(): void {
    forkJoin({
      sportif: this.apiService.getSportifMe(),
      seances: this.apiService.getMySeances(),
    }).subscribe({
      next: (results) => {
        if (Array.isArray(results.sportif) && results.sportif.length > 0) {
          this.sportif = results.sportif[0];
        } else {
          this.sportif = results.sportif as unknown as Sportif;
        }

        console.log('API response for seances:', results.seances);

        if (Array.isArray(results.seances)) {
          this.seances = results.seances;
        } else if (
          results.seances &&
          results.seances.hasOwnProperty('seances')
        ) {
          this.seances = (results.seances as any).seances;
        } else {
          this.seances = [];
          console.error(
            'Format de réponse inattendu pour les séances:',
            results.seances
          );
        }

        const maintenant = new Date();
        this.seancesPrevues = this.seances
          .filter((seance) => new Date(seance.date_heure) > maintenant)
          .sort(
            (a, b) =>
              new Date(a.date_heure).getTime() -
              new Date(b.date_heure).getTime()
          );

        this.seancesPassees = this.seances
          .filter((seance) => new Date(seance.date_heure) <= maintenant)
          .sort(
            (a, b) =>
              new Date(b.date_heure).getTime() -
              new Date(a.date_heure).getTime()
          );

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

  navigateToBilan(): void {
    // Obtenir la date actuelle
    const today = new Date();

    // Définir la date de début (1 mois en arrière par défaut)
    const startDate = new Date();
    startDate.setMonth(today.getMonth() - 1);

    // Formater les dates au format YYYY-MM-DD
    const dateMin = this.formatDate(startDate);
    const dateMax = this.formatDate(today);

    // Naviguer vers le composant bilan avec les paramètres
    this.router.navigate(['/bilan'], {
      queryParams: {
        id_sportif: this.sportif?.id,
        date_min: dateMin,
        date_max: dateMax,
        period: 'month', // Période par défaut (mois)
      },
    });
  }

  // Fonction utilitaire pour formater la date
  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  getObjectKeys(obj: object): string[] {
    return Object.keys(obj);
  }

  private calculerStatistiques(): void {
    this.nombreTotalSeances = this.seances.length;

    this.typesSeances = {};
    this.seances.forEach((seance) => {
      const typeSeance = seance.type_seance || 'Non spécifié';

      if (this.typesSeances[typeSeance]) {
        this.typesSeances[typeSeance]++;
      } else {
        this.typesSeances[typeSeance] = 1;
      }
    });

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

  openConfirmModal(seanceId: number): void {
    this.seanceToCancel = seanceId;
    this.showConfirmModal = true;
  }

  closeConfirmModal(): void {
    this.showConfirmModal = false;
    this.seanceToCancel = null;
  }

  confirmCancellation(): void {
    if (this.seanceToCancel !== null) {
      const seanceId = this.seanceToCancel;
      this.cancellationLoading[seanceId] = true;
      this.successMessage = null;

      this.apiService.UnreserveSeance(seanceId).subscribe({
        next: (response) => {
          this.seancesPrevues = this.seancesPrevues.filter(
            (seance) => seance.id !== seanceId
          );
          this.cancellationLoading[seanceId] = false;

          if (response && response.message) {
            this.successMessage = response.message;
          } else {
            this.successMessage = 'Vous avez bien été désinscrit de la séance';
          }

          setTimeout(() => {
            this.successMessage = null;
          }, 5000);
        },
        error: (err) => {
          console.error("Erreur lors de l'annulation de la séance", err);
          this.error = "Une erreur est survenue lors de l'annulation";
          this.cancellationLoading[seanceId] = false;
        },
        complete: () => {
          this.closeConfirmModal();
        },
      });
    }
  }
}
