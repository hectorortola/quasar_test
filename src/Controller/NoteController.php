<?php

namespace App\Controller;

use App\Entity\DataSerializer;
use App\Entity\Note;
use App\Repository\CategoryRepository;
use App\Repository\NoteRepository;
use App\Repository\UserRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends DataSerializer
{
    #[Route('/note', name: 'create_note', methods: ['POST'])]
    public function create(Request $request, NoteRepository $noteRepository, UserRepository $userRepository): Response
    {
        $new_note = new Note();
        $new_note->setDateCreated(new \DateTime());
        $parameters = json_decode($request->getContent(), true);
        $content = $parameters['content'];
        $user_id = $parameters['user_id'];

        if (strlen($content) <= 0) {
            return new Response('Error: Content cannot be empty', 422);
        }

        if (!$user_id or $user_id <= 0) {
            return new Response('Error: User_id cannot be empty or 0', 422);
        }

        $user = $userRepository->find($user_id);
        if (!$user) {
            return new Response('Error: User not found', 422);
        }

        $new_note->setContent($content);
        $new_note->setOwner($user);
        $noteRepository->save($new_note, true);
        $jsonData = parent::serialize($new_note);
        
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/notes', name: 'list_note', methods: ['GET'])]
    public function read(NoteRepository $noteRepository, UserRepository $userRepository): Response
    {
        $notes = $noteRepository->findAll();
        $note_list = [];

        if (count($notes) === 0) {
            return new Response('No note has been registered yet', 200);
        }

        foreach ($notes as $note) {
            $user = $userRepository->find($note->getOwner());
            $note_categories = $note->getCategories();
            $category_list = [];

            if (count($note_categories) === 0) {
                $category_list[] = [
                    'category' => []
                ];
            }else{
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
            $jsonData = parent::serialize($note_list);
        };

        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/note/{id}', name: 'delete_note', methods: ['DELETE'])]
    public function delete(int $id, NoteRepository $noteRepository): Response
    {
        $note_to_delete = $noteRepository->find($id);
        if (!$note_to_delete) {
            return new Response('Error: No note found for id: ' . $id, 404);
        }

        $noteRepository->remove($note_to_delete, true);
        return new Response('Note with id ' . $id . ' has been deleted successfully', 200);
    }

    #[Route('/note/{id}', name: 'edit_note', methods: ['PUT'])]
    public function update(Request $request, int $id, NoteRepository $noteRepository, UserRepository $userRepository): Response
    {  
        $note_to_update = $noteRepository->find($id);
        if (!$note_to_update) {
            return new Response('Error: No note found for id: ' . $id, 404);
        }

        $parameters = json_decode($request->getContent(), true);
        $content = $parameters['content'];
        $user_id = $parameters['user_id'];

        if (strlen($content) <= 0) {
            return new Response('Content cannot be empty', 422);
        }

        if (!$user_id) {            
            return new Response('User_id cannot be empty', 422);
        }

        $user = $userRepository->find($user_id);
        if (!$user) {
            return new Response('User not found', 404);
        }


        if ($note_to_update->getContent() !== $content) {
            $note_to_update->setContent($content);
        }
        if ($note_to_update->getOwner() !== $user) {
            $note_to_update->setOwner($user);
        }
        $noteRepository->flush();
        return new Response('User with id ' . $id . ' has been updated successfully', 200);
    }

    #[Route('/note/{id}/category/{category_id}', name: 'add_category', methods: ['POST'])]
    public function addCategory(int $id, int $category_id, CategoryRepository $categoryRepository, NoteRepository $noteRepository): Response
    {
        $note = $noteRepository->find($id);
        $category = $categoryRepository->find($category_id);

        if (!$note || !$category) {
            return new Response('Note or category not found',404);
        }

        $note->addCategory($category);
        $noteRepository->flush();

        $jsonData = parent::serialize($note);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/note/{id}/category/{category_id}', name: 'remove_category',  methods: ['DELETE'])]
    public function removeCategory(int $id, int $category_id, CategoryRepository $categoryRepository, NoteRepository $noteRepository): Response
    {
        $note = $noteRepository->find($id);
        $category = $categoryRepository->find($category_id);

        if (!$note || !$category) {
            return new Response('Note or category not found', 404);
        }

        $note->removeCategory($category);
        $noteRepository->flush();

        $jsonData = parent::serialize($note);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/old-notes', name: 'read_old_notes', methods: ['GET'])]
    public function checkOldNotes(NoteRepository $noteRepository, UserRepository $userRepository): Response
    {
        $notes = $noteRepository->findOldNotes();
        if (count($notes) < 1) {
            return new Response('No notes older than 7 days found', 404);
        }

        foreach ($notes as $note) {
            $user = $userRepository->find($note->getOwner());
            $note_categories = $note->getCategories();
            $category_list = [];

            if (count($note_categories) === 0) {
                $category_list[] = [
                    'category' => []
                ];
            }else{
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

        $jsonData = parent::serialize($note_list);
        return new Response($jsonData, 200, ['Content-Type'=> 'application/json']);
    }
}
