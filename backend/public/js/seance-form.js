document.addEventListener('DOMContentLoaded', function() {
    const typeSeanceSelect = document.querySelector('select[name="Seance[type_seance]"]');
    const sportifsSelect = document.querySelector('select[name="Seance[sportifs][]"]');
    
    if (typeSeanceSelect && sportifsSelect) {
        // Fonction pour mettre à jour les restrictions
        function updateSportifRestrictions() {
            const typeSeance = typeSeanceSelect.value;
            let maxSportifs = 3; // Par défaut (trio)
            
            if (typeSeance === 'solo') {
                maxSportifs = 1;
            } else if (typeSeance === 'duo') {
                maxSportifs = 2;
            }
            
            // Mettre à jour l'attribut pour TomSelect
            sportifsSelect.setAttribute('data-max-items', maxSportifs);
            
            // Si TomSelect est déjà initialisé
            if (sportifsSelect.tomselect) {
                sportifsSelect.tomselect.settings.maxItems = maxSportifs;
                
                // Vérifier si le nombre actuel de sélections dépasse la nouvelle limite
                const selectedItems = sportifsSelect.tomselect.items;
                if (selectedItems.length > maxSportifs) {
                    // Garder seulement les premiers éléments jusqu'à la limite
                    const itemsToKeep = selectedItems.slice(0, maxSportifs);
                    // Désélectionner les éléments en trop
                    selectedItems.forEach(item => {
                        if (!itemsToKeep.includes(item)) {
                            sportifsSelect.tomselect.removeItem(item, true);
                        }
                    });
                }
            }
            
            // Ajouter un message d'information
            const infoMessage = document.getElementById('sportif-limit-info');
            if (!infoMessage) {
                const infoDiv = document.createElement('div');
                infoDiv.id = 'sportif-limit-info';
                infoDiv.className = 'form-text text-muted';
                infoDiv.textContent = `Type de séance: ${typeSeance} - Maximum ${maxSportifs} sportif(s)`;
                sportifsSelect.parentNode.appendChild(infoDiv);
            } else {
                infoMessage.textContent = `Type de séance: ${typeSeance} - Maximum ${maxSportifs} sportif(s)`;
            }
        }
        
        // Appliquer au chargement initial
        updateSportifRestrictions();
        
        // Appliquer lors du changement de type de séance
        typeSeanceSelect.addEventListener('change', updateSportifRestrictions);
        
        // Observer les changements de TomSelect (pour les cas où il est initialisé après)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    if (document.querySelector('.ts-wrapper') && !sportifsSelect.tomselect) {
                        // TomSelect a été initialisé, attendre un peu puis réappliquer les restrictions
                        setTimeout(updateSportifRestrictions, 100);
                    }
                }
            });
        });
        
        observer.observe(sportifsSelect.parentNode, { childList: true, subtree: true });
    }
});