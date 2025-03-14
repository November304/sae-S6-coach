document.addEventListener('DOMContentLoaded', function () {
    const coachSelect = document.querySelector('#FicheDePaie_coach');
    const coachField = document.querySelector('#FicheDePaie_coach-ts-control');
    const totalHeuresField = document.querySelector('#FicheDePaie_total_heures');
    const montantTotalField = document.querySelector('#FicheDePaie_montant_total');
    const tarifsCoachField = document.querySelector('.tarifs-coach-field');

    if (!coachField || !totalHeuresField || !montantTotalField || !tarifsCoachField) {
        return;
    }

    

    const tarifsCoach = JSON.parse(tarifsCoachField.getAttribute('value') || '{}');

    function updateMontantTotal() {
        console.log("Changement de tarif");
        const selectedOption = coachField.querySelector('div.item');
        const coachId = selectedOption.getAttribute('data-value');
        const totalHeures = parseInt(totalHeuresField.value) || 0;
        const tarifHoraire = tarifsCoach[coachId] || 0;
        montantTotalField.value = (totalHeures * tarifHoraire).toFixed(2);
    }

    coachSelect.addEventListener('change', updateMontantTotal);
    totalHeuresField.addEventListener('input', updateMontantTotal);
});
