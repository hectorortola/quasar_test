<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/create', name: 'create_user')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $new_user = new User();
        
        # Get request parameters
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        # Check if fields are empty
        if (empty($name)) {
            $response->setData([
                'success' => false,
                'error' => 'Name cannot be empty',
                'data' => null
            ]);
            return $response;
        }

        # Set attributes to the new user
        $new_user->setName($name);

        # Save
        $entityManager->getRepository(User::class)->save($new_user, true);

        $response->setData([
            'success' => true,
            'data' =>
            [
                'id' => $new_user->getId(),
                'name' => $new_user->getName()
            ]
        ]);
        return $response;
    }

    #[Route('/user', name: 'list_user')]
    public function read(EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $users = $entityManager->getRepository(User::class)->findAll();
        $userList = [];

        if (count($users) === 0){
            $response->setData([
                'success' => false,
                'error' => 'No user has been registered'
            ]);
            return $response;
        }

        # Iterate to build array with data
        foreach ($users as $user) {
            $userList[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
            ];
        };
        
        $response->setData([
            'success' => true,
            'data' => $userList
        ]);
        return $response;
    }

    #[Route('/user/delete/{id}', name: 'delete_user')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $user_to_delete = $entityManager->getRepository(User::class)->find($id);
        if (!$user_to_delete) {
            return $this->json('No user found for id: ' . $id, 404);
        }

        $entityManager->getRepository(User::class)->remove($user_to_delete, true);

        # Response body
        $response->setData([
            'success' => true,
            'data' => 'User with id ' . $id . ' has been deleted successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/user/edit/{id}', name: 'edit_user')]
    public function update(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user_to_update = $entityManager->getRepository(User::class)->find($id);
        if (!$user_to_update) {
            return $this->json('No user found for id: ' . $id, 404);
        }

        # Get params
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        # Update
        if ($user_to_update->getName() !== $name) {
            $user_to_update->setName($name);
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
}
