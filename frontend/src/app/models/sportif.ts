import { Utilisateur } from "./utilisateur";

export class Sportif extends Utilisateur {
    constructor(
        public date_inscription: Date,
        public niveau_sportif: string,
        data: Partial<Utilisateur>
    ) {
        super(
            data.id!,
            data.nom!,
            data.prenom!,
            data.email!,
            data.mot_de_passe!,
            data.role!
        );
    }
}