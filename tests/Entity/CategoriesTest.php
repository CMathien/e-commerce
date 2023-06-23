<?php 
namespace App\Tests\Entity;

use App\Entity\Categories;
use App\Entity\Products;
use PHPUnit\Framework\TestCase;

class CategoriesTest extends TestCase
{
    public function testSetName()
    {
        $category = new Categories();
        $name = 'Test Category';

        $category->setName($name);

        $this->assertEquals($name, $category->getName());
    }

    public function testSetCategoryOrder()
    {
        $category = new Categories();
        $order = 1;

        $category->setCategoryOrder($order);

        $this->assertEquals($order, $category->getCategoryOrder());
    }

    public function testSetParent()
    {
        $category = new Categories();
        $parent = new Categories();

        $category->setParent($parent);

        $this->assertEquals($parent, $category->getParent());
    }

    public function testAddCategory()
    {
        $category = new Categories();
        $childCategory = new Categories();

        $category->addCategory($childCategory);

        $this->assertTrue($category->getCategories()->contains($childCategory));
        $this->assertEquals($category, $childCategory->getParent());
    }

    public function testRemoveCategory()
    {
        $category = new Categories();
        $childCategory = new Categories();
        $category->addCategory($childCategory);

        $category->removeCategory($childCategory);

        $this->assertFalse($category->getCategories()->contains($childCategory));
        $this->assertNull($childCategory->getParent());
    }

    public function testAddProduct()
    {
        $category = new Categories();
        $product = new Products();

        $category->addProduct($product);

        $this->assertTrue($category->getProducts()->contains($product));
        $this->assertEquals($category, $product->getCategories());
    }

    public function testRemoveProduct()
    {
        $category = new Categories();
        $product = new Products();
        $category->addProduct($product);

        $category->removeProduct($product);

        $this->assertFalse($category->getProducts()->contains($product));
        $this->assertNull($product->getCategories());
    }
}