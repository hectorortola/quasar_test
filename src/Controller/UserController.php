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
        
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if (empty($name)) {
            $response->setData([
                'success' => false,
                'error' => 'Name cannot be empty',
                'data' => null
            ]);
            return $response;
        }

        $new_user->setName($name);
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
        $user_list = [];

        if (count($users) === 0){
            $response->setData([
                'success' => false,
                'error' => 'No user has been registered'
            ]);
            return $response;
        }

        foreach ($users as $user) {
            $user_list[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
            ];
        };
        
        $response->setData([
            'success' => true,
            'data' => $user_list
        ]);
        return $response;
    }

    #[Route('/user/delete/{id}', name: 'delete_user')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $response = new JsonResponse();
        $user_to_delete = $entityManager->getRepository(User::class)->find($id);
        if (!$user_to_delete) {
            $response->setData([
                'success' => false,
                'error' => 'No user found for id: ' . $id
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $entityManager->getRepository(User::class)->remove($user_to_delete, true);
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
        $response = new JsonResponse();
        $user_to_update = $entityManager->getRepository(User::class)->find($id);

        if (!$user_to_update) {
            $response->setData([
                'success' => false,
                'error' => 'No user found for id: ' . $id
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if ($user_to_update->getName() !== $name) {
            $user_to_update->setName($name);
        }
        $entityManager->flush();
        
        $response->setData([
            'success' => true,
            'data' => 'User with id ' . $id . ' has been updated successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }
}
