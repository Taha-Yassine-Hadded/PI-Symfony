<?php

namespace App\Controller\ProjectController;

use App\Service\Projet\ProjectService;
use App\Service\Projet\TacheService;
use App\Service\Projet\FichierService;
use App\Entity\EtatEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tache;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\Projet\ProjectMembersService;
use App\Entity\ProjectMembers;
use App\Entity\Fichier;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Notifier\Notification\Notification;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;

class HomeStudentController extends AbstractController
{
    private $userId;
    private $projectService;
    private $tacheService;
    private $entityManager;
    private $fichierService;
    private $projectMembersService;
    // private $projectMembersUserService;


    public function __construct(Security $security,EntityManagerInterface $entityManager,ProjectService $projectService, TacheService $tacheService , ProjectMembersService $projectMembersService , FichierService $fichierService  /* ,ProjectMembersUserService $projectMembersUserService*/)
    {
        $this->userId = $security->getUser()->getId();
        if (!$this->userId) {
            throw new \RuntimeException('User not authenticated');
        }
        $this->projectService = $projectService;
        $this->fichierService = $fichierService;
        $this->tacheService = $tacheService;
        $this->entityManager = $entityManager;
        $this->projectMembersService = $projectMembersService;
    }

    #[Route('/home-student', name: 'home-student')]
    public function showProjects(): Response
    {
        $userid = $this->userId; // Remplacez ceci par l'ID de l'utilisateur dont vous voulez récupérer les projets

        // Récupérer les noms de projet pour affichage
        $projectNames = $this->projectService->getProjectNamesForUserId($userid);



        return $this->render('/Project/student/HomeStudent.html.twig', [
            'projectNames' => $projectNames,
            'taches' => [], // Initialiser avec une liste vide, car aucune tâche n'est sélectionnée par défaut
            'fichiers' => [],
            'etatEnumValues' => [],


        ]);

    }

    #[Route('/home-student/{projectId}', name: 'home-student-project')]
    public function index(Request $request, $projectId ,SessionInterface $session): Response
    {
        $userid = $this->userId;

        $taches = $this->tacheService->getTachesByUserIdAndProjectId($userid, $projectId);

        $projectNames = $this->projectService->getProjectNamesForUserId($userid);
        $fichiers = $this->fichierService->getFichiersByProjectId($projectId);

        $etatEnumValues = EtatEnum::getValidStates();
        foreach ($taches as $tache) {
            $tache->isLate = $tache->getDedline() <= new \DateTime();
        }
        return $this->render('/Project/student/HomeStudent.html.twig', [
            'projectNames' => $projectNames,
            'taches' => $taches,
            'etatEnumValues' => $etatEnumValues,
            'fichiers' => $fichiers,


        ]);
    }


