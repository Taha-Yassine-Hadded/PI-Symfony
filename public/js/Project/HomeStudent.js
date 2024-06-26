function deleteTasks() {
    var checkedCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    var tasksToDelete = [];

    // Parcourir les cases à cocher sélectionnées et ajouter les ID des tâches à supprimer à un tableau
    checkedCheckboxes.forEach(function(checkbox) {
        tasksToDelete.push(checkbox.dataset.tacheId);
    });

    // Envoyer une requête AJAX pour supprimer les tâches de la base de données
    fetch('/delete-tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ tasks: tasksToDelete }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la suppression des tâches');
            }
            // Si la suppression est réussie, actualisez la page pour mettre à jour l'interface utilisateur
            window.location.reload();
        })
        .catch(error => console.error('Erreur lors de la suppression des tâches: ', error));
}
function editTaskDeadline(taskId, deadline) {
    // Créez un élément input de type date pour sélectionner la nouvelle date
    var dateInput = document.createElement('input');
    dateInput.type = 'date';
    dateInput.value = deadline;

    // Ajoutez un événement onChange pour détecter lorsque la date est modifiée
    dateInput.addEventListener('change', function() {
        // Mettez à jour la date de délai avec la nouvelle valeur
        updateTaskDeadline(taskId, dateInput.value);
    });

    // Remplacez l'image d'édition par l'élément input de type date
    var editImage = document.querySelector('#tache-' + taskId + ' img[alt="Éditer Date"]');
    editImage.parentNode.replaceChild(dateInput, editImage);
}
function updateTaskDeadline(taskId, newDeadline) {
    fetch('/update-task-deadline/' + taskId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ deadline: newDeadline }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de la date de délai de la tâche');
            }
            showAlertMessage("Date de délai modifiée avec succès", "/images/Project/checked.png");
            console.log('Date de délai de la tâche mise à jour avec succès');
        })
        .catch(error => console.error('Erreur lors de la mise à jour de la date de délai de la tâche: ', error));
}
function editTask(taskId, description) {
    // Vérifiez s'il existe déjà un champ de texte et un bouton "Enregistrer"
    var existingTextField = document.querySelector('#tache-' + taskId + ' input[type="text"]');
    var existingSaveButton = document.querySelector('#tache-' + taskId + ' button');

    if (!existingTextField && !existingSaveButton) {
        // Sélectionner le paragraphe contenant la description
        var descriptionParagraph = document.querySelector('#tache-' + taskId + ' p');
        var textField = document.createElement('input');
        textField.type = 'text';
        textField.value = description;
        textField.classList.add('textfield'); // Ajouter la classe 'textfield'



        // Remplacer le paragraphe par le champ de texte
        descriptionParagraph.parentNode.replaceChild(textField, descriptionParagraph);

        // Créer un bouton "Enregistrer"
        var saveButton = document.createElement('button');
        saveButton.classList.add('save-button'); // Ajouter la classe 'save-button'
        saveButton.textContent = 'Enregistrer';
        saveButton.addEventListener('click', function() {
            // Mettre à jour la description avec la valeur du champ de texte
            updateTaskDescription(taskId, textField.value);

            // Remplacer le champ de texte par le nouveau paragraphe
            var newDescriptionParagraph = document.createElement('p');
            newDescriptionParagraph.textContent = textField.value;
            textField.parentNode.replaceChild(newDescriptionParagraph, textField);

            // Supprimer le bouton "Enregistrer"
            saveButton.parentNode.removeChild(saveButton);
        });

        // Ajouter le bouton "Enregistrer" après le champ de texte
        textField.parentNode.insertBefore(saveButton, textField.nextSibling);
    }
}
function updateTaskDescription(taskId, newDescription) {
    fetch('/update-task-description/' + taskId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ description: newDescription }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de la description de la tâche');
            }
            showAlertMessage("Description modifiée avec succès", "/images/Project/checked.png");
            console.log('Description de la tâche mise à jour avec succès');
            // Afficher le message d'alerte avec l'image de vérification
        })
        .catch(error => console.error('Erreur lors de la mise à jour de la description de la tâche: ', error));
}
var selectedProjectId;
document.addEventListener("DOMContentLoaded", function() {
    const projectLinks = document.querySelectorAll('.project-link');

    projectLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault(); // Empêche le comportement par défaut du lien

            // Stocker l'ID du projet dans une variable globale
            selectedProjectId = this.getAttribute('data-project-id');

            window.location.href = "/home-student/" + selectedProjectId; // Redirige vers la route avec l'ID du projet
        });
    });
});
const openDrawerButton = document.querySelector('.open-drawer');
const drawer = document.querySelector('.drawer');
const listContainers = document.querySelectorAll('.list-container');

