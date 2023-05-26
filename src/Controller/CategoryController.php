<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class CategoryController extends AbstractController
{
    #[Route('/category/create', name: 'create_category')]
    public function create(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $new_category = new Category();
        $response = new JsonResponse();

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
        $new_category->setName($name);

        # Save
        $categoryRepository->save($new_category, true);

        $response->setData([
            'success' => true,
            'data' =>
            [
                'id' => $new_category->getId(),
                'title' => $new_category->getName()
            ]
        ]);
        return $response;
    }

    #[Route('/category', name: 'list_category')]
    public function read(CategoryRepository $categoryRepository): JsonResponse
    {
        $response = new JsonResponse();
        $categories = $categoryRepository->findAll();
        $categoryList = [];

        if (count($categories) === 0){
            $response->setData([
                'success' => false,
                'error' => 'No note has been registered'
            ]);
            return $response;
        }

        # Iterate to build array with data
        foreach ($categories as $category) {
            $categoryList[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        };
        
        $response->setData([
            'success' => true,
            'data' => $categoryList
        ]);
        return $response;
    }

    #[Route('/category/delete/{id}', name: 'delete_category')]
    public function delete(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        $response = new JsonResponse();
        $category_to_delete = $categoryRepository->find($id);
        if (!$category_to_delete) {
            return $this->json('No user found for id' . $id, 404);
        }

        $categoryRepository->remove($category_to_delete, true);

        # Response body
        $response->setData([
            'success' => true,
            'data' => 'Category with id ' . $id . ' has been deleted successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }

    #[Route('/category/edit/{id}', name: 'edit_category')]
    public function update(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {

        $category_to_update = $entityManager->getRepository(Category::class)->find($id);
        if (!$category_to_update) {
            return $this->json('No category found for id' . $id, 404);
        }

        # Get params
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        # Update
        if ($category_to_update->getName() !== $name) {
            $category_to_update->setName($name);
        }
        $entityManager->flush();

        # Prepare response
        $response = new JsonResponse();
        $response->setData([
            'success' => true,
            'data' => 'Category with id ' . $id . ' has been updated successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }
}
