import { Component, OnInit, AfterViewInit, OnDestroy } from '@angular/core';
import { CalendarView, CalendarEvent, CalendarDateFormatter, DateFormatterParams } from 'angular-calendar';
import { addHours, addDays, subDays } from 'date-fns';
import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';
import { Seance } from '../models/seance';
import { ApiService } from '../services/api.service';
import { Coach } from '../models/coach';
import { AuthService } from '../services/auth.service';

declare var $: any;

registerLocaleData(localeFr);

export class CustomDateFormatter extends CalendarDateFormatter {
  override weekViewHour({ date }: DateFormatterParams): string {
    return new Intl.DateTimeFormat('fr-FR', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(date);
  }
}

@Component({
  selector: 'app-planning',
  templateUrl: './planning.component.html',
  styleUrls: ['./planning.component.scss'],
  providers: [
    {
      provide: CalendarDateFormatter,
      useClass: CustomDateFormatter,
    },
  ],
})
export class PlanningComponent implements OnInit, AfterViewInit, OnDestroy {
  view: CalendarView = CalendarView.Week;
  viewDate: Date = new Date();
  dayStartHour = 1;
  dayEndHour = 24;
  hourSegments = 1;
  weekStartsOn = 1;

  coaches: Coach[] = [];
  seances: Seance[] = [];
  events: CalendarEvent[] = [];
  sessionTypes: string[] = [];
  sessionLevels: string[] = []
  selectedSeance: Seance | null = null;

  selectedCoachIds: number[] = [];
  selectedSessionTypes: string[] = [];
  selectedSessionLevels: string[] = [];

  constructor(
    private apiService: ApiService,
    public authService: AuthService,
  ) {}

  ngOnInit(): void {
    const isLogged = this.authService.currentAuthUserValue.isLogged();

    if (!isLogged) {
      this.apiService.getSeanceListPublic().subscribe((data: Seance[]) => {
        this.seances = data;
        this.extractSessionTypes();
        this.extractSessionLevels();
        this.updateEvents();
      });
    } else {
      this.apiService.getSeanceList().subscribe((data: Seance[]) => {
        this.seances = data;
        this.extractSessionTypes();
        this.extractSessionLevels();
        this.updateEvents();
      });
    }

  
    this.apiService.getCoachsList().subscribe((data: Coach[]) => {
      this.coaches = data;
    });
  }
  

  ngAfterViewInit(): void {
    setTimeout(() => {
      $('#coach').select2({
        width: '100%',
        placeholder: 'Choisissez un ou plusieurs coachs',
        allowClear: true
      }).on('change', (event: any) => this.onCoachChange(event));
  
      $('#sessionType').select2({
        width: '100%',
        placeholder: 'Choisissez un ou plusieurs types de séance',
        allowClear: true
      }).on('change', (event: any) => this.onSessionTypeChange(event));
  
      $('#niveauSession').select2({
        width: '100%',
        placeholder: 'Choisissez un ou plusieurs niveaux',
        allowClear: true
      }).on('change', (event: any) => this.onSessionLevelChange(event));
    }, 0);
  }  

  ngOnDestroy(): void {
    $('#coach').select2('destroy');
    $('#sessionType').select2('destroy');
    $('#niveauSession').select2('destroy');
  }

  extractSessionTypes(): void {
    const types = new Set<string>();
    this.seances.forEach(seance => {
      if (seance.type_seance) {
        types.add(seance.type_seance);
      }
    });
    this.sessionTypes = Array.from(types);
  }

  extractSessionLevels(): void {
    const levels = new Set<string>();
    this.seances.forEach(seance => {
      if (seance.niveau_seance) {
        levels.add(seance.niveau_seance);
      }
    });
    this.sessionLevels = Array.from(levels);
  }

  updateEvents(): void {
    let filteredSeances = this.seances;
  
    if (this.selectedCoachIds.length > 0) {
      filteredSeances = filteredSeances.filter(seance => 
        seance.coach && this.selectedCoachIds.includes(seance.coach.id)
      );
    }
  
    if (this.selectedSessionTypes.length > 0) {
      filteredSeances = filteredSeances.filter(seance => 
        this.selectedSessionTypes.includes(seance.type_seance)
      );
    }
  
    if (this.selectedSessionLevels.length > 0) {
      filteredSeances = filteredSeances.filter(seance => 
        this.selectedSessionLevels.includes(seance.niveau_seance)
      );
    }
  
    this.events = this.transformSeancesToEvents(filteredSeances);
  }

  onCoachChange(event: any): void {
    const selectedValues = $('#coach').val();
    this.selectedCoachIds = selectedValues ? selectedValues.map(Number) : [];
    this.updateEvents();
  }

  onSessionTypeChange(event: any): void {
    const selectedValues = $('#sessionType').val();
    this.selectedSessionTypes = selectedValues ? selectedValues.map(String) : [];
    this.updateEvents();
  }
  
  onSessionLevelChange(event: any): void {
    const selectedValues = $('#niveauSession').val();
    this.selectedSessionLevels = selectedValues ? selectedValues.map(String) : [];
    this.updateEvents();
  }  

  transformSeancesToEvents(seances: Seance[]): CalendarEvent[] {
    return seances.map(seance => {
      let startDate = new Date(seance.date_heure);
      const endDate = addHours(startDate, 1);
      const hours = startDate.getUTCHours().toString().padStart(2, '0');
      const minutes = startDate.getUTCMinutes().toString().padStart(2, '0');

      return {
        start: startDate,
        end: endDate,
        title: `${seance.theme_seance} - ${seance.type_seance} (${hours}h${minutes})`,
        color: { primary: '#d9230f', secondary: '#d9230f' },
        meta: { id: seance.id }
      };
    });
  }

  previousWeek(): void {
    this.viewDate = subDays(this.viewDate, 7);
  }

  nextWeek(): void {
    this.viewDate = addDays(this.viewDate, 7);
  }

  onEventClick({ event }: { event: CalendarEvent }): void {
    const seance = this.seances.find(s => s.id === event.meta.id);
  
    if (seance) {
      this.selectedSeance = seance;
      console.log(seance);
    } else {
      console.error("Séance non trouvée !");
    }
  }

  CalendarView = CalendarView;
}