openDrawerButton.addEventListener('click', function() {
    drawer.classList.toggle('open');
    listContainers.forEach(container => {
        if (drawer.classList.contains('open')) {
            container.style.marginLeft = '140px'; // Réduire l'espace entre les conteneurs
        } else {
            container.style.marginLeft = '110px'; // Restaurer l'espace normal entre les conteneurs
        }
    });
});
// Gestion des événements de glisser-déposer pour empêcher le déplacement du contenu
listContainers.forEach(container => {
    container.addEventListener('dragover', function(ev) {
        ev.preventDefault(); // Empêcher le comportement par défaut (déplacement du contenu)
    });

    container.addEventListener('drop', function(ev) {
        ev.preventDefault(); // Empêcher le comportement par défaut (déplacement du contenu)
        const data = ev.dataTransfer.getData("text");
        ev.target.appendChild(document.getElementById(data));
    });
});
function allowDrop(ev) {
    ev.preventDefault();
}
function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.id);
}
function drop(ev, targetList) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text");
    var draggedItem = document.getElementById(data);
    ev.target.appendChild(draggedItem);

    var tacheId = data.split('-')[1];
    var newState = ''; // Nouvel état de la tâche

    // Déterminez le nouvel état en fonction de la liste cible
    switch (targetList) {
        case 'todo-list':
            newState = 'AFaire';
            break;
        case 'inprogress-list':
            newState = 'EnCours';
            break;
        case 'done-list':
            newState = 'Termine';
            break;
        default:
            newState = ''; // État par défaut
            break;
    }


    // Mise à jour de l'état de la tâche dans l'élément DOM
    draggedItem.dataset.etat = newState;

    // Envoi de la requête AJAX pour mettre à jour l'état de la tâche côté serveur
    updateTacheEtat(tacheId, newState);
}
window.updateTacheEtat = function(tacheId, newState) {
    console.log("Updating task state for task ID:", tacheId, "New state:", newState);
    fetch('/update-tache-etat/' + tacheId + '/' + newState, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({}),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de l\'état de la tâche');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue lors de la mise à jour de l\'état de la tâche');
            }
            console.log('État de la tâche mis à jour avec succès');
        })
        .catch(error => console.error('Erreur lors de la mise à jour de l\'état de la tâche: ', error));
}
function showAlertMessage(message, imagePath) {
    // Créer le conteneur pour le message d'alerte
    var alertContainer = document.createElement('div');
    alertContainer.classList.add('alert-message');

    // Créer l'image et le texte du message d'alerte
    var img = document.createElement('img');
    img.src = imagePath;
    img.alt = "Vérification";
    img.classList.add('alert-image');

    var text = document.createElement('span');
    text.textContent = message;
    text.classList.add('alert-text');

    // Ajouter l'image et le texte au conteneur
    alertContainer.appendChild(img);
    alertContainer.appendChild(text);

    // Ajouter le message d'alerte à la page
    document.body.appendChild(alertContainer);

    // Disparition du message d'alerte après 10 secondes
    setTimeout(function() {
        alertContainer.style.display = 'none';
    }, 5000); // 10 secondes en millisecondes
}

