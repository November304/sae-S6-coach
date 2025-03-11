export class Seance {
    constructor(
        public id: number,
        public date_heure: Date,
        public type_seance: string,
        public theme_seance: string,
        public coach_id: number,
        public sportifs: [],
        public exercices: [],
        public statut: string,
        public niveau_sceance: string,
    ) {}
}
