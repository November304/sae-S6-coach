import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../services/api.service';
import { Seance } from '../models/seance';
import { DatePipe } from '@angular/common';

interface BilanData {
  totalSeances: number;
  repartitionTypes: { [key: string]: number };
  topExercices: { nom: string; count: number }[];
  dureeTotal: number; // en minutes
  exerciceFrequence: { [key: string]: number };
}

interface ApiResponse {
  total_seances: number;
  total_temps: number;
  repartition_par_type: { [key: string]: number };
  top_exercices: { id: number; nom: string; count: number }[];
  seances?: Seance[]; // Optionnel, au cas où les séances sont aussi renvoyées
}

interface PeriodOption {
  label: string;
  value: string;
  days: number;
}

@Component({
  selector: 'app-bilan',
  templateUrl: './bilan.component.html',
  styleUrls: ['./bilan.component.css'],
  providers: [DatePipe],
})
export class BilanComponent implements OnInit {
  // Paramètres de l'URL
  idSportif: number | null = null;
  dateMin: string = '';
  dateMax: string = '';
  period: string = 'month';

  // Options de période
  periodOptions: PeriodOption[] = [
    { label: 'Semaine', value: 'week', days: 7 },
    { label: 'Mois', value: 'month', days: 30 },
    { label: 'Trimestre', value: 'quarter', days: 90 },
    { label: 'Année', value: 'year', days: 365 },
  ];

  // Données du bilan
  bilanData: BilanData = {
    totalSeances: 0,
    repartitionTypes: {},
    topExercices: [],
    dureeTotal: 0,
    exerciceFrequence: {},
  };

  // Données pour les graphiques
  chartData: any = {};

  // États de l'interface
  loading: boolean = true;
  error: string | null = null;
  showAdvancedOptions: boolean = false;
  comparaisonActive: boolean = false;
  periodePrec: BilanData | null = null;

  seances: Seance[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private apiService: ApiService,
    private datePipe: DatePipe
  ) {}

  ngOnInit(): void {
    this.route.queryParams.subscribe((params) => {
      this.idSportif = params['id_sportif']
        ? Number(params['id_sportif'])
        : null;
      this.dateMin = params['date_min'] || this.getDefaultDateMin();
      this.dateMax = params['date_max'] || this.getDefaultDateMax();
      this.period = params['period'] || 'month';

      this.loadBilan();
    });
  }

  getDefaultDateMin(): string {
    const date = new Date();
    date.setMonth(date.getMonth() - 1);
    return this.formatDate(date);
  }

  getDefaultDateMax(): string {
    return this.formatDate(new Date());
  }

  formatDate(date: Date): string {
    return this.datePipe.transform(date, 'yyyy-MM-dd') || '';
  }

  loadBilan(): void {
    this.loading = true;
    this.error = null;

    if (!this.idSportif) {
      this.error = "L'identifiant du sportif est manquant";
      this.loading = false;
      return;
    }

    // Appel à l'API pour récupérer les statistiques dans la période spécifiée
    this.apiService.getStatsByDateRange(this.dateMin, this.dateMax).subscribe({
      next: (data: any) => {
        // On traite la réponse comme un ApiResponse
        const apiResponse = data as ApiResponse;

        // Transformation des données de l'API vers notre format BilanData
        this.bilanData = {
          totalSeances: apiResponse.total_seances,
          repartitionTypes: apiResponse.repartition_par_type,
          topExercices: apiResponse.top_exercices.map((ex) => ({
            nom: ex.nom,
            count: ex.count,
          })),
          dureeTotal: apiResponse.total_temps,
          exerciceFrequence: apiResponse.top_exercices.reduce((acc, ex) => {
            acc[ex.nom] = ex.count;
            return acc;
          }, {} as { [key: string]: number }),
        };

        // Si la réponse contient aussi les séances, on les conserve
        if (apiResponse.seances) {
          this.seances = apiResponse.seances;
        }

        this.prepareChartData();
        this.loading = false;

        // Si la comparaison est active, charger les données de la période précédente
        if (this.comparaisonActive) {
          this.loadPeriodePrecedente();
        }
      },
      error: (err) => {
        console.error('Erreur lors du chargement des données du bilan', err);
        this.error = 'Erreur lors du chargement des données';
        this.loading = false;
      },
    });
  }

