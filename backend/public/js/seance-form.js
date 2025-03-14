document.addEventListener("DOMContentLoaded", function() {
    const typeSeanceSelect = document.querySelector(".type-seance-select");
    const sportifSelect = document.querySelector(".sportifs-select");

    // Met à jour le nombre maximum autorisé dès le chargement ou lors d'un changement de type de séance.
    if (typeSeanceSelect) {
        updateSportifLimit(typeSeanceSelect.value);
        typeSeanceSelect.addEventListener("change", function() {
            updateSportifLimit(this.value);
        });
    }

    // Attendre que TomSelect soit initialisé et ajouter des écouteurs d'événements.
    if (sportifSelect) {
        const interval = setInterval(() => {
            if (sportifSelect.tomselect) {
                clearInterval(interval);
                sportifSelect.tomselect.on("item_add", function() {
                    checkSportifLimit();
                });
                sportifSelect.tomselect.on("item_remove", function() {
                    checkSportifLimit();
                });
            }
        }, 100);
    }
});

function updateSportifLimit(typeSeance) {
    const helpText = document.getElementById("sportifs-help");
    let maxSportifs = 0;
    
    if (!typeSeance || typeSeance === "") {
        helpText.innerHTML = "<strong>Veuillez d'abord sélectionner un type de séance</strong> pour définir le nombre de sportifs autorisés.";
    } else if (typeSeance === "solo") {
        maxSportifs = 1;
        helpText.innerHTML = "Type <strong>Solo</strong>: Sélectionnez <strong>1 seul</strong> sportif.";
    } else if (typeSeance === "duo") {
        maxSportifs = 2;
        helpText.innerHTML = "Type <strong>Duo</strong>: Sélectionnez <strong>2 sportifs maximum</strong>.";
    } else if (typeSeance === "trio") {
        maxSportifs = 3;
        helpText.innerHTML = "Type <strong>Trio</strong>: Sélectionnez <strong>3 sportifs maximum</strong>.";
    }
    
    const sportifSelect = document.querySelector(".sportifs-select");
    if (sportifSelect && sportifSelect.tomselect) {
        sportifSelect.tomselect.settings.maxItems = maxSportifs || 1;
        if (!typeSeance || typeSeance === "") {
            sportifSelect.tomselect.disable();
            sportifSelect.tomselect.control.classList.add("disabled-select");
        } else {
            sportifSelect.tomselect.enable();
            sportifSelect.tomselect.control.classList.remove("disabled-select");
        }
        checkSportifLimit();
    }
    
    return maxSportifs;
}

function checkSportifLimit() {
    const typeSeanceSelect = document.querySelector(".type-seance-select");
    const typeSeance = typeSeanceSelect ? typeSeanceSelect.value : "";
    const sportifSelect = document.querySelector(".sportifs-select");
    
    if (!sportifSelect || !sportifSelect.tomselect) return;
    
    const selectedCount = sportifSelect.tomselect.items.length;
    let maxSportifs = 0;
    
    if (!typeSeance || typeSeance === "") {
        maxSportifs = 0;
    } else if (typeSeance === "solo") {
        maxSportifs = 1;
    } else if (typeSeance === "duo") {
        maxSportifs = 2;
    } else if (typeSeance === "trio") {
        maxSportifs = 3;
    }
    
    let errorDiv = document.getElementById("sportif-error-message");
    
    if (selectedCount > maxSportifs) {
        if (!errorDiv) {
            errorDiv = document.createElement("div");
            errorDiv.id = "sportif-error-message";
            errorDiv.className = "invalid-feedback d-block";
            sportifSelect.parentNode.appendChild(errorDiv);
        }
        errorDiv.style.color = "red";
        errorDiv.textContent = `Erreur: Vous avez sélectionné ${selectedCount} sportifs, mais le type de séance "${typeSeance}" permet un maximum de ${maxSportifs} sportif(s).`;
        sportifSelect.tomselect.control.classList.add("is-invalid");
        sportifSelect.tomselect.control.classList.remove("is-warning");
    } else {
        if (errorDiv) {
            errorDiv.remove();
        }
        sportifSelect.tomselect.control.classList.remove("is-invalid");
        sportifSelect.tomselect.control.classList.remove("is-warning");
    }
}

window.updateSportifLimit = updateSportifLimit;
window.checkSportifLimit = checkSportifLimit;