    #[Route('/update-tache-etat/{tacheId}/{newState}', name: 'update_tache_etat')]
    public function updateEtat($tacheId, $newState, TacheService $tacheService): JsonResponse
    {
        try {
            $tache = $tacheService->updateEtat($tacheId, $newState);
            if (!$tache) {
                return new JsonResponse(['success' => false, 'message' => 'Tâche non trouvée'], Response::HTTP_NOT_FOUND);
            }
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update-task-description/{taskId}', name: 'update_task_description')]
    public function updateTaskDescription(Request $request, $taskId, TacheService $tacheService): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true);
            $newDescription = $requestData['description'] ?? null;
            if ($newDescription === null) {
                return new JsonResponse(['success' => false, 'message' => 'Description manquante'], Response::HTTP_BAD_REQUEST);
            }
            $tacheService->updateTaskDescription($taskId, $newDescription);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update-task-deadline/{tacheId}', name: 'update-task-deadline')]
    public function updateTaskDeadline($tacheId, Request $request, TacheService $tacheService): JsonResponse
    {
        try {
            // Récupérer la nouvelle deadline à partir des données de la requête
            $requestData = json_decode($request->getContent(), true);
            $newDeadline = $requestData['deadline'] ?? null;

            // Vérifier si la nouvelle deadline est valide
            if ($newDeadline === null) {
                return new JsonResponse(['success' => false, 'message' => 'Nouvelle date de délai manquante'], Response::HTTP_BAD_REQUEST);
            }

            // Appeler la méthode du service pour mettre à jour la deadline de la tâche
            $tache = $tacheService->updateDeadline($tacheId, $newDeadline);

            // Vérifier si la tâche a été trouvée et mise à jour avec succès
            if (!$tache) {
                return new JsonResponse(['success' => false, 'message' => 'Tâche non trouvée'], Response::HTTP_NOT_FOUND);
            }

            // Retourner une réponse JSON indiquant le succès de la mise à jour
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            // Capturer toute exception survenue et renvoyer une réponse d'erreur
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour de la date de délai de la tâche'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete-tasks', name: 'delete_tasks')]
    public function deleteTasks(Request $request, TacheService $tacheService): JsonResponse
    {
        try {
            $requestData = json_decode($request->getContent(), true);
            $tasksToDelete = $requestData['tasks'] ?? [];

            foreach ($tasksToDelete as $taskId) {
                $tacheService->deleteTaskById($taskId);
            }

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/add-task/{projectId}', name: 'add_task')]
    public function addTask(Request $request, ProjectMembersService $projectMembersService, TacheService $tacheService, int $projectId): JsonResponse
    {
        try {
            // Récupérer l'ID de l'utilisateur actuel
            $userid = $this->userId;
            dump($projectId);
            // Récupérer l'ID du membre pour l'utilisateur actuel et le projet spécifié
            $memberId = $projectMembersService->findIdMemberByUserIdAndProjectId($userid, $projectId);

            // Récupérer les données du formulaire
            $requestData = json_decode($request->getContent(), true);
            $description = $requestData['description'] ?? null;
            $deadline = $requestData['deadline'] ?? null;

            // Vérifier que la description et la deadline ne sont pas vides
            if (!$description || !$deadline) {
                return new JsonResponse(['success' => false, 'message' => 'Description ou deadline manquante'], Response::HTTP_BAD_REQUEST);
            }

            // Créer une nouvelle instance de tâche
            $task = new Tache();
            $task->setDescription($description);
            $task->setDateAjout(new \DateTime()); // Date actuelle
            $task->setDedline(new \DateTime($deadline));
            $task->setEtat(EtatEnum::AFaire); // Utilisation de la valeur française pour l'état

            // Récupérer l'objet ProjectMembers correspondant à l'ID du membre
            $member = $projectMembersService->findById($memberId);

            // Vérifier si le membre existe
            if (!$member) {
                return new JsonResponse(['success' => false, 'message' => 'Membre introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Assigner le membre à la tâche
            $task->setMember($member);

            // Ajouter la tâche à la base de données en utilisant le service TacheService
            $tacheService->ajouterTache($task);

            // Retourner une réponse JSON avec l'ID de la tâche ajoutée
            return new JsonResponse(['success' => true, 'taskId' => $task->getId()]);
        } catch (\Exception $e) {
            // Gérer les erreurs
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/import-fichier', name: 'import_fichier')]
    public function importFichier(Request $request, ProjectMembersService $projectMembersService): Response
    {
        // Récupérer l'ID de l'utilisateur (vous pouvez remplacer 1 par la méthode appropriée pour récupérer l'ID de l'utilisateur)
        $userId = $this->userId;

        // Récupérer l'ID du projet à partir des données de la requête
        $projectId = $request->request->get('projectId');

        // Trouver l'ID du membre
        $memberId = $projectMembersService->findIdMemberByUserIdAndProjectId($userId, $projectId);

        // Vérifier si un membre a été trouvé pour cet utilisateur et ce projet
        if (!$memberId) {
            return new Response('Aucun membre trouvé pour cet utilisateur et ce projet', Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le fichier envoyé depuis le formulaire
        $uploadedFile = $request->files->get('file');

        // Vérifier si un fichier a été envoyé
        if (!$uploadedFile instanceof UploadedFile) {
            return new Response('Aucun fichier n\'a été envoyé', Response::HTTP_BAD_REQUEST);
        }

        // Générer un nom unique pour le fichier pour éviter les collisions
        $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();

        // Déplacer le fichier vers le répertoire où vous souhaitez le stocker
        try {
            $uploadedFile->move(
                $this->getParameter('dossier_fichiers'), // Le répertoire où stocker les fichiers (à définir dans votre configuration Symfony)
                $fileName
            );
        } catch (FileException $e) {
            // Gérer les erreurs lors du déplacement du fichier
            // Par exemple, renvoyer un message d'erreur ou rediriger avec un message d'erreur
            return new Response('Une erreur s\'est produite lors de l\'import du fichier', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Créer une nouvelle instance de l'entité Fichier
        $fichier = new Fichier();
        $fichier->setNom($uploadedFile->getClientOriginalName()); // Nom d'origine du fichier
        $fichier->setPath($fileName); // Chemin vers le fichier
        $fichier->setDateAjout(new \DateTime());

        // Récupérer l'objet ProjectMembers correspondant à l'ID du membre
        $member = $this->getDoctrine()->getRepository(ProjectMembers::class)->find($memberId);

        // Associer le membre au fichier
        $fichier->setMember($member);

        // Enregistrez l'entité Fichier en base de données en utilisant Doctrine
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($fichier);
        $entityManager->flush();

        // Rediriger l'utilisateur vers une autre page ou retourner un message de confirmation
        return new Response('Le fichier a été importé avec succès', Response::HTTP_OK);
    }
    #[Route('/download/{id}', name: 'download_fichier_by_id')]
    public function telechargerFichier(Request $request, FichierService $fichierService): Response
    {
        try {
            // Récupérer l'ID du fichier à télécharger à partir des paramètres de la route
            $fichierId = $request->attributes->get('id');
            echo $fichierId;

            // Vérifier si l'ID du fichier est fourni dans la requête
            if (!$fichierId) {
                throw new \Exception('L\'ID du fichier n\'a pas été fourni.');
            }

            // Récupérer l'objet Fichier à partir de son ID
            $fichier = $fichierService->getFichierById($fichierId);

            // Vérifier si le fichier a été trouvé
            if (!$fichier) {
                throw new \Exception('Le fichier demandé n\'a pas été trouvé.');
            }

            // Obtenez le chemin du fichier
            $pathFichier = $fichier->getPath();
            echo $pathFichier;

            // Vérifiez si le fichier existe
            ///if (!file_exists("C:\Users\raahm\Desktop\\" . $pathFichier)){
            //throw new \Exception('Le fichier n\'existe pas.');
            // }

            // Récupérer le contenu du fichier
            $fileContent = file_get_contents("C:\Users\\raahm\Desktop\\" . $pathFichier);


            // Créer une réponse avec le contenu du fichier
            $response = new Response($fileContent);

            // Définir les en-têtes pour indiquer que le contenu est un téléchargement
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($pathFichier) . '"');

            return $response;
        } catch (\Exception $e) {
            // En cas d'erreur, renvoyer une réponse avec le message d'erreur
            return new Response('Erreur lors du téléchargement du fichier : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}