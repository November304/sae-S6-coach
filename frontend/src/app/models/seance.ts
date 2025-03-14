import { Coach } from "./coach";
import { Exercice } from "./exercice";
export class Seance {
  constructor(
    public id: number,
    public date_heure: Date,
    public type_seance: string,
    public theme_seance: string,
    public coach: Coach,
    public sportifs: [],
    public exercices: Exercice[],
    public statut: string,
    public niveau_seance: string
  ) {}
}
