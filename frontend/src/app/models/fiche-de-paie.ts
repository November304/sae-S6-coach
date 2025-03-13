export class FicheDePaie {
  constructor(
    public id: number,
    public coach: number,
    public periode: string,
    public total_heures: number,
    public montant_total: number
  ) {}
}
