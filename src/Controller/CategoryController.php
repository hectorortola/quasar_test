<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\DataSerializer;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends DataSerializer
{
    #[Route('/category', name: 'create_category', methods:['POST'])]
    public function create(Request $request, CategoryRepository $categoryRepository): Response
    {
        $new_category = new Category();
        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if (empty($name)) {
            return new Response('Name cannot be empty', 422);
        }

        $new_category->setName($name);
        $categoryRepository->save($new_category, true);
        $jsonData = parent::serialize($new_category);

        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/category', name: 'list_category', methods: ['GET'])]
    public function read(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        $category_list = [];

        if (count($categories) === 0){
            return new Response('No note has been registered', 200);
        }

        foreach ($categories as $category) {
            $category_list[] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        };
        
        $jsonData = parent::serialize($category_list);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }

    #[Route('/category/{id}', name: 'delete_category', methods: ['DELETE'])]
    public function delete(int $id, CategoryRepository $categoryRepository): Response
    {
        $category_to_delete = $categoryRepository->find($id);
        if (!$category_to_delete) {
            return new Response('No user found for id '. $id, 404);
        }

        $categoryRepository->remove($category_to_delete, true);
        return new Response( 'Category with id ' . $id . ' has been deleted successfully', 200);
    }

    #[Route('/category/{id}', name: 'edit_category', methods:['PUT'])]
    public function update(Request $request, int $id, CategoryRepository $categoryRepository): Response
    {
        $category_to_update = $categoryRepository->find($id);
        if (!$category_to_update) {
            return new Response('No category found for id' . $id, 404);
        }

        $parameters = json_decode($request->getContent(), true);
        $name = $parameters['name'];

        if ($category_to_update->getName() !== $name) {
            $category_to_update->setName($name);
        }
        $categoryRepository->flush();
        $jsonData = parent::serialize($category_to_update);
        return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
    }
}
