// planning.js
$(document).ready(function() {
    // Gestion dynamique des champs
    $('#type').change(function() {
        const type = $(this).val();
        
        // Masquer tous les champs et réinitialiser les validations
        $('#enseignantDiv, #etudiantDiv').hide();
        $('#id_enseignant, #niveau').prop('required', false);
        
        // Afficher le champ concerné et activer la validation
        if (type === 'enseignant') {
            $('#enseignantDiv').show();
            $('#id_enseignant').prop('required', true);
        } 
        else if (type === 'etudiant') {
            $('#etudiantDiv').show();
            $('#niveau').prop('required', true);
        }
    });

    // Gestion de l'affichage du nom du fichier
    $('input[type="file"]').change(function(e) {
        if (e.target.files.length > 0) {
            const fileName = e.target.files[0].name;
            $(this).siblings('.file-label').html(`📄 ${fileName}`);
            
            // Vérification de la taille du fichier
            const fileSize = e.target.files[0].size;
            if (fileSize > 10 * 1024 * 1024) { // 10 Mo
                alert('Le fichier est trop volumineux (max 10 Mo)');
                $(this).val(''); // Réinitialiser le champ
                $(this).siblings('.file-label').html('📁 Glisser-déposer ou choisir un fichier');
            }
        } else {
            $(this).siblings('.file-label').html('📁 Glisser-déposer ou choisir un fichier');
        }
    });

    // Soumission du formulaire avec indicateur de chargement
    $('#uploadForm').submit(function(e) {
        const type = $('#type').val();
        
        // Validation des champs
        if (type === 'enseignant' && $('#id_enseignant').val() === '') {
            e.preventDefault();
            alert('Veuillez sélectionner un enseignant');
            return false;
        } 
        else if (type === 'etudiant' && $('#niveau').val() === '') {
            e.preventDefault();
            alert('Veuillez sélectionner un niveau');
            return false;
        }
        
        if ($('#fichier')[0].files.length === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner un fichier PDF');
            return false;
        }
        
        // Afficher l'indicateur de chargement
        $('#loadingOverlay').show();
        $('#submitBtn').prop('disabled', true);
        
        // Simulation de progression
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 10;
            if (progress > 90) progress = 90;
            $('#uploadProgress').css('width', progress + '%');
        }, 300);
        
        setTimeout(() => {
            clearInterval(progressInterval);
            $('#uploadProgress').css('width', '100%');
        }, 3000);
        
        return true;
    });
});