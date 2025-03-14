document.addEventListener("DOMContentLoaded", function () {
  const typeSeanceSelect = document.querySelector(".type-seance-select");
  const sportifSelect = document.querySelector(".sportifs-select");

  // Met à jour le nombre maximum autorisé dès le chargement ou lors d'un changement de type de séance.
  if (typeSeanceSelect) {
    updateSportifLimit(typeSeanceSelect.value);
    typeSeanceSelect.addEventListener("change", function () {
      updateSportifLimit(this.value);
    });
  }

  // Créer le conteneur pour les messages
  const helpContainer = document.createElement("div");
  helpContainer.id = "seance-help-container";
  document
    .querySelector(".sportifs-select")
    .parentNode.insertBefore(
      helpContainer,
      document.querySelector(".sportifs-select")
    );

  // Déplacer le message existant et ajouter le nouveau
  const existingHelp = document.getElementById("sportifs-help");
  const niveauHelpHtml = `
        <div id="niveau-help" class="form-help mt-2">
            <strong>Veuillez d'abord sélectionner un niveau de séance</strong>
        </div>
    `;

  helpContainer.appendChild(existingHelp);
  helpContainer.insertAdjacentHTML("beforeend", niveauHelpHtml);

  // Écouteurs pour le niveau
  const niveauSeanceSelect = document.querySelector(".niveau-seance-select");
  if (niveauSeanceSelect) {
    niveauSeanceSelect.addEventListener("change", function () {
      checkNiveauSportif();
      updateNiveauHelp(this.value);
    });
    updateNiveauHelp(niveauSeanceSelect.value);
  }

  // Modifier les écouteurs TomSelect pour inclure la vérification des niveaux
  if (sportifSelect) {
    const interval = setInterval(() => {
      if (sportifSelect.tomselect) {
        clearInterval(interval);
        sportifSelect.tomselect.on("item_add", function () {
          checkSportifLimit();
          checkNiveauSportif();
        });
        sportifSelect.tomselect.on("item_remove", function () {
          checkSportifLimit();
          checkNiveauSportif();
        });
      }
    }, 100);
  }

  const exercicesSelect = document.querySelector(
    '[data-controller="exercices-duree"]'
  );
  const dureeTotalField = document.getElementById("dureeEstimeeTotal");
  const dureePreview = document.getElementById("duree-preview");

  function updateDureeTotale() {
    let totalMinutes = 0;
    const selectedOptions = exercicesSelect.selectedOptions;

    Array.from(selectedOptions).forEach((option) => {
      totalMinutes += parseInt(option.dataset.duree) || 0;
    });

    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    // Format différent pour l'affichage en temps réel vs. la valeur du champ
    const formatted = hours
      ? `${hours}h${minutes.toString().padStart(2, "0")}`
      : `${minutes} min`;

    // Mise à jour du champ caché
    if (dureeTotalField) {
      dureeTotalField.value = formatted;
    }

    // Mise à jour de la prévisualisation
    if (dureePreview) {
      dureePreview.innerHTML = `Durée totale estimée : <strong>${formatted}</strong>`;
    }
  }

  // Écouter les changements
  if (exercicesSelect) {
    exercicesSelect.addEventListener("change", updateDureeTotale);

    // Gérer TomSelect (choix multiples EasyAdmin)
    if (exercicesSelect.tomselect) {
      exercicesSelect.tomselect.on("change", updateDureeTotale);
    } else {
      const interval = setInterval(() => {
        if (exercicesSelect.tomselect) {
          clearInterval(interval);
          exercicesSelect.tomselect.on("change", updateDureeTotale);
          // Mise à jour initiale après l'initialisation de TomSelect
          updateDureeTotale();
        }
      }, 100);
    }

    // Mise à jour initiale
    updateDureeTotale();
  }
});

// Modifier updateNiveauHelp pour utiliser le même style
function updateNiveauHelp(niveau) {
  const niveauHelp = document.getElementById("niveau-help");
  const niveauxLabels = {
    débutant: "Débutant",
    intermédiaire: "Intermédiaire",
    avancé: "Avancé",
  };

  if (!niveau) {
    niveauHelp.innerHTML =
      "<strong>Sélectionnez un niveau de séance</strong> pour choisir les sportifs appropriés.";
  } else {
    niveauHelp.innerHTML = `Niveau <strong>${niveauxLabels[niveau]}</strong> : 
            Sélectionnez uniquement des athlètes de niveau <strong>${niveauxLabels[niveau]}</strong>.`;
  }
}

// Modifier la fonction checkNiveauSportif
function checkNiveauSportif() {
  const niveauSeanceSelect = document.querySelector(".niveau-seance-select");
  const niveauSeance = niveauSeanceSelect ? niveauSeanceSelect.value : null;
  const sportifSelect = document.querySelector(".sportifs-select");

  // Réinitialiser les erreurs
  let errorDiv = document.getElementById("niveau-error-message");
  if (errorDiv) errorDiv.remove();

  if (!niveauSeance || !sportifSelect?.tomselect) return;

  const selectedSportifs = sportifSelect.tomselect.items;
  let invalidSportifs = [];

  // Vérifier chaque sportif sélectionné
  selectedSportifs.forEach((sportifId) => {
    const option = sportifSelect.querySelector(`option[value="${sportifId}"]`);
    if (option && option.dataset.level !== niveauSeance) {
      invalidSportifs.push({
        name: option.textContent,
        level: option.dataset.level,
      });
    }
  });

  // Afficher l'erreur si nécessaire
  if (invalidSportifs.length > 0) {
    errorDiv = document.createElement("div");
    errorDiv.id = "niveau-error-message";
    errorDiv.className = "invalid-feedback d-block mt-2";
    errorDiv.style.color = "red";

    const errorList = invalidSportifs.map((s) => `• ${s.name}`).join("<br>");

    errorDiv.innerHTML = `
            <strong>Incompatibilité de niveau :</strong><br>
            ${errorList}
        `;

    // Ajouter après les messages d'aide
    document.getElementById("seance-help-container").appendChild(errorDiv);
  }
}

function updateSportifLimit(typeSeance) {
  const helpText = document.getElementById("sportifs-help");
  let maxSportifs = 0;

  if (!typeSeance || typeSeance === "") {
    helpText.innerHTML =
      "<strong>Veuillez d'abord sélectionner un type de séance</strong> pour définir le nombre de sportifs autorisés.";
  } else if (typeSeance === "solo") {
    maxSportifs = 1;
    helpText.innerHTML =
      "Type <strong>Solo</strong>: Sélectionnez <strong>1 seul</strong> sportif.";
  } else if (typeSeance === "duo") {
    maxSportifs = 2;
    helpText.innerHTML =
      "Type <strong>Duo</strong>: Sélectionnez <strong>2 sportifs maximum</strong>.";
  } else if (typeSeance === "trio") {
    maxSportifs = 3;
    helpText.innerHTML =
      "Type <strong>Trio</strong>: Sélectionnez <strong>3 sportifs maximum</strong>.";
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
window.checkNiveauSportif = checkNiveauSportif;
