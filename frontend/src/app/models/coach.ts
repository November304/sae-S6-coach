import { Utilisateur } from "./utilisateur";

export class Coach extends Utilisateur {
    constructor(
        public specialites: string[],
        public tarif_horaire: number,
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

