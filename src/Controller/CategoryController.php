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

        $new_category->setName($name);
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
        $category_list = [];

        if (count($categories) === 0){
            $response->setData([
                'success' => false,
                'error' => 'No note has been registered'
            ]);
            return $response;
        }

        foreach ($categories as $category) {
            $category_list[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        };
        
        $response->setData([
            'success' => true,
            'data' => $category_list
        ]);
        return $response;
    }

    #[Route('/category/delete/{id}', name: 'delete_category')]
    public function delete(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        $response = new JsonResponse();
        $category_to_delete = $categoryRepository->find($id);
        if (!$category_to_delete) {
            $response->setData([
                'success' => false,
                'error' => 'No user found for id '. $id
            ]);
            $response->setStatusCode(404);
            return $response;
        }

        $categoryRepository->remove($category_to_delete, true);
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

        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if ($category_to_update->getName() !== $name) {
            $category_to_update->setName($name);
        }
        $entityManager->flush();

        $response = new JsonResponse();
        $response->setData([
            'success' => true,
            'data' => 'Category with id ' . $id . ' has been updated successfully'
        ]);
        $response->setStatusCode(200);
        return $response;
    }
}