  prepareChartData(): void {
    // Données pour le graphique de répartition des types de séances
    const typeLabels = Object.keys(this.bilanData.repartitionTypes);
    const typeValues = typeLabels.map(
      (label) => this.bilanData.repartitionTypes[label]
    );

    this.chartData = {
      typeSeances: {
        labels: typeLabels,
        datasets: [
          {
            data: typeValues,
            backgroundColor: [
              '#FF6384',
              '#36A2EB',
              '#FFCE56',
              '#4BC0C0',
              '#9966FF',
              '#FF9F40',
            ],
          },
        ],
      },
    };
  }

  setPeriod(period: string): void {
    const today = new Date();
    let startDate = new Date();

    // Déterminer la date de début en fonction de la période
    switch (period) {
      case 'week':
        startDate.setDate(today.getDate() - 7);
        break;
      case 'month':
        startDate.setMonth(today.getMonth() - 1);
        break;
      case 'quarter':
        startDate.setMonth(today.getMonth() - 3);
        break;
      case 'year':
        startDate.setFullYear(today.getFullYear() - 1);
        break;
    }

    this.dateMin = this.formatDate(startDate);
    this.dateMax = this.formatDate(today);
    this.period = period;

    // Mettre à jour l'URL avec les nouveaux paramètres
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {
        id_sportif: this.idSportif,
        date_min: this.dateMin,
        date_max: this.dateMax,
        period: this.period,
      },
      queryParamsHandling: 'merge',
    });

    // Recharger les données
    this.loadBilan();
  }

  toggleAdvancedOptions(): void {
    this.showAdvancedOptions = !this.showAdvancedOptions;
  }

  applyCustomPeriod(startDate: string, endDate: string): void {
    this.dateMin = startDate;
    this.dateMax = endDate;
    this.period = 'custom';

    // Mettre à jour l'URL avec les nouveaux paramètres
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {
        id_sportif: this.idSportif,
        date_min: this.dateMin,
        date_max: this.dateMax,
        period: this.period,
      },
      queryParamsHandling: 'merge',
    });

    // Recharger les données
    this.loadBilan();
  }

  toggleComparaison(): void {
    this.comparaisonActive = !this.comparaisonActive;

    if (this.comparaisonActive) {
      this.loadPeriodePrecedente();
    } else {
      this.periodePrec = null;
    }
  }

  loadPeriodePrecedente(): void {
    // Calculer la période précédente équivalente
    const dateMin = new Date(this.dateMin);
    const dateMax = new Date(this.dateMax);
    const duree = dateMax.getTime() - dateMin.getTime();

    const dateMinPrec = new Date(dateMin.getTime() - duree);
    const dateMaxPrec = new Date(dateMax.getTime() - duree);

    // Formater les dates
    const dateMinPrecStr = this.formatDate(dateMinPrec);
    const dateMaxPrecStr = this.formatDate(dateMaxPrec);

    // Appel à l'API pour la période précédente
    this.apiService
      .getStatsByDateRange(dateMinPrecStr, dateMaxPrecStr)
      .subscribe({
        next: (data: any) => {
          const apiResponse = data as ApiResponse;

          // Transformation des données de l'API vers notre format BilanData
          this.periodePrec = {
            totalSeances: apiResponse.total_seances,
            repartitionTypes: apiResponse.repartition_par_type,
            topExercices: apiResponse.top_exercices.map((ex) => ({
              nom: ex.nom,
              count: ex.count,
            })),
            dureeTotal: apiResponse.total_temps,
            exerciceFrequence: apiResponse.top_exercices.reduce((acc, ex) => {
              acc[ex.nom] = ex.count;
              return acc;
            }, {} as { [key: string]: number }),
          };
        },
        error: (err) => {
          console.error(
            'Erreur lors du chargement des données de la période précédente',
            err
          );
          this.periodePrec = null;
        },
      });
  }

  // Calculer le pourcentage d'évolution entre deux valeurs
  calculateEvolution(
    current: number,
    previous: number
  ): { value: number; isPositive: boolean } {
    if (previous === 0) return { value: 0, isPositive: true };

    const evolution = ((current - previous) / previous) * 100;
    return {
      value: Math.abs(evolution),
      isPositive: evolution >= 0,
    };
  }

  // Convertir les minutes en format heures:minutes
  formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h${mins < 10 ? '0' + mins : mins}`;
  }

  // Méthode utilitaire pour itérer sur les clés d'un objet dans le template
  getObjectKeys(obj: any): string[] {
    return Object.keys(obj);
  }

  // Méthode pour obtenir une couleur pour le graphique pie chart
  getPieChartColor(index: number): string {
    const colors = [
      '#FF6384',
      '#36A2EB',
      '#FFCE56',
      '#4BC0C0',
      '#9966FF',
      '#FF9F40',
    ];
    return colors[index % colors.length];
  }
}
