<?php
namespace App\Tests\Controller;

use App\Controller\CategoriesController;
use App\Entity\Categories;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoriesControllerTest extends KernelTestCase
{
    public function testList()
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $category = new Categories();
        $category->setSlug("test");
        $category->setName("test")
                ->setCategoryOrder(1);

        $productsRepository = $this->createMock(ProductsRepository::class);
        $request = new Request();

        $controller = new CategoriesController();

        $response = $controller->list($category, $productsRepository, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListWithInvalidCategory()
    {
        $this->expectException(NotFoundHttpException::class);

        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $categoryRepository = $container->get('App\Repository\CategoriesRepository');
        $productsRepository = $this->createMock(ProductsRepository::class);
        $request = new Request();

        $controller = new CategoriesController();

        $response = $controller->list($categoryRepository->find(123), $productsRepository, $request);
    }
}