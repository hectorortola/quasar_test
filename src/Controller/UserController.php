<?php

namespace App\Controller;

use App\Entity\DataSerializer;
use App\Entity\User;
use App\Repository\UserRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends DataSerializer
{

    #[Route('/user', name: 'create_user', methods: ['POST'])]
    public function create(Request $request, UserRepository $userRepository): Response
    {
        $new_user = new User();
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if (empty($name)) {
            return new Response('Error: Name cannot be empty', 422);
        }

        $new_user->setName($name);
        $userRepository->save($new_user, true);
        $jsonData = parent::serialize($new_user);
        return new Response($jsonData, 200,  ['Content-Type' => 'application/json']);
    }

    #[Route('/users', name: 'list_user', methods: ['GET'])]
    public function read(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        if (count($users) === 0) {
            return new Response('No user has been registered yet', 200);
        }

        $user_list = [];
        foreach ($users as $user) {
            $user_list[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
            ];
        };

        $jsonData = parent::serialize($user_list);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/user/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function delete(int $id, UserRepository $userRepository): Response
    {
        $user_to_delete = $userRepository->find($id);
        if (!$user_to_delete) {
            return new Response('Error: No user found for id: ' . $id, 404);
        }
        $userRepository->remove($user_to_delete, true);
        return new Response('User with id ' . $id . ' has been deleted successfully', 200);
    }

    #[Route('/user/{id}', name: 'edit_user', methods: ['PUT'])]
    public function update(Request $request, int $id, UserRepository $userRepository): Response
    {
        $userToUpdate = $userRepository->find($id);
        if (!$userToUpdate) {
            return new Response('Error: No user found with id: ' . $id, 404);
        }

        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];
        if (empty($name)) {
            return new Response('Error: Name cannot be empty', 422);
        }

        if ($userToUpdate->getName() !== $name) {
            $userToUpdate->setName($name);
        }
        $userRepository->flush();

        $jsonData = parent::serialize($userToUpdate);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }
}
