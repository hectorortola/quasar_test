<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends AbstractController
{
    #[Route('/note/create', name: 'create_note')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $new_note = new Note();
        $new_note->setDateCreated(new \DateTime());

        # Get request parameters
        $parameters = json_decode($request->getContent(), true);
        $content = $parameters['content'];
        $user_id = $parameters['user_id'];

        # Check if content is empty
        if (strlen($content) <= 0) {
            $response->setData([
                'success' => false,
                'error' => 'Content cannot be empty',
                'data' => null
            ]);
            $response->setStatusCode(422);
            return $response;
        }

        # Check if user_id exist
        if (!$user_id or $user_id <= 0) {
            $response->setData([
                'success' => false,
                'error' => 'User_id cannot be empty or 0',
                'data' => null
            ]);
            $response->setStatusCode(422);
            return $response;
        }
        $user = $entityManager->getRepository(User::class)->find($user_id);
        if (!$user) {
            $response->setData([
                'success' => false,
                'error' => 'User not found',
                'data' => null
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $new_note->setContent($content);
        $new_note->setOwner($user);

        # Save
        $entityManager->getRepository(Note::class)->save($new_note, true);

        $response->setData([
            'success' => true,
            'data' =>
            [
                'id' => $new_note->getId(),
                'content' => $new_note->getContent(),
                'owner' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ],
                'date_created' => $new_note->getDateCreated(),
            ]
        ]);
        return $response;
    }

    #[Route('/notes', name: 'list_note')]
    public function read(EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $notes = $entityManager->getRepository(Note::class)->findAll();
        $noteList = [];

        if (count($notes) === 0) {
            $response->setData([
                'success' => false,
                'error' => 'No note has been registered'
            ]);
            return $response;
        }

        # Iterate to build array with data
        foreach ($notes as $note) {
            $user = $entityManager->getRepository(User::class)->find($note->getOwner());

            $note_categories = $note->getCategories();
            $category_list = [];

            if (count($note_categories) === 0) {
                $category_list[] = [
                    'category' => []
                ];
            }

            if (count($note_categories) === 1) {
                $category_list[] = [
                    'category' => $note_categories[0]->getName()
                ];
            }

            if (count($note_categories) > 1) {
                foreach ($note_categories as $category) {
                    $category_list[] = [
                        'category' => $category->getName()
                    ];
                };
            }

            $noteList[] = [
                'id' => $note->getId(),
                'content' => $note->getContent(),
                'date_created' => $note->getDateCreated(),
                'categories' => $category_list,
                'owner' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ],

            ];
        };

        $response->setData([
            'success' => true,
            'data' => $noteList
        ]);
        return $response;
    }

    #[Route('/note/delete/{id}', name: 'delete_note')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $note_to_delete = $entityManager->getRepository(Note::class)->find($id);

        if (!$note_to_delete) {
            $response->setData([
                'success' => false,
                'error' => 'No note found for id: ' . $id
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $entityManager->getRepository(Note::class)->remove($note_to_delete, true);

        $response->setData([
            'success' => true,
            'data' => 'Note with id ' . $id . ' has been deleted successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/note/edit/{id}', name: 'edit_note')]
    public function update(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $note_to_update = $entityManager->getRepository(Note::class)->find($id);

        if (!$note_to_update) {
            $response->setData([
                'success' => false,
                'error' => 'No note found for id: ' . $id
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        # Get params
        $parameters = json_decode($request->getContent(), true);
        $content = $parameters['content'];
        $user_id = $parameters['user_id'];

        # Check if content is empty
        if (strlen($content) <= 0) {
            $response->setData([
                'success' => false,
                'error' => 'Content cannot be empty',
                'data' => null
            ]);
            $response->setStatusCode(422);
            return $response;
        }

        # Check if user_id exist
        if (!$user_id) {
            $response->setData([
                'success' => false,
                'error' => 'User_id cannot be empty',
                'data' => null
            ]);
            $response->setStatusCode(422);
            return $response;
        }

        $user = $entityManager->getRepository(User::class)->find($user_id);
        if (!$user) {
            $response->setData([
                'success' => false,
                'error' => 'User not found',
                'data' => null
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        # Update 
        if ($note_to_update->getContent() !== $content) {
            $note_to_update->setContent($content);
        }

        if ($note_to_update->getOwner() !== $user) {
            $note_to_update->seteOwner($user);
        }

        $entityManager->flush();

        # Prepare response
        $response = new JsonResponse();
        $response->setData([
            'success' => true,
            'data' => 'User with id ' . $id . ' has been updated successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/note/{id}/add-category/{category_id}', name: 'add_category')]
    public function addCategory(int $id, int $category_id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();

        $note = $entityManager->getRepository(Note::class)->find($id);
        $category = $entityManager->getRepository(Category::class)->find($category_id);

        if (!$note || !$category) {
            $response->setData([
                'success' => false,
                'error' => 'Note or category not found',
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $note->addCategory($category);
        $entityManager->flush();

        $response->setData([
            'success' => true,
            'error' => 'Category has been added successfully',
        ]);

        return $response;
    }

    #[Route('/note/{id}/remove-category/{category_id}', name: 'remove_category')]
    public function removeCategory(int $id, int $category_id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();

        $note = $entityManager->getRepository(Note::class)->find($id);
        $category = $entityManager->getRepository(Category::class)->find($category_id);

        if (!$note || !$category) {
            $response->setData([
                'success' => false,
                'error' => 'Note or category not found',
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $note->removeCategory($category);
        $entityManager->flush();

        $response->setData([
            'success' => true,
            'error' => 'Category has been removed successfully',
        ]);

        return $response;
    }

    #[Route('/old-notes', name: 'read_old_notes')]
    public function checkOldNotes(EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $notes = $entityManager->getRepository(Note::class)->findOldNotes();

        if(count($notes) < 1){
            $response->setData([
                'success' => true,
                'data' => 'No notes older than 7 days found',
            ]);
            return $response;
        }

        $notes_list = [];
        foreach ($notes as $note){
            $user = $entityManager->getRepository(User::class)->find($note->getOwner());
            $note_categories = $note->getCategories();
            $category_list = [];

            if (count($note_categories) === 0) {
                $category_list[] = [
                    'category' => []
                ];
            }

            if (count($note_categories) === 1) {
                $category_list[] = [
                    'category' => $note_categories[0]->getName()
                ];
            }

            if (count($note_categories) > 1) {
                foreach ($note_categories as $category) {
                    $category_list[] = [
                        'category' => $category->getName()
                    ];
                };
            }

            $note_list[] = [
                'id' => $note->getId(),
                'content' => $note->getContent(),
                'date_created' => $note->getDateCreated(),
                'categories' => $category_list,
                'owner' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                ],
            ];
        };

        $response->setData([
            'success' => true,
            'data' => $note_list,
        ]);
        return $response;
    }
}
