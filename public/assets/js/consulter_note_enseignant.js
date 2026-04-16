function openTab(evt, tabId) {
    const tabContents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
    }

    const tabs = document.getElementsByClassName("tab");
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
    }

    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function validerNote(input) {
    const value = parseFloat(input.value);
    
    if (input.value === "") {
        input.classList.remove("invalid");
        return true;
    }

    if (isNaN(value) || value < 0 || value > 20) {
        input.classList.add("invalid");
        alert("La note doit être un nombre entre 0 et 20.");
        return false;
    } else {
        input.classList.remove("invalid");
        return true;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const noteInputs = document.querySelectorAll('input[type="number"]');

    noteInputs.forEach(input => {
        input.addEventListener("change", function() {
            validerNote(this);
        });
    });

    const forms = document.querySelectorAll("form");
    forms.forEach(form => {
        form.addEventListener("submit", function(e) {
            let valid = true;
            const inputs = this.querySelectorAll('input[type="number"]');
            
            inputs.forEach(input => {
                if (!input.disabled && !validerNote(input)) {
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
                alert("Veuillez corriger les notes invalides avant de soumettre.");
            }
        });
    });
});