function deleteTasks() {
    var checkedCheckboxes = document.querySelectorAll('input[type="checkbox"]:checked');
    var tasksToDelete = [];

    // Vérifier s'il y a des cases à cocher sélectionnées
    if (checkedCheckboxes.length === 0) {
        // Afficher un message d'alerte demandant à l'utilisateur de sélectionner au moins une tâche
        showAlertMessage("Veuillez sélectionner au moins une tâche à supprimer", "/images/Project/mark.png");
        return; // Sortir de la fonction si aucune tâche n'est sélectionnée
    }

    // Parcourir les cases à cocher sélectionnées et ajouter les ID des tâches à supprimer à un tableau
    checkedCheckboxes.forEach(function(checkbox) {
        tasksToDelete.push(checkbox.dataset.tacheId);
    });

    // Envoyer une requête AJAX pour supprimer les tâches de la base de données
    fetch('/delete-tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ tasks: tasksToDelete }),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la suppression des tâches');
            }
            // Si la suppression est réussie, afficher un message d'alerte de succès
            showAlertMessages("Tâches supprimées avec succès", "/images/Project/checked.png");
            // Actualiser la page pour mettre à jour l'interface utilisateur
            window.location.reload();
        })
        .catch(error => console.error('Erreur lors de la suppression des tâches: ', error));
}
function showAlertMessages(message, imagePath) {
    // Créer le conteneur pour le message d'alerte
    var alertContainer = document.createElement('div');
    alertContainer.classList.add('alert-message');

    // Créer l'image et le texte du message d'alerte
    var img = document.createElement('img');
    img.src = imagePath;
    img.alt = "Vérification";
    img.classList.add('alert-image');

    var text = document.createElement('span');
    text.textContent = message;
    text.classList.add('alert-text');

    // Ajouter l'image et le texte au conteneur
    alertContainer.appendChild(img);
    alertContainer.appendChild(text);

    // Ajouter le message d'alerte à la page
    document.body.appendChild(alertContainer);

    // Disparition du message d'alerte après 5 secondes
    setTimeout(function() {
        alertContainer.style.display = 'none';
    }, 5000); // 5 secondes en millisecondes
}
function toggleAddTaskForm(element) {
    var overlay = document.getElementById('overlay');
    var form = document.getElementById('add-task-form');
    if (overlay.style.display === "none") {
        overlay.style.display = "block";
        form.style.display = "block";
    } else {
        overlay.style.display = "none";
        form.style.display = "none";
    }
}
function addTask() {
    // Obtenir le chemin de l'URL
    var path = window.location.pathname;
    // Extraire les derniers chiffres du chemin de l'URL
    var projectId = path.match(/\d+$/)[0];

    console.log("ID du projet : " + projectId);

    // Vérifier si projectId est défini
    if (projectId) {
        // Récupérer la description et la date limite de la tâche
        var description = document.getElementById('task-description-input').value;
        var deadline = document.getElementById('task-deadline-input').value;
        // Vérifier si les champs de description et de date limite sont remplis
        if (description && deadline) {
            // Envoyer une requête AJAX pour ajouter la tâche à la base de données
            fetch('/add-task/' + projectId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ description: description, deadline: deadline }),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur lors de l\'ajout de la tâche');
                    }
                    // Afficher un message de succès
                    showAlertMessages("Tâche ajoutée avec succès", "/images/Project/checked.png");
                    // Si l'ajout est réussi, actualiser la page pour mettre à jour l'interface utilisateur
                    window.location.reload();
                })
                .catch(error => console.error('Erreur lors de l\'ajout de la tâche: ', error));
        } else {
            // Afficher un message d'échec si l'un des champs n'est pas rempli
            showAlertMessages("Veuillez remplir tous les champs", "/images/Project/mark.png");
        }
    } else {
        console.error('ID du projet non trouvé dans l\'URL');
    }
}
document.getElementById('importBtn').addEventListener('click', function() {
    // Récupérer l'ID du projet à partir de l'URL
    var projectId = window.location.pathname.split('/').pop();

    // Créer un input de type fichier
    var input = document.createElement('input');
    input.type = 'file';
    // Déclencher le clic sur l'input
    input.click();
    // Ajouter un écouteur d'événements pour récupérer le fichier sélectionné
    input.addEventListener('change', function() {
        // Accéder au fichier sélectionné via input.files
        var file = input.files[0];
        // Créer un objet FormData pour envoyer le fichier et l'ID du projet
        var formData = new FormData();
        formData.append('file', file);
        formData.append('projectId', projectId);

        // Créer une requête AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/import-fichier'); // Remplacez l'URL par l'URL de votre contrôleur Symfony
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Succès de la requête, faire quelque chose si nécessaire
                console.log('Le fichier a été importé avec succès');
            } else {
                // Gérer les erreurs de la requête
                console.error('Erreur lors de l\'import du fichier : ', xhr.statusText);
            }
        };
        // Envoyer la requête avec les données du formulaire
        xhr.send(formData);
    });
});
