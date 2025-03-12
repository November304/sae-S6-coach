export class Utilisateur {
    constructor(
        public id: number,
        public nom: string,
        public prenom: string,
        public email: string,
        public mot_de_passe: string,
        public role: string, 
    ) {}     
}
